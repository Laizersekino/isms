<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\ClassRoom;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Stream;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use App\Models\Term;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceControllerTest extends TestCase
{
    use RefreshDatabase;

    private const ALL_ATTENDANCE_PERMISSIONS = [
        'attendance.view',
        'attendance.create',
        'attendance.update',
    ];

    public function test_guest_is_redirected_from_all_attendance_endpoints(): void
    {
        $context = $this->createContext();
        $student = $this->createStudentInContext($context);
        $attendance = $this->createAttendance($context, $student);

        $this->get(route('attendance.index'))->assertRedirect(route('login'));
        $this->get(route('attendance.create'))->assertRedirect(route('login'));
        $this->post(route('attendance.store'))->assertRedirect(route('login'));
        $this->get(route('attendance.edit', $attendance))->assertRedirect(route('login'));
        $this->put(route('attendance.update', $attendance))->assertRedirect(route('login'));
        $this->delete(route('attendance.destroy', $attendance))->assertRedirect(route('login'));
    }

    public function test_student_without_attendance_permissions_is_forbidden_from_all_endpoints(): void
    {
        $this->assertRoleWithoutAttendancePermissionsIsForbidden('Student');
    }

    public function test_parent_without_attendance_permissions_is_forbidden_from_all_endpoints(): void
    {
        $this->assertRoleWithoutAttendancePermissionsIsForbidden('Parent');
    }

    public function test_principal_can_view_but_cannot_create_update_or_delete_attendance(): void
    {
        $user = $this->userWithRolePermissions('Principal', ['attendance.view']);
        $context = $this->createContext();
        $student = $this->createStudentInContext($context);
        $attendance = $this->createAttendance($context, $student);

        $this->actingAs($user)->get(route('attendance.index'))->assertOk();
        $this->get(route('attendance.create'))->assertForbidden();
        $this->post(route('attendance.store'))->assertForbidden();
        $this->get(route('attendance.edit', $attendance))->assertForbidden();
        $this->put(route('attendance.update', $attendance))->assertForbidden();
        $this->delete(route('attendance.destroy', $attendance))->assertForbidden();
        $this->assertModelExists($attendance);
    }

    public function test_teacher_can_record_and_update_attendance_in_an_active_assignment(): void
    {
        $context = $this->createContext();
        $teacher = $this->createTeacherUser($context, 'Teacher');
        $student = $this->createStudentInContext($context);

        $this->actingAs($teacher['user'])
            ->get(route('attendance.create', ['assignment_id' => $teacher['assignment']->id]))
            ->assertOk()
            ->assertViewHas('students', fn ($students) => $students->modelKeys() === [$student->id]);

        $payload = $this->storePayload($context, $student);
        $payload['assignment_id'] = $teacher['assignment']->id;

        $this->post(route('attendance.store'), $payload)
            ->assertRedirect(route('attendance.index'));

        $attendance = Attendance::query()->where('student_id', $student->id)->firstOrFail();
        $this->assertSame('Present', $attendance->status);
        $this->assertSame($teacher['user']->id, $attendance->recorded_by);

        $this->put(route('attendance.update', $attendance), $this->updatePayload($context, $student, 'Late'))
            ->assertRedirect(route('attendance.index'));

        $this->assertDatabaseHas('attendance', [
            'id' => $attendance->id,
            'status' => 'Late',
            'recorded_by' => $teacher['user']->id,
        ]);
    }

    public function test_teacher_cannot_access_or_change_attendance_outside_their_active_assignment(): void
    {
        $firstContext = $this->createContext('Class A', 'Stream A', '2026');
        $secondContext = $this->createContext('Class B', 'Stream B', '2027');
        $firstTeacher = $this->createTeacherUser($firstContext, 'Teacher One');
        $this->createTeacherUser($secondContext, 'Teacher Two');
        $student = $this->createStudentInContext($secondContext);
        $attendance = $this->createAttendance($secondContext, $student);

        $this->actingAs($firstTeacher['user'])
            ->get(route('attendance.edit', $attendance))
            ->assertForbidden();

        $this->put(route('attendance.update', $attendance), $this->updatePayload($secondContext, $student))
            ->assertForbidden();

        $this->delete(route('attendance.destroy', $attendance))
            ->assertForbidden();

        $this->assertModelExists($attendance);
    }

    public function test_teacher_cannot_submit_another_assignment_or_mismatched_assignment_context(): void
    {
        $firstContext = $this->createContext('Class A', 'Stream A', '2026');
        $secondContext = $this->createContext('Class B', 'Stream B', '2027');
        $firstTeacher = $this->createTeacherUser($firstContext, 'Teacher One');
        $secondTeacher = $this->createTeacherUser($secondContext, 'Teacher Two');
        $student = $this->createStudentInContext($secondContext);

        $foreignAssignmentPayload = $this->storePayload($secondContext, $student);
        $foreignAssignmentPayload['assignment_id'] = $secondTeacher['assignment']->id;

        $this->actingAs($firstTeacher['user'])
            ->post(route('attendance.store'), $foreignAssignmentPayload)
            ->assertForbidden();

        $mismatchedContextPayload = $this->storePayload($firstContext, $this->createStudentInContext($firstContext));
        $mismatchedContextPayload['assignment_id'] = $secondTeacher['assignment']->id;

        $this->post(route('attendance.store'), $mismatchedContextPayload)
            ->assertForbidden();
    }

    public function test_teacher_attendance_list_contains_only_records_in_active_assignment_scope(): void
    {
        $firstContext = $this->createContext('Class A', 'Stream A', '2026');
        $secondContext = $this->createContext('Class B', 'Stream B', '2027');
        $firstTeacher = $this->createTeacherUser($firstContext, 'Teacher One');
        $this->createTeacherUser($secondContext, 'Teacher Two');
        $firstStudent = $this->createStudentInContext($firstContext);
        $secondStudent = $this->createStudentInContext($secondContext);
        $firstAttendance = $this->createAttendance($firstContext, $firstStudent);
        $this->createAttendance($secondContext, $secondStudent);

        $response = $this->actingAs($firstTeacher['user'])->get(route('attendance.index'));

        $response->assertOk();
        $this->assertSame(
            [$firstAttendance->id],
            $response->viewData('attendance')->modelKeys()
        );
    }

    public function test_academic_officer_and_school_administrator_can_view_update_and_delete_without_teacher_records(): void
    {
        foreach (['Academic Officer', 'School Administrator'] as $roleName) {
            $user = $this->userWithRolePermissions($roleName, [
                'attendance.view',
                'attendance.update',
            ]);
            $context = $this->createContext($roleName.' Class', $roleName.' Stream', $roleName.' Year');
            $student = $this->createStudentInContext($context);
            $attendance = $this->createAttendance($context, $student);

            $this->actingAs($user)->get(route('attendance.index'))->assertOk();
            $this->get(route('attendance.create'))->assertForbidden();
            $this->get(route('attendance.edit', $attendance))->assertOk();

            $this->put(route('attendance.update', $attendance), $this->updatePayload($context, $student, 'Late'))
                ->assertRedirect(route('attendance.index'));

            $this->delete(route('attendance.destroy', $attendance))
                ->assertRedirect(route('attendance.index'));

            $this->assertDatabaseMissing('attendance', ['id' => $attendance->id]);
        }
    }

    public function test_super_administrator_can_view_create_update_and_delete_without_a_teacher_record(): void
    {
        $user = $this->userWithRolePermissions('Super Administrator', self::ALL_ATTENDANCE_PERMISSIONS);
        $context = $this->createContext();
        $teacher = $this->createTeacherUser($context, 'Teacher');
        $student = $this->createStudentInContext($context);
        $attendance = $this->createAttendance($context, $student);

        $this->actingAs($user)->get(route('attendance.index'))->assertOk();
        $this->get(route('attendance.create', ['assignment_id' => $teacher['assignment']->id]))
            ->assertOk();

        $this->post(route('attendance.store'), $this->storePayload($context, $student))
            ->assertRedirect(route('attendance.index'));

        $this->patch(route('attendance.update', $attendance), $this->updatePayload($context, $student, 'Excused'))
            ->assertRedirect(route('attendance.index'));

        $this->assertDatabaseHas('attendance', [
            'id' => $attendance->id,
            'status' => 'Excused',
        ]);

        $this->delete(route('attendance.destroy', $attendance))
            ->assertRedirect(route('attendance.index'));
        $this->assertDatabaseMissing('attendance', ['id' => $attendance->id]);
    }

    public function test_teacher_cannot_record_attendance_for_a_student_outside_the_active_enrollment_context(): void
    {
        $context = $this->createContext();
        $teacher = $this->createTeacherUser($context, 'Teacher');
        $enrolledStudent = $this->createStudentInContext($context);
        $otherStudent = Student::create([
            'admission_number' => 'ST-OUTSIDE',
            'first_name' => 'Out',
            'last_name' => 'OfClass',
        ]);

        $response = $this->actingAs($teacher['user'])
            ->post(route('attendance.store'), [
                ...$this->storePayload($context, $enrolledStudent, $otherStudent),
                'assignment_id' => $teacher['assignment']->id,
            ]);

        $response->assertSessionHasErrors('students');
        $this->assertDatabaseMissing('attendance', ['student_id' => $enrolledStudent->id]);
        $this->assertDatabaseMissing('attendance', ['student_id' => $otherStudent->id]);
    }

    public function test_update_rejects_student_not_enrolled_in_submitted_context(): void
    {
        $user = $this->userWithRolePermissions('School Administrator', ['attendance.update']);
        $context = $this->createContext();
        $otherContext = $this->createContext('Other Class', 'Other Stream', '2027');
        $student = $this->createStudentInContext($context);
        $otherStudent = $this->createStudentInContext($otherContext);
        $attendance = $this->createAttendance($context, $student);

        $response = $this->actingAs($user)->put(
            route('attendance.update', $attendance),
            $this->updatePayload($context, $otherStudent)
        );

        $response->assertSessionHasErrors('student_id');
        $this->assertDatabaseHas('attendance', [
            'id' => $attendance->id,
            'student_id' => $student->id,
        ]);
    }

    public function test_update_rejects_a_term_from_another_academic_year(): void
    {
        $user = $this->userWithRolePermissions('School Administrator', ['attendance.update']);
        $context = $this->createContext();
        $otherContext = $this->createContext('Other Class', 'Other Stream', '2027');
        $student = $this->createStudentInContext($context);
        $attendance = $this->createAttendance($context, $student);
        $payload = $this->updatePayload($context, $student);
        $payload['term_id'] = $otherContext['term']->id;

        $response = $this->actingAs($user)
            ->put(route('attendance.update', $attendance), $payload);

        $response->assertSessionHasErrors('student_id');
        $this->assertDatabaseHas('attendance', [
            'id' => $attendance->id,
            'term_id' => $context['term']->id,
        ]);
    }

    public function test_update_rejects_a_stream_that_does_not_belong_to_the_submitted_class(): void
    {
        $user = $this->userWithRolePermissions('School Administrator', ['attendance.update']);
        $context = $this->createContext();
        $otherContext = $this->createContext('Other Class', 'Other Stream', '2027');
        $student = $this->createStudentInContext($context);
        $attendance = $this->createAttendance($context, $student);
        $payload = $this->updatePayload($context, $student);
        $payload['stream_id'] = $otherContext['stream']->id;

        $response = $this->actingAs($user)
            ->put(route('attendance.update', $attendance), $payload);

        $response->assertSessionHasErrors('stream_id');
        $this->assertDatabaseHas('attendance', [
            'id' => $attendance->id,
            'stream_id' => $context['stream']->id,
        ]);
    }

    public function test_user_without_attendance_update_cannot_delete_attendance(): void
    {
        $user = $this->userWithRolePermissions('Attendance Viewer', ['attendance.view', 'attendance.create']);
        $context = $this->createContext();
        $student = $this->createStudentInContext($context);
        $attendance = $this->createAttendance($context, $student);

        $this->actingAs($user)
            ->delete(route('attendance.destroy', $attendance))
            ->assertForbidden();

        $this->assertModelExists($attendance);
    }

    public function test_attendance_index_and_valid_bulk_recording_remain_available_to_authorized_teachers(): void
    {
        $context = $this->createContext();
        $teacher = $this->createTeacherUser($context, 'Teacher');
        $student = $this->createStudentInContext($context);

        $this->actingAs($teacher['user'])
            ->post(route('attendance.store'), [
                ...$this->storePayload($context, $student),
                'assignment_id' => $teacher['assignment']->id,
            ])
            ->assertRedirect(route('attendance.index'));

        $response = $this->get(route('attendance.index'));
        $response->assertOk();
        $this->assertSame(
            [$student->id],
            $response->viewData('attendance')->pluck('student_id')->all()
        );
    }

    private function assertRoleWithoutAttendancePermissionsIsForbidden(string $roleName): void
    {
        $user = $this->userWithRolePermissions($roleName, []);
        $context = $this->createContext();
        $student = $this->createStudentInContext($context);
        $attendance = $this->createAttendance($context, $student);

        $this->actingAs($user)->get(route('attendance.index'))->assertForbidden();
        $this->get(route('attendance.create'))->assertForbidden();
        $this->post(route('attendance.store'))->assertForbidden();
        $this->get(route('attendance.edit', $attendance))->assertForbidden();
        $this->put(route('attendance.update', $attendance))->assertForbidden();
        $this->delete(route('attendance.destroy', $attendance))->assertForbidden();
    }

    private function userWithRolePermissions(string $roleName, array $permissions): User
    {
        $user = User::factory()->create();
        $role = Role::create(['name' => $roleName]);

        foreach ($permissions as $permissionName) {
            $permission = Permission::firstOrCreate(['name' => $permissionName]);
            $role->permissions()->attach($permission->id);
        }

        $user->roles()->attach($role->id);

        return $user;
    }

    private function createContext(
        string $className = 'Class 1',
        string $streamName = 'Stream A',
        string $yearName = '2026'
    ): array {
        $classRoom = ClassRoom::create(['name' => $className]);
        $stream = Stream::create([
            'class_id' => $classRoom->id,
            'name' => $streamName,
        ]);
        $academicYear = AcademicYear::create([
            'name' => $yearName,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ]);
        $term = Term::create([
            'academic_year_id' => $academicYear->id,
            'name' => 'Term 1',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ]);
        $subject = Subject::create([
            'code' => 'SUB-'.$className.'-'.$yearName,
            'name' => 'Mathematics',
        ]);

        return compact('classRoom', 'stream', 'academicYear', 'term', 'subject');
    }

    private function createTeacherUser(array $context, string $name): array
    {
        $teacher = Teacher::create([
            'employee_number' => 'EMP-'.$name.'-'.$context['academicYear']->name,
            'first_name' => $name,
            'last_name' => 'Teacher',
        ]);
        $user = User::factory()->create(['teacher_id' => $teacher->id]);
        $role = Role::firstOrCreate(['name' => 'Teacher']);
        foreach (self::ALL_ATTENDANCE_PERMISSIONS as $permissionName) {
            $permission = Permission::firstOrCreate(['name' => $permissionName]);
            $role->permissions()->syncWithoutDetaching([$permission->id]);
        }
        $user->roles()->attach($role->id);

        $assignment = TeacherAssignment::create([
            'teacher_id' => $teacher->id,
            'class_id' => $context['classRoom']->id,
            'stream_id' => $context['stream']->id,
            'subject_id' => $context['subject']->id,
            'academic_year_id' => $context['academicYear']->id,
            'status' => 'active',
        ]);

        return compact('teacher', 'user', 'assignment');
    }

    private function createStudentInContext(array $context, ?string $admissionNumber = null): Student
    {
        $student = Student::create([
            'admission_number' => $admissionNumber ?? 'ST-'.(Student::count() + 1),
            'first_name' => 'Test',
            'last_name' => 'Student',
        ]);
        StudentEnrollment::create([
            'student_id' => $student->id,
            'academic_year_id' => $context['academicYear']->id,
            'class_id' => $context['classRoom']->id,
            'stream_id' => $context['stream']->id,
            'status' => 'active',
            'enrollment_date' => '2026-01-01',
        ]);

        return $student;
    }

    private function createAttendance(array $context, Student $student): Attendance
    {
        return Attendance::create([
            'student_id' => $student->id,
            'academic_year_id' => $context['academicYear']->id,
            'term_id' => $context['term']->id,
            'class_id' => $context['classRoom']->id,
            'stream_id' => $context['stream']->id,
            'attendance_date' => '2026-02-10',
            'status' => 'Present',
        ]);
    }

    private function storePayload(array $context, Student ...$students): array
    {
        $studentRows = [];
        foreach ($students as $student) {
            $studentRows[$student->id] = [
                'status' => 'Present',
                'remarks' => '',
            ];
        }

        return [
            'academic_year_id' => $context['academicYear']->id,
            'class_id' => $context['classRoom']->id,
            'stream_id' => $context['stream']->id,
            'attendance_date' => '2026-02-10',
            'students' => $studentRows,
        ];
    }

    private function updatePayload(array $context, Student $student, string $status = 'Present'): array
    {
        return [
            'student_id' => $student->id,
            'academic_year_id' => $context['academicYear']->id,
            'term_id' => $context['term']->id,
            'class_id' => $context['classRoom']->id,
            'stream_id' => $context['stream']->id,
            'attendance_date' => '2026-02-10',
            'status' => $status,
            'remarks' => 'Updated',
        ];
    }
}
