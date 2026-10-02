<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\ClassRoom;
use App\Models\Exam;
use App\Models\ExamSubject;
use App\Models\Mark;
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

class MarkControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_enter_marks_for_their_assignment_and_active_enrollment(): void
    {
        $context = $this->createContext();
        $teacher = $this->createTeacherWithAssignment($context);
        $student = $this->createEnrolledStudent($context);

        $this->actingAs($teacher['user'])
            ->post(route('marks.store'), $this->markPayload($context, $student))
            ->assertRedirect(route('marks.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('marks', [
            'exam_subject_id' => $context['examSubject']->id,
            'student_id' => $student->id,
            'marks_obtained' => 85,
            'grade' => 'A',
            'status' => 'Pending',
            'entered_by' => $teacher['user']->id,
            'approved_by' => null,
            'approved_at' => null,
        ]);
    }

    public function test_teacher_cannot_enter_marks_for_another_teachers_assignment(): void
    {
        $context = $this->createContext();
        $this->createTeacherWithAssignment($context, 'Assigned');
        $otherTeacher = $this->createTeacherUser('Other');
        $student = $this->createEnrolledStudent($context);

        $this->actingAs($otherTeacher['user'])
            ->post(route('marks.store'), $this->markPayload($context, $student))
            ->assertForbidden();

        $this->assertDatabaseMissing('marks', [
            'exam_subject_id' => $context['examSubject']->id,
            'student_id' => $student->id,
        ]);
    }

    public function test_teacher_cannot_enter_marks_for_a_student_without_matching_active_enrollment(): void
    {
        $context = $this->createContext();
        $teacher = $this->createTeacherWithAssignment($context);
        $student = $this->createStudent();

        $this->actingAs($teacher['user'])
            ->from(route('marks.create'))
            ->post(route('marks.store'), $this->markPayload($context, $student))
            ->assertSessionHasErrors('students');

        $this->assertDatabaseMissing('marks', [
            'exam_subject_id' => $context['examSubject']->id,
            'student_id' => $student->id,
        ]);
    }

    public function test_teacher_cannot_enter_marks_for_mismatched_class_or_academic_year_enrollment(): void
    {
        $context = $this->createContext();
        $teacher = $this->createTeacherWithAssignment($context);

        $otherClass = ClassRoom::create(['name' => 'Class 2']);
        $wrongClassStudent = $this->createStudent();
        $this->createEnrollment($wrongClassStudent, $context, classId: $otherClass->id);
        $otherStream = Stream::create([
            'class_id' => $otherClass->id,
            'name' => 'Stream B',
        ]);

        $otherYear = AcademicYear::create([
            'name' => '2027',
            'start_date' => '2027-01-01',
            'end_date' => '2027-12-31',
        ]);
        $wrongYearStudent = $this->createStudent();
        $this->createEnrollment($wrongYearStudent, $context, academicYearId: $otherYear->id);
        $wrongStreamStudent = $this->createStudent();
        $this->createEnrollment($wrongStreamStudent, $context, streamId: $otherStream->id);

        $this->actingAs($teacher['user'])
            ->from(route('marks.create'))
            ->post(route('marks.store'), [
                'exam_subject_id' => $context['examSubject']->id,
                'students' => [
                    $wrongClassStudent->id => ['marks_obtained' => 70],
                    $wrongYearStudent->id => ['marks_obtained' => 75],
                    $wrongStreamStudent->id => ['marks_obtained' => 80],
                ],
            ])
            ->assertSessionHasErrors('students');

        $this->assertDatabaseMissing('marks', [
            'exam_subject_id' => $context['examSubject']->id,
        ]);
    }

    public function test_authorized_non_teacher_can_enter_marks_without_a_teacher_record(): void
    {
        $context = $this->createContext();
        $staff = $this->userWithPermissions('Academic Officer', ['marks.create']);
        $student = $this->createEnrolledStudent($context);

        $this->assertNull($staff->teacher);

        $this->actingAs($staff)
            ->post(route('marks.store'), $this->markPayload($context, $student))
            ->assertRedirect(route('marks.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('marks', [
            'exam_subject_id' => $context['examSubject']->id,
            'student_id' => $student->id,
            'status' => 'Pending',
        ]);
    }

    public function test_approved_mark_cannot_be_overwritten_or_have_approval_metadata_cleared(): void
    {
        $context = $this->createContext();
        $staff = $this->userWithPermissions('Academic Officer', ['marks.create']);
        $student = $this->createEnrolledStudent($context);
        $approver = User::factory()->create();
        $approvedAt = now()->subDay()->startOfSecond();
        $mark = Mark::create([
            'exam_subject_id' => $context['examSubject']->id,
            'student_id' => $student->id,
            'marks_obtained' => 91,
            'grade' => 'A',
            'status' => 'Approved',
            'entered_by' => $staff->id,
            'approved_by' => $approver->id,
            'approved_at' => $approvedAt,
        ]);

        $this->actingAs($staff)
            ->from(route('marks.create'))
            ->post(route('marks.store'), $this->markPayload($context, $student, 50))
            ->assertSessionHasErrors('students');

        $this->assertDatabaseHas('marks', [
            'id' => $mark->id,
            'marks_obtained' => 91,
            'grade' => 'A',
            'status' => 'Approved',
            'entered_by' => $staff->id,
            'approved_by' => $approver->id,
            'approved_at' => $approvedAt->toDateTimeString(),
        ]);
    }

    public function test_mark_entry_still_enforces_maximum_marks_and_approval_workflow_remains_available(): void
    {
        $context = $this->createContext();
        $staff = $this->userWithPermissions('Academic Officer', ['marks.create', 'marks.approve']);
        $student = $this->createEnrolledStudent($context);

        $this->actingAs($staff)
            ->from(route('marks.create'))
            ->post(route('marks.store'), $this->markPayload($context, $student, 101))
            ->assertSessionHasErrors('students');

        $this->post(route('marks.store'), $this->markPayload($context, $student, 80))
            ->assertRedirect(route('marks.index'));

        $mark = Mark::query()->where('student_id', $student->id)->firstOrFail();
        $this->post(route('marks.approve', $mark))
            ->assertRedirect(route('marks.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('marks', [
            'id' => $mark->id,
            'status' => 'Approved',
            'approved_by' => $staff->id,
        ]);
    }

    public function test_teacher_create_form_only_lists_their_assigned_exam_subjects_and_students(): void
    {
        $context = $this->createContext();
        $teacher = $this->createTeacherWithAssignment($context);
        $student = $this->createEnrolledStudent($context);

        $response = $this->actingAs($teacher['user'])
            ->get(route('marks.create', ['exam_subject_id' => $context['examSubject']->id]));

        $response->assertOk();
        $this->assertSame(
            [$context['examSubject']->id],
            $response->viewData('examSubjects')->modelKeys()
        );
        $this->assertSame([$student->id], $response->viewData('students')->modelKeys());
    }

    private function createContext(): array
    {
        $classRoom = ClassRoom::create(['name' => 'Class 1']);
        $stream = Stream::create([
            'class_id' => $classRoom->id,
            'name' => 'Stream A',
        ]);
        $academicYear = AcademicYear::create([
            'name' => '2026',
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
            'code' => 'MATH',
            'name' => 'Mathematics',
        ]);
        $exam = Exam::create([
            'academic_year_id' => $academicYear->id,
            'term_id' => $term->id,
            'name' => 'Midterm',
            'exam_type' => 'Midterm',
        ]);
        $examSubject = ExamSubject::create([
            'exam_id' => $exam->id,
            'subject_id' => $subject->id,
            'class_id' => $classRoom->id,
            'max_marks' => 100,
        ]);

        return compact('classRoom', 'stream', 'academicYear', 'term', 'subject', 'exam', 'examSubject');
    }

    private function createTeacherWithAssignment(array $context, string $name = 'Assigned'): array
    {
        $teacherUser = $this->createTeacherUser($name);

        TeacherAssignment::create([
            'teacher_id' => $teacherUser['teacher']->id,
            'class_id' => $context['classRoom']->id,
            'stream_id' => $context['stream']->id,
            'subject_id' => $context['subject']->id,
            'academic_year_id' => $context['academicYear']->id,
            'status' => 'active',
        ]);

        return $teacherUser;
    }

    private function createTeacherUser(string $name): array
    {
        $teacher = Teacher::create([
            'employee_number' => 'EMP-'.$name,
            'first_name' => $name,
            'last_name' => 'Teacher',
        ]);
        $user = User::factory()->create(['teacher_id' => $teacher->id]);
        $role = Role::firstOrCreate(['name' => 'Teacher']);
        $permission = Permission::firstOrCreate(['name' => 'marks.create']);
        $role->permissions()->syncWithoutDetaching([$permission->id]);
        $user->roles()->attach($role->id);

        return compact('teacher', 'user');
    }

    private function userWithPermissions(string $roleName, array $permissions): User
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

    private function createStudent(): Student
    {
        return Student::create([
            'admission_number' => 'ST-'.(Student::count() + 1),
            'first_name' => 'Test',
            'last_name' => 'Student',
        ]);
    }

    private function createEnrolledStudent(array $context): Student
    {
        $student = $this->createStudent();
        $this->createEnrollment($student, $context);

        return $student;
    }

    private function createEnrollment(
        Student $student,
        array $context,
        ?int $classId = null,
        ?int $academicYearId = null,
        ?int $streamId = null
    ): void {
        StudentEnrollment::create([
            'student_id' => $student->id,
            'academic_year_id' => $academicYearId ?? $context['academicYear']->id,
            'class_id' => $classId ?? $context['classRoom']->id,
            'stream_id' => $streamId ?? $context['stream']->id,
            'status' => 'active',
        ]);
    }

    private function markPayload(
        array $context,
        Student $student,
        int $marks = 85
    ): array {
        return [
            'exam_subject_id' => $context['examSubject']->id,
            'students' => [
                $student->id => [
                    'marks_obtained' => $marks,
                    'remarks' => 'Test mark',
                ],
            ],
        ];
    }
}
