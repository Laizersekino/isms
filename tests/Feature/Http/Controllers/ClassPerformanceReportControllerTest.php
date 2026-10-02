<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\ClassRoom;
use App\Models\Exam;
use App\Models\ExamSubject;
use App\Models\Grade;
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

class ClassPerformanceReportControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_html_pdf_and_print_routes(): void
    {
        $class = ClassRoom::create(['name' => 'Form 1A']);
        [$year, $term] = $this->createAcademicPeriod();
        $filters = ['academic_year_id' => $year->id, 'term_id' => $term->id];

        $this->get(route('reports.academic.class-performance', ['class' => $class] + $filters))
            ->assertRedirect(route('login'));
        $this->get(route('reports.academic.class-performance.pdf', ['class' => $class] + $filters))
            ->assertRedirect(route('login'));
        $this->get(route('reports.academic.class-performance.print', ['class' => $class] + $filters))
            ->assertRedirect(route('login'));
    }

    public function test_user_without_academic_report_view_permission_is_forbidden(): void
    {
        $user = User::factory()->create();
        $class = ClassRoom::create(['name' => 'Form 1A']);
        [$year, $term] = $this->createAcademicPeriod();

        $this->actingAs($user)
            ->get(route('reports.academic.class-performance', [
                'class' => $class,
                'academic_year_id' => $year->id,
                'term_id' => $term->id,
            ]))
            ->assertForbidden();
    }

    public function test_academic_staff_can_view_class_performance_report(): void
    {
        $class = ClassRoom::create(['name' => 'Form 1A']);
        [$year, $term] = $this->createAcademicPeriod();

        foreach (['Super Administrator', 'School Administrator', 'Principal', 'Academic Officer'] as $role) {
            $user = $this->userWithPermissions($role, ['reports.academic.view']);
            $this->actingAs($user)
                ->get(route('reports.academic.class-performance', [
                    'class' => $class,
                    'academic_year_id' => $year->id,
                    'term_id' => $term->id,
                ]))
                ->assertOk()
                ->assertSee('Form 1A');
        }
    }

    public function test_parent_and_student_are_forbidden_even_when_they_have_view_permission(): void
    {
        $class = ClassRoom::create(['name' => 'Form 1A']);
        [$year, $term] = $this->createAcademicPeriod();

        foreach (['Parent', 'Student'] as $role) {
            $user = $this->userWithPermissions($role, ['reports.academic.view']);
            $this->actingAs($user)
                ->get(route('reports.academic.class-performance', [
                    'class' => $class,
                    'academic_year_id' => $year->id,
                    'term_id' => $term->id,
                ]))
                ->assertForbidden();
        }
    }

    public function test_teacher_can_view_a_class_with_an_active_assignment_for_selected_year(): void
    {
        [$year, $term, $class, $stream] = $this->createAcademicContext();
        $teacher = $this->createTeacher('teacher@example.test');
        $this->assignTeacher($teacher, $year, $class, $stream);
        $user = $this->userWithPermissions('Teacher', ['reports.academic.view'], 'teacher@example.test');
        $user->forceFill(['teacher_id' => $teacher->id])->save();

        $this->actingAs($user)
            ->get(route('reports.academic.class-performance', [
                'class' => $class,
                'academic_year_id' => $year->id,
                'term_id' => $term->id,
            ]))
            ->assertOk()
            ->assertSee('Form 1A');
    }

    public function test_teacher_is_forbidden_from_unassigned_class(): void
    {
        [$year, $term, $assignedClass] = $this->createAcademicContext();
        $unassignedClass = ClassRoom::create(['name' => 'Form 2A']);
        $teacher = $this->createTeacher('teacher@example.test');
        $user = $this->userWithPermissions('Teacher', ['reports.academic.view'], 'teacher@example.test');
        $user->forceFill(['teacher_id' => $teacher->id])->save();

        $this->actingAs($user)
            ->get(route('reports.academic.class-performance', [
                'class' => $unassignedClass,
                'academic_year_id' => $year->id,
                'term_id' => $term->id,
            ]))
            ->assertForbidden();
    }

    public function test_report_calculates_class_subject_top_performer_and_grade_metrics(): void
    {
        $user = $this->userWithPermissions('Academic Officer', ['reports.academic.view']);
        [$year, $term, $class] = $this->createAcademicContext();
        $first = $this->createStudent('ST-001', 'Alex', 'Able');
        $second = $this->createStudent('ST-002', 'Bea', 'Bright');
        $withoutMarks = $this->createStudent('ST-003', 'Chris', 'Clever');
        $this->enroll($first, $year, $class);
        $this->enroll($second, $year, $class);
        $this->enroll($withoutMarks, $year, $class);
        $this->createGrades();

        $math = $this->createSubject('MAT', 'Mathematics');
        $english = $this->createSubject('ENG', 'English');
        $mathExam = $this->createExam($year, $term, 'Midterm');
        $englishExam = $this->createExam($year, $term, 'Final');
        $mathAssessment = $this->createExamSubject($mathExam, $class, $math);
        $englishAssessment = $this->createExamSubject($englishExam, $class, $english);
        $this->createMark($first, $mathAssessment, 80);
        $this->createMark($first, $englishAssessment, 60);
        $this->createMark($second, $mathAssessment, 90);
        $this->createMark($second, $englishAssessment, 40);
        $this->createMark($first, $this->createExamSubject(
            $this->createExam($year, $term, 'Pending Exam'),
            $class,
            $this->createSubject('SCI', 'Science')
        ), 95, 'Pending');

        $response = $this->actingAs($user)->get(route('reports.academic.class-performance', [
            'class' => $class,
            'academic_year_id' => $year->id,
            'term_id' => $term->id,
        ]));

        $response->assertOk()
            ->assertSee('Form 1A')
            ->assertSee('Mathematics')
            ->assertSee('85.00%')
            ->assertSee('English')
            ->assertSee('50.00%')
            ->assertSee('Alex Able')
            ->assertSee('Bea Bright')
            ->assertSee('Grade distribution')
            ->assertDontSee('Chris Clever')
            ->assertDontSee('Science');
        $this->assertSame(67.5, $response->viewData('classAverage'));
        $this->assertSame(100.0, $response->viewData('passRate'));
        $this->assertSame(3, $response->viewData('totalStudents'));
        $this->assertSame(2, $response->viewData('studentsWithMarks'));
        $this->assertSame(1, $response->viewData('topPerformers')->first()['rank']);
        $this->assertSame('B', $response->viewData('topPerformers')->first()['grade']);
        $this->assertSame([
            ['code' => 'A', 'count' => 0, 'percentage' => 0.0],
            ['code' => 'B', 'count' => 1, 'percentage' => 50.0],
            ['code' => 'C', 'count' => 1, 'percentage' => 50.0],
            ['code' => 'D', 'count' => 0, 'percentage' => 0.0],
            ['code' => 'F', 'count' => 0, 'percentage' => 0.0],
        ], $response->viewData('gradeDistribution')->all());
    }

    public function test_query_filters_select_only_marks_for_requested_year_and_term(): void
    {
        $user = $this->userWithPermissions('Academic Officer', ['reports.academic.view']);
        [$year, $term, $class] = $this->createAcademicContext();
        $otherTerm = Term::create([
            'academic_year_id' => $year->id,
            'name' => 'Term 2',
            'start_date' => '2026-05-01',
            'end_date' => '2026-08-31',
            'status' => 'active',
        ]);
        $student = $this->createStudent('ST-004', 'Drew', 'Determined');
        $this->enroll($student, $year, $class);
        $subject = $this->createSubject('MAT', 'Mathematics');
        $this->createMark($student, $this->createExamSubject(
            $this->createExam($year, $term, 'Term 1 Exam'),
            $class,
            $subject
        ), 80);
        $this->createMark($student, $this->createExamSubject(
            $this->createExam($year, $otherTerm, 'Term 2 Exam'),
            $class,
            $subject
        ), 20);

        $response = $this->actingAs($user)->get(route('reports.academic.class-performance', [
            'class' => $class,
            'academic_year_id' => $year->id,
            'term_id' => $term->id,
        ]));

        $this->assertSame(80.0, $response->viewData('classAverage'));
    }

    public function test_students_with_equal_total_marks_share_the_same_rank(): void
    {
        $user = $this->userWithPermissions('Academic Officer', ['reports.academic.view']);
        [$year, $term, $class] = $this->createAcademicContext();
        $first = $this->createStudent('ST-006', 'Equal', 'First');
        $second = $this->createStudent('ST-007', 'Equal', 'Second');
        $third = $this->createStudent('ST-008', 'Lower', 'Score');
        foreach ([$first, $second, $third] as $student) {
            $this->enroll($student, $year, $class);
        }

        $subjectOne = $this->createSubject('MAT', 'Mathematics');
        $subjectTwo = $this->createSubject('ENG', 'English');
        $exam = $this->createExam($year, $term, 'Term exam');
        $assessmentOne = $this->createExamSubject($exam, $class, $subjectOne);
        $assessmentTwo = $this->createExamSubject($exam, $class, $subjectTwo);
        $this->createMark($first, $assessmentOne, 80);
        $this->createMark($first, $assessmentTwo, 20);
        $this->createMark($second, $assessmentOne, 70);
        $this->createMark($second, $assessmentTwo, 30);
        $this->createMark($third, $assessmentOne, 50);
        $this->createMark($third, $assessmentTwo, 40);

        $response = $this->actingAs($user)->get(route('reports.academic.class-performance', [
            'class' => $class,
            'academic_year_id' => $year->id,
            'term_id' => $term->id,
        ]));

        $rows = collect($response->viewData('students'))->keyBy('id');
        $this->assertSame(1, $rows[$first->id]['rank']);
        $this->assertSame(1, $rows[$second->id]['rank']);
        $this->assertSame(3, $rows[$third->id]['rank']);
    }

    public function test_term_must_belong_to_selected_academic_year(): void
    {
        $user = $this->userWithPermissions('Academic Officer', ['reports.academic.view']);
        [$year, $term, $class] = $this->createAcademicContext();
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
            ->get(route('reports.academic.class-performance', [
                'class' => $class,
                'academic_year_id' => $year->id,
                'term_id' => $otherTerm->id,
            ]))
            ->assertRedirect()
            ->assertSessionHasErrors('term_id');
    }

    public function test_empty_class_and_class_without_marks_render_clear_empty_states(): void
    {
        $user = $this->userWithPermissions('Academic Officer', ['reports.academic.view']);
        [$year, $term, $class] = $this->createAcademicContext();
        $filters = ['academic_year_id' => $year->id, 'term_id' => $term->id];

        $this->actingAs($user)
            ->get(route('reports.academic.class-performance', ['class' => $class] + $filters))
            ->assertOk()
            ->assertSee('No students enrolled in this class');

        $student = $this->createStudent('ST-005', 'Empty', 'Results');
        $this->enroll($student, $year, $class);

        $this->get(route('reports.academic.class-performance', ['class' => $class] + $filters))
            ->assertOk()
            ->assertSee('No results available for this class');
    }

    public function test_print_route_returns_standalone_view(): void
    {
        $user = $this->userWithPermissions('Principal', ['reports.academic.export']);
        [$year, $term, $class] = $this->createAcademicContext();

        $this->actingAs($user)
            ->get(route('reports.academic.class-performance.print', [
                'class' => $class,
                'academic_year_id' => $year->id,
                'term_id' => $term->id,
            ]))
            ->assertOk()
            ->assertSee('max-width: 1000px', false)
            ->assertSee('Class Performance Report');
    }

    public function test_pdf_route_returns_pdf_content_with_valid_signature(): void
    {
        $user = $this->userWithPermissions('Academic Officer', ['reports.academic.export']);
        [$year, $term, $class] = $this->createAcademicContext();

        $response = $this->actingAs($user)
            ->get(route('reports.academic.class-performance.pdf', [
                'class' => $class,
                'academic_year_id' => $year->id,
                'term_id' => $term->id,
            ]))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertSame('%PDF', substr($response->getContent(), 0, 4));
    }

    private function userWithPermissions(string $roleName, array $permissions, ?string $email = null): User
    {
        $user = User::factory()->create([
            'email' => $email ?? strtolower(str_replace(' ', '.', $roleName)).'@example.test',
        ]);
        $role = Role::create(['name' => $roleName]);
        foreach ($permissions as $name) {
            $permission = Permission::firstOrCreate(['name' => $name]);
            $role->permissions()->attach($permission->id);
        }
        $user->roles()->attach($role->id);

        return $user;
    }

    private function createAcademicContext(): array
    {
        [$year, $term] = $this->createAcademicPeriod();
        $class = ClassRoom::create(['name' => 'Form 1A']);
        $stream = Stream::create(['class_id' => $class->id, 'name' => 'Stream A']);

        return [$year, $term, $class, $stream];
    }

    private function createAcademicPeriod(): array
    {
        $year = AcademicYear::create([
            'name' => '2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 'active',
        ]);
        $term = Term::create([
            'academic_year_id' => $year->id,
            'name' => 'Term 1',
            'start_date' => '2026-01-01',
            'end_date' => '2026-04-30',
            'status' => 'active',
        ]);

        return [$year, $term];
    }

    private function createStudent(string $admissionNumber, string $firstName, string $lastName): Student
    {
        return Student::create([
            'admission_number' => $admissionNumber,
            'first_name' => $firstName,
            'last_name' => $lastName,
        ]);
    }

    private function enroll(Student $student, AcademicYear $year, ClassRoom $class): StudentEnrollment
    {
        return StudentEnrollment::create([
            'student_id' => $student->id,
            'academic_year_id' => $year->id,
            'class_id' => $class->id,
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
        return Subject::create(['code' => $code, 'name' => $name, 'status' => 'active']);
    }

    private function createExamSubject(Exam $exam, ClassRoom $class, Subject $subject): ExamSubject
    {
        return ExamSubject::create([
            'exam_id' => $exam->id,
            'subject_id' => $subject->id,
            'class_id' => $class->id,
            'max_marks' => 100,
        ]);
    }

    private function createMark(Student $student, ExamSubject $examSubject, int $score, string $status = 'Approved'): Mark
    {
        return Mark::create([
            'student_id' => $student->id,
            'exam_subject_id' => $examSubject->id,
            'marks_obtained' => $score,
            'status' => $status,
        ]);
    }

    private function createGrades(): void
    {
        foreach ([
            ['A', 80, 100],
            ['B', 70, 79.99],
            ['C', 60, 69.99],
            ['D', 50, 59.99],
            ['F', 0, 49.99],
        ] as [$code, $minimum, $maximum]) {
            Grade::create([
                'name' => $code.' grade',
                'code' => $code,
                'min_percentage' => $minimum,
                'max_percentage' => $maximum,
                'status' => 'active',
            ]);
        }
    }

    private function createTeacher(string $email): Teacher
    {
        return Teacher::create([
            'employee_number' => 'T-'.uniqid(),
            'first_name' => 'Taylor',
            'last_name' => 'Teacher',
            'email' => $email,
        ]);
    }

    private function assignTeacher(Teacher $teacher, AcademicYear $year, ClassRoom $class, Stream $stream): void
    {
        TeacherAssignment::create([
            'teacher_id' => $teacher->id,
            'class_id' => $class->id,
            'stream_id' => $stream->id,
            'subject_id' => $this->createSubject('GEN', 'General')->id,
            'academic_year_id' => $year->id,
            'status' => 'active',
        ]);
    }
}
