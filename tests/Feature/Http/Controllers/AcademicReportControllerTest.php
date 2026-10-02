<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\ClassRoom;
use App\Models\Exam;
use App\Models\ExamSubject;
use App\Models\Grade;
use App\Models\Mark;
use App\Models\ParentModel;
use App\Models\Permission;
use App\Models\ResultPublication;
use App\Models\Role;
use App\Models\Stream;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use App\Models\Term;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcademicReportControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_report_card_html_print_and_pdf_routes(): void
    {
        $student = $this->createStudent('ST-001', 'Alex', 'Learner', 'alex@example.test');
        [$year, $term] = $this->createAcademicContext();
        $query = ['academic_year_id' => $year->id, 'term_id' => $term->id];

        $this->get(route('academic-reports.show', ['student' => $student] + $query))
            ->assertRedirect(route('login'));
        $this->get(route('academic-reports.print', ['student' => $student] + $query))
            ->assertRedirect(route('login'));
        $this->get(route('academic-reports.pdf', ['student' => $student] + $query))
            ->assertRedirect(route('login'));
    }

    public function test_user_without_academic_report_permission_is_forbidden(): void
    {
        $user = User::factory()->create();
        $student = $this->createStudent('ST-002', 'Jordan', 'Learner', 'jordan@example.test');

        $this->actingAs($user)
            ->get(route('academic-reports.show', $student))
            ->assertForbidden();
    }

    public function test_authorized_staff_sees_approved_results_grade_rank_and_attendance(): void
    {
        $user = $this->userWithPermissions('Academic Officer', [
            'reports.academic.view',
            'reports.academic.export',
        ]);
        [$year, $term, $classRoom] = $this->createAcademicContext();
        $student = $this->createStudent('ST-003', 'Alex', 'Learner', 'alex@example.test');
        $peer = $this->createStudent('ST-004', 'Taylor', 'Learner', 'taylor@example.test');
        $this->enroll($student, $year, $classRoom);
        $this->enroll($peer, $year, $classRoom);
        $this->createGrade('A', 80, 100);
        $this->createGrade('C', 0, 79.99);
        $exam = $this->createExam($year, $term, 'Midterm');
        $subject = $this->createSubject('MATH', 'Mathematics');
        $examSubject = $this->createExamSubject($exam, $classRoom, $subject);
        $this->createMark($student, $examSubject, 80);
        $this->createMark($peer, $examSubject, 90);
        $this->createMark($student, $this->createExamSubject(
            $this->createExam($year, $term, 'Final'),
            $classRoom,
            $this->createSubject('SCI', 'Science')
        ), 70, 'Pending');
        $this->createAttendance($student, $year, $term, $classRoom, '2026-02-01', 'Present');
        $this->createAttendance($student, $year, $term, $classRoom, '2026-02-02', 'Late');
        $this->createAttendance($student, $year, $term, $classRoom, '2026-02-03', 'Absent');

        $response = $this->actingAs($user)->get(route('academic-reports.show', $student));

        $response->assertOk()
            ->assertSee('Alex Learner')
            ->assertSee('Mathematics')
            ->assertSee('80.00 / 100.00')
            ->assertSee('Class rank:</strong> 2 / 2', false)
            ->assertSee('Attendance rate:</strong> 66.67%', false)
            ->assertDontSee('Science');
        $this->assertSame(80.0, $response->viewData('percentage'));
        $this->assertSame('A', $response->viewData('overallGrade'));
    }

    public function test_student_can_view_own_report_but_not_another_students_report(): void
    {
        $ownStudent = $this->createStudent('ST-005', 'Morgan', 'Learner', 'morgan@example.test');
        $otherStudent = $this->createStudent('ST-006', 'Riley', 'Learner', 'riley@example.test');
        [$year, , $classRoom] = $this->createAcademicContext();
        $this->enroll($ownStudent, $year, $classRoom);
        $this->enroll($otherStudent, $year, $classRoom);
        $user = $this->userWithPermissions('Student', ['reports.academic.view'], $ownStudent->email);

        $this->actingAs($user)
            ->get(route('academic-reports.show', $ownStudent))
            ->assertOk()
            ->assertSee('Morgan Learner');

        $this->get(route('academic-reports.show', $otherStudent))
            ->assertNotFound();
    }

    public function test_parent_can_view_only_a_linked_students_report(): void
    {
        $linkedStudent = $this->createStudent('ST-007', 'Jamie', 'Learner', 'jamie@example.test');
        $unlinkedStudent = $this->createStudent('ST-008', 'Casey', 'Learner', 'casey@example.test');
        [$year, , $classRoom] = $this->createAcademicContext();
        $this->enroll($linkedStudent, $year, $classRoom);
        $this->enroll($unlinkedStudent, $year, $classRoom);
        $parent = ParentModel::query()->forceCreate([
            'first_name' => 'Pat',
            'last_name' => 'Guardian',
            'phone' => '555-0100',
            'email' => 'parent@example.test',
        ]);
        $linkedStudent->parents()->attach($parent->id, [
            'relationship' => 'Guardian',
            'is_primary' => true,
        ]);
        $user = $this->userWithPermissions('Parent', ['reports.academic.view'], $parent->email);

        $this->actingAs($user)
            ->get(route('academic-reports.show', $linkedStudent))
            ->assertOk()
            ->assertSee('Jamie Learner');

        $this->get(route('academic-reports.show', $unlinkedStudent))
            ->assertNotFound();
    }

    public function test_student_report_only_includes_published_approved_marks(): void
    {
        $student = $this->createStudent('ST-015', 'Quinn', 'Learner', 'quinn@example.test');
        [$year, $term, $classRoom] = $this->createAcademicContext();
        $this->enroll($student, $year, $classRoom);
        $exam = $this->createExam($year, $term, 'Term assessment');
        $subject = $this->createSubject('HIS', 'History');
        $examSubject = $this->createExamSubject($exam, $classRoom, $subject);
        $this->createMark($student, $examSubject, 88);
        $user = $this->userWithPermissions('Student', ['reports.academic.view'], $student->email);
        $route = route('academic-reports.show', [
            'student' => $student,
            'academic_year_id' => $year->id,
            'term_id' => $term->id,
        ]);

        $this->actingAs($user)
            ->get($route)
            ->assertOk()
            ->assertDontSee('History');

        ResultPublication::create([
            'exam_id' => $exam->id,
            'class_id' => $classRoom->id,
            'status' => 'Published',
            'published_at' => now(),
        ]);

        $this->get($route)
            ->assertOk()
            ->assertSee('History')
            ->assertSee('88.00 / 100.00');
    }

    public function test_teacher_can_view_assigned_enrollment_but_not_other_classes(): void
    {
        [$year, , $assignedClass, $assignedStream] = $this->createAcademicContext(withStream: true);
        $otherClass = ClassRoom::create(['name' => 'Class B']);
        $student = $this->createStudent('ST-009', 'Robin', 'Learner', 'robin@example.test');
        $this->enroll($student, $year, $assignedClass, $assignedStream);
        $teacher = Teacher::create([
            'employee_number' => 'T-001',
            'first_name' => 'Taylor',
            'last_name' => 'Teacher',
            'email' => 'teacher@example.test',
        ]);
        $subject = $this->createSubject('ENG', 'English');
        TeacherAssignment::create([
            'teacher_id' => $teacher->id,
            'class_id' => $assignedClass->id,
            'stream_id' => $assignedStream->id,
            'subject_id' => $subject->id,
            'academic_year_id' => $year->id,
            'status' => 'active',
        ]);
        $user = $this->userWithPermissions('Teacher', [
            'reports.academic.view',
            'reports.academic.export',
        ], 'teacher@example.test');
        $user->forceFill(['teacher_id' => $teacher->id])->save();
        $this->enroll($this->createStudent('ST-010', 'Other', 'Learner', 'other@example.test'), $year, $otherClass);

        $this->actingAs($user)
            ->get(route('academic-reports.show', $student))
            ->assertOk()
            ->assertSee('Robin Learner');
        $this->get(route('academic-reports.show', Student::where('admission_number', 'ST-010')->firstOrFail()))
            ->assertNotFound();
    }

    public function test_term_from_another_academic_year_is_rejected(): void
    {
        $user = $this->userWithPermissions('Academic Officer', ['reports.academic.view']);
        [$year, $term, $classRoom] = $this->createAcademicContext();
        $student = $this->createStudent('ST-011', 'Avery', 'Learner', 'avery@example.test');
        $this->enroll($student, $year, $classRoom);
        $otherYear = AcademicYear::create([
            'name' => '2027',
            'start_date' => '2027-01-01',
            'end_date' => '2027-12-31',
            'status' => 'active',
        ]);
        $otherTerm = Term::create([
            'academic_year_id' => $otherYear->id,
            'name' => 'Term 1',
            'start_date' => '2027-01-01',
            'end_date' => '2027-04-30',
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->get(route('academic-reports.show', [
                'student' => $student,
                'academic_year_id' => $year->id,
                'term_id' => $otherTerm->id,
            ]))
            ->assertRedirect()
            ->assertSessionHasErrors('term_id');
    }

    public function test_print_route_returns_a_standalone_print_view(): void
    {
        $user = $this->userWithPermissions('Academic Officer', [
            'reports.academic.view',
            'reports.academic.export',
        ]);
        [$year, $term, $classRoom] = $this->createAcademicContext();
        $student = $this->createStudent('ST-012', 'Sky', 'Learner', 'sky@example.test');
        $this->enroll($student, $year, $classRoom);

        $this->actingAs($user)
            ->get(route('academic-reports.print', [
                'student' => $student,
                'academic_year_id' => $year->id,
                'term_id' => $term->id,
            ]))
            ->assertOk()
            ->assertSee('max-width: 900px', false)
            ->assertSee('Student Academic Report Card');
    }

    public function test_user_without_export_permission_is_forbidden_from_pdf_route(): void
    {
        $student = $this->createStudent('ST-013', 'Drew', 'Learner', 'drew@example.test');
        $viewUser = $this->userWithPermissions('Parent', ['reports.academic.view'], 'parent@example.test');
        ParentModel::query()->forceCreate([
            'first_name' => 'Parent',
            'last_name' => 'Account',
            'phone' => '555-0101',
            'email' => 'parent@example.test',
        ]);

        $this->actingAs($viewUser)
            ->get(route('academic-reports.pdf', $student))
            ->assertForbidden();
    }

    public function test_pdf_route_returns_service_unavailable_when_dompdf_is_missing(): void
    {
        if (class_exists(Pdf::class)) {
            $this->markTestSkipped('DomPDF is installed, so the missing-package fallback is not applicable.');
        }

        $student = $this->createStudent('ST-016', 'Drew', 'Learner', 'drew@example.test');
        $exportUser = $this->userWithPermissions('Academic Officer', [
            'reports.academic.view',
            'reports.academic.export',
        ]);
        [$year, $term, $classRoom] = $this->createAcademicContext();
        $this->enroll($student, $year, $classRoom);

        $response = $this->actingAs($exportUser)
            ->get(route('academic-reports.pdf', [
                'student' => $student,
                'academic_year_id' => $year->id,
                'term_id' => $term->id,
            ]))
            ->assertServiceUnavailable();
        $this->assertStringContainsString('composer require barryvdh/laravel-dompdf', $response->getContent());
    }

    public function test_missing_marks_render_a_clear_empty_state(): void
    {
        $user = $this->userWithPermissions('Academic Officer', ['reports.academic.view']);
        [$year, $term, $classRoom] = $this->createAcademicContext();
        $student = $this->createStudent('ST-014', 'Sam', 'Learner', 'sam@example.test');
        $this->enroll($student, $year, $classRoom);

        $response = $this->actingAs($user)
            ->get(route('academic-reports.show', [
                'student' => $student,
                'academic_year_id' => $year->id,
                'term_id' => $term->id,
            ]))
            ->assertOk()
            ->assertSee('No approved marks are available')
            ->assertSee('Class rank:</strong> N/A', false);
        $this->assertSame('N/A', $response->viewData('overallGrade'));
    }

    private function userWithPermissions(string $roleName, array $permissions, ?string $email = null): User
    {
        $user = User::factory()->create([
            'email' => $email ?? strtolower(str_replace(' ', '.', $roleName)).'@example.test',
        ]);
        $role = Role::create(['name' => $roleName]);

        foreach ($permissions as $permissionName) {
            $permission = Permission::firstOrCreate(['name' => $permissionName]);
            $role->permissions()->attach($permission->id);
        }

        $user->roles()->attach($role->id);

        return $user;
    }

    private function createStudent(string $admissionNumber, string $firstName, string $lastName, string $email): Student
    {
        return Student::create([
            'admission_number' => $admissionNumber,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
        ]);
    }

    private function createAcademicContext(bool $withStream = false): array
    {
        $year = AcademicYear::create([
            'name' => '2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'is_current' => true,
            'status' => 'active',
        ]);
        $term = Term::create([
            'academic_year_id' => $year->id,
            'name' => 'Term 1',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 'active',
        ]);
        $classRoom = ClassRoom::create(['name' => 'Class A']);
        $stream = $withStream
            ? Stream::create(['class_id' => $classRoom->id, 'name' => 'Stream A'])
            : null;

        return [$year, $term, $classRoom, $stream];
    }

    private function enroll(
        Student $student,
        AcademicYear $year,
        ClassRoom $classRoom,
        ?Stream $stream = null
    ): StudentEnrollment {
        return StudentEnrollment::create([
            'student_id' => $student->id,
            'academic_year_id' => $year->id,
            'class_id' => $classRoom->id,
            'stream_id' => $stream?->id,
            'status' => 'active',
        ]);
    }

    private function createExam(AcademicYear $year, Term $term, string $name): Exam
    {
        return Exam::create([
            'academic_year_id' => $year->id,
            'term_id' => $term->id,
            'name' => $name,
            'exam_type' => 'Assessment',
            'status' => 'active',
        ]);
    }

    private function createSubject(string $code, string $name): Subject
    {
        return Subject::create([
            'code' => $code,
            'name' => $name,
            'status' => 'active',
        ]);
    }

    private function createExamSubject(Exam $exam, ClassRoom $classRoom, Subject $subject): ExamSubject
    {
        return ExamSubject::create([
            'exam_id' => $exam->id,
            'subject_id' => $subject->id,
            'class_id' => $classRoom->id,
            'max_marks' => 100,
        ]);
    }

    private function createMark(
        Student $student,
        ExamSubject $examSubject,
        int $marks,
        string $status = 'Approved'
    ): Mark {
        return Mark::create([
            'exam_subject_id' => $examSubject->id,
            'student_id' => $student->id,
            'marks_obtained' => $marks,
            'status' => $status,
        ]);
    }

    private function createGrade(string $code, float $minimum, float $maximum): Grade
    {
        return Grade::create([
            'name' => $code.' grade',
            'code' => $code,
            'min_percentage' => $minimum,
            'max_percentage' => $maximum,
            'status' => 'active',
        ]);
    }

    private function createAttendance(
        Student $student,
        AcademicYear $year,
        Term $term,
        ClassRoom $classRoom,
        string $date,
        string $status
    ): Attendance {
        return Attendance::create([
            'student_id' => $student->id,
            'academic_year_id' => $year->id,
            'term_id' => $term->id,
            'class_id' => $classRoom->id,
            'attendance_date' => $date,
            'status' => $status,
        ]);
    }
}
