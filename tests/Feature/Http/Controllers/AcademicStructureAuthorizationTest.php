<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\ClassRoom;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Stream;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Subject;
use App\Models\Term;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcademicStructureAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_receptionist_with_student_update_cannot_edit_or_update_academic_structure_or_enrollments(): void
    {
        $user = $this->userWithPermissions('Receptionist', [
            'students.view',
            'students.update',
        ]);
        $records = $this->createAcademicRecords();

        $routes = [
            ['academic-years.edit', 'academic-years.update', $records['year'], $this->yearPayload()],
            ['terms.edit', 'terms.update', $records['term'], $this->termPayload($records['year'])],
            ['classes.edit', 'classes.update', $records['class'], $this->classPayload()],
            ['streams.edit', 'streams.update', $records['stream'], $this->streamPayload($records['class'])],
            ['subjects.edit', 'subjects.update', $records['subject'], $this->subjectPayload()],
            ['enrollments.edit', 'enrollments.update', $records['enrollment'], $this->enrollmentPayload($records)],
        ];

        $this->actingAs($user);

        foreach ($routes as [$editRoute, $updateRoute, $record, $payload]) {
            $this->get(route($editRoute, $record))->assertForbidden();
            $this->put(route($updateRoute, $record), $payload)->assertForbidden();
        }

        $this->assertSame('2026', $records['year']->fresh()->name);
        $this->assertSame('Class A', $records['class']->fresh()->name);
        $this->assertSame('active', $records['enrollment']->fresh()->status);
    }

    public function test_student_update_permission_does_not_render_academic_or_enrollment_edit_links(): void
    {
        $user = $this->userWithPermissions('Receptionist', [
            'students.view',
            'students.update',
        ]);
        $records = $this->createAcademicRecords();
        $indexRoutes = [
            ['academic-years.index', 'academic-years.edit', $records['year']],
            ['terms.index', 'terms.edit', $records['term']],
            ['classes.index', 'classes.edit', $records['class']],
            ['streams.index', 'streams.edit', $records['stream']],
            ['subjects.index', 'subjects.edit', $records['subject']],
            ['enrollments.index', 'enrollments.edit', $records['enrollment']],
        ];

        $this->actingAs($user);

        foreach ($indexRoutes as [$indexRoute, $editRoute, $record]) {
            $this->get(route($indexRoute))
                ->assertDontSee(route($editRoute, $record));
        }

        $this->get(route('students.index'))
            ->assertSee(route('students.edit', $records['student']));
    }

    public function test_authorized_academic_officer_can_update_each_academic_module(): void
    {
        $user = $this->userWithPermissions('Academic Officer', [
            'academic_structure.update',
            'enrollments.update',
        ]);
        $records = $this->createAcademicRecords();

        $responses = [
            $this->actingAs($user)->put(
                route('academic-years.update', $records['year']),
                $this->yearPayload('2027')
            ),
            $this->put(
                route('terms.update', $records['term']),
                $this->termPayload($records['year'], 'Term Two')
            ),
            $this->put(
                route('classes.update', $records['class']),
                $this->classPayload('Class B')
            ),
            $this->put(
                route('streams.update', $records['stream']),
                $this->streamPayload($records['class'], 'Stream B')
            ),
            $this->put(
                route('subjects.update', $records['subject']),
                $this->subjectPayload('MAT-2', 'Algebra')
            ),
            $this->put(
                route('enrollments.update', $records['enrollment']),
                $this->enrollmentPayload($records, 'completed')
            ),
        ];

        foreach ($responses as $response) {
            $response->assertRedirect();
        }

        $this->assertSame('2027', $records['year']->fresh()->name);
        $this->assertSame('Term Two', $records['term']->fresh()->name);
        $this->assertSame('Class B', $records['class']->fresh()->name);
        $this->assertSame('Stream B', $records['stream']->fresh()->name);
        $this->assertSame('Algebra', $records['subject']->fresh()->name);
        $this->assertSame('completed', $records['enrollment']->fresh()->status);
    }

    public function test_student_update_permission_still_allows_student_record_updates(): void
    {
        $user = $this->userWithPermissions('Receptionist', ['students.update']);
        $student = $this->createStudent();

        $this->actingAs($user)
            ->put(route('students.update', $student), [
                'admission_number' => 'ST-1',
                'first_name' => 'Updated',
                'last_name' => 'Student',
                'date_of_birth' => '2010-01-01',
                'gender' => 'Female',
                'status' => 'active',
            ])
            ->assertRedirect(route('students.index'));

        $this->assertSame('Updated', $student->fresh()->first_name);
    }

    public function test_permission_migration_grants_new_permissions_only_to_responsible_roles(): void
    {
        $roles = collect([
            'Super Administrator',
            'School Administrator',
            'Academic Officer',
            'Receptionist',
        ])->mapWithKeys(fn (string $name): array => [
            $name => Role::create(['name' => $name]),
        ]);

        $migration = require database_path(
            'migrations/2026_10_02_061430_add_academic_structure_and_enrollment_update_permissions.php'
        );
        $migration->up();

        foreach (['Super Administrator', 'School Administrator', 'Academic Officer'] as $roleName) {
            $this->assertTrue($roles[$roleName]->fresh()->permissions()->whereIn('name', [
                'academic_structure.update',
                'enrollments.update',
            ])->count() === 2);
        }

        $this->assertSame(
            0,
            $roles['Receptionist']->permissions()
                ->whereIn('name', ['academic_structure.update', 'enrollments.update'])
                ->count()
        );
    }

    private function createAcademicRecords(): array
    {
        $year = $this->createAcademicYear();
        $term = $this->createTerm($year);
        $class = $this->createClass();
        $stream = $this->createStream($class);
        $subject = $this->createSubject();
        $student = $this->createStudent();
        $enrollment = StudentEnrollment::create([
            'student_id' => $student->id,
            'academic_year_id' => $year->id,
            'class_id' => $class->id,
            'stream_id' => $stream->id,
            'status' => 'active',
            'enrollment_date' => '2026-01-01',
        ]);

        return compact('year', 'term', 'class', 'stream', 'subject', 'student', 'enrollment');
    }

    private function userWithPermissions(string $roleName, array $permissionNames): User
    {
        $user = User::factory()->create();
        $role = Role::create(['name' => $roleName]);

        foreach ($permissionNames as $permissionName) {
            $permission = Permission::firstOrCreate(['name' => $permissionName]);
            $role->permissions()->attach($permission->id);
        }

        $user->roles()->attach($role->id);

        return $user;
    }

    private function createAcademicYear(string $name = '2026'): AcademicYear
    {
        return AcademicYear::create([
            'name' => $name,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 'active',
        ]);
    }

    private function createTerm(AcademicYear $year): Term
    {
        return Term::create([
            'academic_year_id' => $year->id,
            'name' => 'Term One',
            'start_date' => '2026-01-01',
            'end_date' => '2026-04-30',
            'status' => 'active',
        ]);
    }

    private function createClass(string $name = 'Class A'): ClassRoom
    {
        return ClassRoom::create([
            'name' => $name,
            'description' => 'Test class',
            'status' => 'active',
        ]);
    }

    private function createStream(ClassRoom $class): Stream
    {
        return Stream::create([
            'class_id' => $class->id,
            'name' => 'Stream A',
            'description' => 'Test stream',
            'status' => 'active',
        ]);
    }

    private function createSubject(): Subject
    {
        return Subject::create([
            'code' => 'MAT-1',
            'name' => 'Mathematics',
            'description' => 'Test subject',
            'status' => 'active',
        ]);
    }

    private function createStudent(): Student
    {
        return Student::create([
            'admission_number' => 'ST-1',
            'first_name' => 'Test',
            'last_name' => 'Student',
            'date_of_birth' => '2010-01-01',
            'gender' => 'Male',
            'status' => 'active',
        ]);
    }

    private function yearPayload(string $name = '2026'): array
    {
        return [
            'name' => $name,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 'active',
        ];
    }

    private function termPayload(AcademicYear $year, string $name = 'Term One'): array
    {
        return [
            'academic_year_id' => $year->id,
            'name' => $name,
            'start_date' => '2026-01-01',
            'end_date' => '2026-04-30',
            'status' => 'active',
        ];
    }

    private function classPayload(string $name = 'Class A'): array
    {
        return [
            'name' => $name,
            'description' => 'Test class',
            'status' => 'active',
        ];
    }

    private function streamPayload(ClassRoom $class, string $name = 'Stream A'): array
    {
        return [
            'class_id' => $class->id,
            'name' => $name,
            'description' => 'Test stream',
            'status' => 'active',
        ];
    }

    private function subjectPayload(string $code = 'MAT-1', string $name = 'Mathematics'): array
    {
        return [
            'code' => $code,
            'name' => $name,
            'description' => 'Test subject',
            'status' => 'active',
        ];
    }

    private function enrollmentPayload(array $records, string $status = 'active'): array
    {
        return [
            'student_id' => $records['student']->id,
            'academic_year_id' => $records['year']->id,
            'class_id' => $records['class']->id,
            'stream_id' => $records['stream']->id,
            'status' => $status,
            'enrollment_date' => '2026-01-01',
        ];
    }
}
