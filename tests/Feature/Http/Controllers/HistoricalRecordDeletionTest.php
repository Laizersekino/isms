<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\ClassRoom;
use App\Models\Exam;
use App\Models\ExamSubject;
use App\Models\Mark;
use App\Models\Permission;
use App\Models\ResultPublication;
use App\Models\Role;
use App\Models\Stream;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Subject;
use App\Models\Term;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class HistoricalRecordDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_with_enrollment_cannot_be_deleted(): void
    {
        $this->actingAs($this->userWithDeletePermissions());
        $context = $this->createContext();
        $student = $this->createStudent();
        $enrollment = $this->createEnrollment($context, $student);

        $response = $this->delete(route('students.destroy', $student));

        $this->assertBlockedDelete($response, 'enrollment, attendance, examination, or other historical records');
        $this->assertModelExists($student);
        $this->assertModelExists($enrollment);
    }

    public function test_enrollment_with_attendance_cannot_be_deleted(): void
    {
        $this->actingAs($this->userWithDeletePermissions());
        $context = $this->createContext();
        $student = $this->createStudent();
        $enrollment = $this->createEnrollment($context, $student);
        $attendance = $this->createAttendance($context, $student);

        $response = $this->delete(route('enrollments.destroy', $enrollment));

        $this->assertBlockedDelete($response);
        $this->assertModelExists($enrollment);
        $this->assertModelExists($attendance);
    }

    public function test_enrollment_with_marks_cannot_be_deleted(): void
    {
        $this->actingAs($this->userWithDeletePermissions());
        $context = $this->createContext();
        $student = $this->createStudent();
        $enrollment = $this->createEnrollment($context, $student);
        $examSubject = $this->createExamSubject($context);
        $mark = $this->createMark($student, $examSubject);

        $response = $this->delete(route('enrollments.destroy', $enrollment));

        $this->assertBlockedDelete($response);
        $this->assertModelExists($enrollment);
        $this->assertModelExists($mark);
    }

    public function test_enrollment_with_published_class_results_cannot_be_deleted(): void
    {
        $this->actingAs($this->userWithDeletePermissions());
        $context = $this->createContext();
        $student = $this->createStudent();
        $enrollment = $this->createEnrollment($context, $student);
        $exam = $this->createExam($context['year'], $context['term']);
        $publication = ResultPublication::create([
            'exam_id' => $exam->id,
            'class_id' => $context['class']->id,
            'status' => 'published',
        ]);

        $response = $this->delete(route('enrollments.destroy', $enrollment));

        $this->assertBlockedDelete($response);
        $this->assertModelExists($enrollment);
        $this->assertModelExists($publication);
    }

    public function test_student_with_attendance_cannot_be_deleted(): void
    {
        $this->actingAs($this->userWithDeletePermissions());
        $context = $this->createContext();
        $student = $this->createStudent();
        $attendance = $this->createAttendance($context, $student);

        $response = $this->delete(route('students.destroy', $student));

        $this->assertBlockedDelete($response);
        $this->assertModelExists($student);
        $this->assertModelExists($attendance);
    }

    public function test_student_with_marks_cannot_be_deleted(): void
    {
        $this->actingAs($this->userWithDeletePermissions());
        $context = $this->createContext();
        $student = $this->createStudent();
        $examSubject = $this->createExamSubject($context);
        $mark = $this->createMark($student, $examSubject);

        $response = $this->delete(route('students.destroy', $student));

        $this->assertBlockedDelete($response);
        $this->assertModelExists($student);
        $this->assertModelExists($mark);
    }

    public function test_academic_year_with_dependent_terms_cannot_be_deleted(): void
    {
        $this->actingAs($this->userWithDeletePermissions());
        $year = $this->createAcademicYear();
        $term = $this->createTerm($year);

        $response = $this->delete(route('academic-years.destroy', $year));

        $this->assertBlockedDelete($response);
        $this->assertModelExists($year);
        $this->assertModelExists($term);
    }

    public function test_term_with_dependent_examination_cannot_be_deleted(): void
    {
        $this->actingAs($this->userWithDeletePermissions());
        $context = $this->createContext();
        $exam = $this->createExam($context['year'], $context['term']);

        $response = $this->delete(route('terms.destroy', $context['term']));

        $this->assertBlockedDelete($response);
        $this->assertModelExists($context['term']);
        $this->assertModelExists($exam);
    }

    public function test_class_with_enrollment_cannot_be_deleted(): void
    {
        $this->actingAs($this->userWithDeletePermissions());
        $context = $this->createContext();
        $student = $this->createStudent();
        $enrollment = $this->createEnrollment($context, $student);

        $response = $this->delete(route('classes.destroy', $context['class']));

        $this->assertBlockedDelete($response);
        $this->assertModelExists($context['class']);
        $this->assertModelExists($enrollment);
    }

    public function test_stream_with_historical_attendance_cannot_be_deleted(): void
    {
        $this->actingAs($this->userWithDeletePermissions());
        $context = $this->createContext();
        $student = $this->createStudent();
        $attendance = $this->createAttendance($context, $student);

        $response = $this->delete(route('streams.destroy', $context['stream']));

        $this->assertBlockedDelete($response);
        $this->assertModelExists($context['stream']);
        $this->assertModelExists($attendance);
    }

    public function test_exam_with_subject_marks_and_publication_cannot_be_deleted(): void
    {
        $this->actingAs($this->userWithDeletePermissions());
        $context = $this->createContext();
        $exam = $this->createExam($context['year'], $context['term']);
        $examSubject = $this->createExamSubject($context, $exam);
        $student = $this->createStudent();
        $mark = $this->createMark($student, $examSubject);
        $publication = ResultPublication::create([
            'exam_id' => $exam->id,
            'class_id' => $context['class']->id,
            'status' => 'published',
        ]);

        $response = $this->delete(route('exams.destroy', $exam));

        $this->assertBlockedDelete($response);
        $this->assertModelExists($exam);
        $this->assertModelExists($examSubject);
        $this->assertModelExists($mark);
        $this->assertModelExists($publication);
    }

    public function test_exam_subject_with_marks_cannot_be_deleted(): void
    {
        $this->actingAs($this->userWithDeletePermissions());
        $context = $this->createContext();
        $examSubject = $this->createExamSubject($context);
        $student = $this->createStudent();
        $mark = $this->createMark($student, $examSubject);

        $response = $this->delete(route('exam-subjects.destroy', $examSubject));

        $this->assertBlockedDelete($response);
        $this->assertModelExists($examSubject);
        $this->assertModelExists($mark);
    }

    public function test_exam_subject_for_a_published_result_cannot_be_deleted(): void
    {
        $this->actingAs($this->userWithDeletePermissions());
        $context = $this->createContext();
        $examSubject = $this->createExamSubject($context);
        $publication = ResultPublication::create([
            'exam_id' => $examSubject->exam_id,
            'class_id' => $examSubject->class_id,
            'status' => 'published',
        ]);

        $response = $this->delete(route('exam-subjects.destroy', $examSubject));

        $this->assertBlockedDelete($response);
        $this->assertModelExists($examSubject);
        $this->assertModelExists($publication);
    }

    public function test_subject_with_exam_results_cannot_be_deleted(): void
    {
        $this->actingAs($this->userWithDeletePermissions());
        $context = $this->createContext();
        $examSubject = $this->createExamSubject($context);
        $student = $this->createStudent();
        $mark = $this->createMark($student, $examSubject);

        $response = $this->delete(route('subjects.destroy', $context['subject']));

        $this->assertBlockedDelete($response);
        $this->assertModelExists($context['subject']);
        $this->assertModelExists($examSubject);
        $this->assertModelExists($mark);
    }

    public function test_records_without_dependencies_can_still_be_deleted(): void
    {
        $this->actingAs($this->userWithDeletePermissions());
        $student = $this->createStudent();
        $year = $this->createAcademicYear();
        $term = $this->createTerm($year);
        $class = $this->createClass('Independent Class');
        $stream = $this->createStream($class, 'Independent Stream');
        $exam = $this->createExam($year, $term);
        $enrolledStudent = $this->createStudent();
        $enrollment = $this->createEnrollment(
            compact('year', 'class', 'stream'),
            $enrolledStudent
        );
        $subject = $this->createSubject('BIO-1');
        $examSubject = $this->createExamSubject(
            compact('year', 'term'),
            $exam,
            $class,
            $subject
        );

        $this->delete(route('students.destroy', $student))
            ->assertRedirect(route('students.index'));
        $this->assertDatabaseMissing('students', ['id' => $student->id]);

        $this->delete(route('enrollments.destroy', $enrollment))
            ->assertRedirect(route('enrollments.index'));
        $this->assertDatabaseMissing('student_enrollments', ['id' => $enrollment->id]);

        $this->delete(route('streams.destroy', $stream))
            ->assertRedirect(route('streams.index'));
        $this->assertDatabaseMissing('streams', ['id' => $stream->id]);

        $this->delete(route('exam-subjects.destroy', $examSubject))
            ->assertRedirect(route('exam-subjects.index'));
        $this->assertDatabaseMissing('exam_subjects', ['id' => $examSubject->id]);

        $this->delete(route('classes.destroy', $class))
            ->assertRedirect(route('classes.index'));
        $this->assertDatabaseMissing('classes', ['id' => $class->id]);

        $this->delete(route('exams.destroy', $exam))
            ->assertRedirect(route('exams.index'));
        $this->assertDatabaseMissing('exams', ['id' => $exam->id]);

        $this->delete(route('terms.destroy', $term))
            ->assertRedirect(route('terms.index'));
        $this->assertDatabaseMissing('terms', ['id' => $term->id]);

        $this->delete(route('academic-years.destroy', $year))
            ->assertRedirect(route('academic-years.index'));
        $this->assertDatabaseMissing('academic_years', ['id' => $year->id]);

        $this->delete(route('subjects.destroy', $subject))
            ->assertRedirect(route('subjects.index'));
        $this->assertDatabaseMissing('subjects', ['id' => $subject->id]);
    }

    private function assertBlockedDelete(TestResponse $response, ?string $messageFragment = null): void
    {
        $response->assertSessionHasErrors('delete');

        $message = session('errors')->first('delete');
        $this->assertStringContainsString('cannot be deleted', $message);

        if ($messageFragment !== null) {
            $this->assertStringContainsString($messageFragment, $message);
        }
    }

    private function userWithDeletePermissions(): User
    {
        $user = User::factory()->create();
        $role = Role::create(['name' => 'School Administrator']);

        foreach (['students.delete', 'marks.delete'] as $permissionName) {
            $permission = Permission::firstOrCreate(['name' => $permissionName]);
            $role->permissions()->attach($permission->id);
        }

        $user->roles()->attach($role->id);

        return $user;
    }

    private function createContext(): array
    {
        $year = $this->createAcademicYear();
        $term = $this->createTerm($year);
        $class = $this->createClass();
        $stream = $this->createStream($class);
        $subject = $this->createSubject();

        return compact('year', 'term', 'class', 'stream', 'subject');
    }

    private function createAcademicYear(): AcademicYear
    {
        return AcademicYear::create([
            'name' => '2026',
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

    private function createStream(ClassRoom $class, string $name = 'Stream A'): Stream
    {
        return Stream::create([
            'class_id' => $class->id,
            'name' => $name,
            'description' => 'Test stream',
            'status' => 'active',
        ]);
    }

    private function createSubject(string $code = 'MAT-1'): Subject
    {
        return Subject::create([
            'code' => $code,
            'name' => $code === 'MAT-1' ? 'Mathematics' : 'Biology',
            'description' => 'Test subject',
            'status' => 'active',
        ]);
    }

    private function createStudent(): Student
    {
        return Student::create([
            'admission_number' => 'ST-'.(Student::query()->count() + 1),
            'first_name' => 'Test',
            'last_name' => 'Student',
            'date_of_birth' => '2010-01-01',
            'gender' => 'Male',
            'status' => 'active',
        ]);
    }

    private function createEnrollment(array $context, Student $student): StudentEnrollment
    {
        return StudentEnrollment::create([
            'student_id' => $student->id,
            'academic_year_id' => $context['year']->id,
            'class_id' => $context['class']->id,
            'stream_id' => $context['stream']->id,
            'status' => 'active',
            'enrollment_date' => '2026-01-01',
        ]);
    }

    private function createAttendance(array $context, Student $student): Attendance
    {
        return Attendance::create([
            'student_id' => $student->id,
            'academic_year_id' => $context['year']->id,
            'term_id' => $context['term']->id,
            'class_id' => $context['class']->id,
            'stream_id' => $context['stream']->id,
            'attendance_date' => '2026-02-10',
            'status' => 'Present',
        ]);
    }

    private function createExam(AcademicYear $year, Term $term): Exam
    {
        return Exam::create([
            'academic_year_id' => $year->id,
            'term_id' => $term->id,
            'name' => 'Midterm',
            'exam_type' => 'Midterm',
            'start_date' => '2026-02-01',
            'end_date' => '2026-02-10',
            'status' => 'Active',
        ]);
    }

    private function createExamSubject(
        array $context,
        ?Exam $exam = null,
        ?ClassRoom $class = null,
        ?Subject $subject = null
    ): ExamSubject {
        $exam ??= $this->createExam($context['year'], $context['term']);

        return ExamSubject::create([
            'exam_id' => $exam->id,
            'subject_id' => ($subject ?? $context['subject'])->id,
            'class_id' => ($class ?? $context['class'])->id,
            'max_marks' => 100,
        ]);
    }

    private function createMark(Student $student, ExamSubject $examSubject): Mark
    {
        return Mark::create([
            'exam_subject_id' => $examSubject->id,
            'student_id' => $student->id,
            'marks_obtained' => 75,
            'grade' => 'B',
            'status' => 'draft',
        ]);
    }
}
