<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\ClassRoom;
use App\Models\Exam;
use App\Models\ExamSubject;
use App\Models\Mark;
use App\Models\ParentModel;
use App\Models\Permission;
use App\Models\ResultPublication;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Subject;
use App\Models\Term;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentResultControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_student_results_and_print_routes(): void
    {
        $student = $this->createStudent('ST-001', 'Alex', 'Learner', 'alex@example.test');
        $exam = $this->createExam();

        $this->get(route('student-results.index'))
            ->assertRedirect(route('login'));

        $this->get(route('student-results.print', [
            'student' => $student,
            'exam_id' => $exam->id,
        ]))->assertRedirect(route('login'));
    }

    public function test_user_without_result_permissions_is_forbidden_from_both_routes(): void
    {
        $user = User::factory()->create();
        $student = $this->createStudent('ST-002', 'Jordan', 'Learner', 'jordan@example.test');
        $exam = $this->createExam();

        $this->actingAs($user)
            ->get(route('student-results.index'))
            ->assertForbidden();

        $this->get(route('student-results.print', [
            'student' => $student,
            'exam_id' => $exam->id,
        ]))->assertForbidden();
    }

    public function test_student_role_with_matching_email_sees_only_own_published_approved_results(): void
    {
        $user = $this->createPortalUser('Student', null, 'alex@example.test');
        $ownStudent = $this->createStudent('ST-003', 'Alex', 'Learner', 'alex@example.test');
        $otherStudent = $this->createStudent('ST-004', 'Taylor', 'Learner', 'taylor@example.test');
        [$publishedExam, $approvedMark] = $this->createPublishedResultFixtures(
            $ownStudent,
            $otherStudent
        );

        $response = $this->actingAs($user)->get(route('student-results.index', [
            'exam_id' => $publishedExam->id,
        ]));

        $response->assertOk();
        $this->assertSame(
            [$ownStudent->id],
            $response->viewData('students')->modelKeys()
        );
        $this->assertSame(
            [$publishedExam->id],
            $response->viewData('publishedExams')->pluck('exam_id')->all()
        );
        $this->assertSame(
            [$approvedMark->id],
            $response->viewData('resultRows')->modelKeys()
        );
    }

    public function test_student_role_without_a_matching_student_record_is_forbidden(): void
    {
        $user = $this->createPortalUser('Student', null, 'missing@example.test');

        $this->actingAs($user)
            ->get(route('student-results.index'))
            ->assertForbidden();
    }

    public function test_parent_with_broad_view_permission_sees_only_linked_students(): void
    {
        $user = $this->createPortalUser('Parent', 'reports.view', 'parent@example.test');
        $parent = ParentModel::query()->forceCreate([
            'first_name' => 'Casey',
            'last_name' => 'Guardian',
            'phone' => '555-0100',
            'email' => 'parent@example.test',
        ]);
        $linkedStudent = $this->createStudent('ST-005', 'Morgan', 'Learner', 'morgan@example.test');
        $unlinkedStudent = $this->createStudent('ST-006', 'Riley', 'Learner', 'riley@example.test');
        $linkedStudent->parents()->attach($parent->id, [
            'relationship' => 'Guardian',
            'is_primary' => true,
        ]);
        $exam = $this->createExam();

        $response = $this->actingAs($user)->get(route('student-results.index'));

        $response->assertOk();
        $this->assertSame(
            [$linkedStudent->id],
            $response->viewData('students')->modelKeys()
        );

        $this->get(route('student-results.print', [
            'student' => $unlinkedStudent,
            'exam_id' => $exam->id,
        ]))->assertNotFound();
    }

    public function test_student_cannot_search_for_or_print_another_students_results(): void
    {
        $user = $this->createPortalUser('Student', null, 'alex@example.test');
        $this->createStudent('ST-007', 'Alex', 'Learner', 'alex@example.test');
        $otherStudent = $this->createStudent('ST-008', 'Taylor', 'Learner', 'taylor@example.test');
        $exam = $this->createExam();

        $searchResponse = $this->actingAs($user)
            ->get(route('student-results.index', [
                'admission_number' => $otherStudent->admission_number,
            ]));

        $searchResponse->assertOk();
        $this->assertTrue($searchResponse->viewData('students')->isEmpty());

        $this->get(route('student-results.print', [
            'student' => $otherStudent,
            'exam_id' => $exam->id,
        ]))
            ->assertNotFound();
    }

    public function test_portal_account_with_ambiguous_email_mapping_is_forbidden(): void
    {
        $user = $this->createPortalUser('Student', null, 'alex@example.test');
        $this->createStudent('ST-009', 'Alex', 'Learner', 'alex@example.test');
        $this->createStudent('ST-010', 'Alexandra', 'Learner', 'alex@example.test');

        $this->actingAs($user)
            ->get(route('student-results.index'))
            ->assertForbidden();
    }

    public function test_parent_account_with_ambiguous_email_mapping_is_forbidden(): void
    {
        $user = $this->createPortalUser('Parent', null, 'parent@example.test');
        ParentModel::query()->forceCreate([
            'first_name' => 'Casey',
            'last_name' => 'Guardian',
            'phone' => '555-0100',
            'email' => 'parent@example.test',
        ]);
        ParentModel::query()->forceCreate([
            'first_name' => 'Jordan',
            'last_name' => 'Guardian',
            'phone' => '555-0101',
            'email' => 'parent@example.test',
        ]);

        $this->actingAs($user)
            ->get(route('student-results.index'))
            ->assertForbidden();
    }

    public function test_staff_with_results_permission_can_view_all_students(): void
    {
        $user = $this->createPortalUser('Results Staff', 'reports.view', 'staff@example.test');
        $firstStudent = $this->createStudent('ST-011', 'Alex', 'Learner', 'alex@example.test');
        $secondStudent = $this->createStudent('ST-012', 'Taylor', 'Learner', 'taylor@example.test');

        $response = $this->actingAs($user)->get(route('student-results.index'));

        $response->assertOk();
        $this->assertSame(
            [$firstStudent->id, $secondStudent->id],
            $response->viewData('students')->modelKeys()
        );
    }

    public function test_super_administrator_with_portal_and_broad_permissions_can_view_all_students(): void
    {
        $user = $this->createPortalUser('Super Administrator', [
            'students.view',
            'reports.view',
            'marks.view',
            'student.portal',
            'parent.portal',
        ], 'admin@school.local');
        $firstStudent = $this->createStudent('ST-013', 'Alex', 'Learner', 'alex@example.test');
        $secondStudent = $this->createStudent('ST-014', 'Taylor', 'Learner', 'taylor@example.test');

        $response = $this->actingAs($user)->get(route('student-results.index'));

        $response->assertOk();
        $this->assertSame(
            [$firstStudent->id, $secondStudent->id],
            $response->viewData('students')->modelKeys()
        );
    }

    private function createPortalUser(string $roleName, string|array|null $permissions, string $email): User
    {
        $user = User::factory()->create(['email' => $email]);
        $role = Role::create(['name' => $roleName]);

        foreach ((array) $permissions as $permissionName) {
            $permission = Permission::create(['name' => $permissionName]);
            $role->permissions()->attach($permission->id);
        }

        $user->roles()->attach($role->id);

        return $user;
    }

    private function createStudent(
        string $admissionNumber,
        string $firstName,
        string $lastName,
        string $email
    ): Student {
        return Student::create([
            'admission_number' => $admissionNumber,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
        ]);
    }

    private function createExam(): Exam
    {
        $academicYear = AcademicYear::create([
            'name' => '2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ]);
        $term = Term::create([
            'academic_year_id' => $academicYear->id,
            'name' => 'Term 1',
            'start_date' => '2026-01-01',
            'end_date' => '2026-04-30',
        ]);

        return Exam::create([
            'academic_year_id' => $academicYear->id,
            'term_id' => $term->id,
            'name' => 'Midterm',
            'exam_type' => 'Midterm',
        ]);
    }

    private function createPublishedResultFixtures(Student $student, Student $otherStudent): array
    {
        $publishedExam = $this->createExam();
        $classRoom = ClassRoom::create(['name' => 'Class 1']);

        StudentEnrollment::create([
            'student_id' => $student->id,
            'academic_year_id' => $publishedExam->academic_year_id,
            'class_id' => $classRoom->id,
            'status' => 'active',
        ]);

        $subject = Subject::create([
            'code' => 'MATH',
            'name' => 'Mathematics',
            'status' => 'active',
        ]);
        $examSubject = ExamSubject::create([
            'exam_id' => $publishedExam->id,
            'subject_id' => $subject->id,
            'class_id' => $classRoom->id,
            'max_marks' => 100,
        ]);
        $approvedMark = Mark::create([
            'exam_subject_id' => $examSubject->id,
            'student_id' => $student->id,
            'marks_obtained' => 85,
            'status' => 'Approved',
        ]);
        Mark::create([
            'exam_subject_id' => $examSubject->id,
            'student_id' => $otherStudent->id,
            'marks_obtained' => 92,
            'status' => 'Approved',
        ]);

        $unapprovedSubject = Subject::create([
            'code' => 'SCI',
            'name' => 'Science',
            'status' => 'active',
        ]);
        $unapprovedExamSubject = ExamSubject::create([
            'exam_id' => $publishedExam->id,
            'subject_id' => $unapprovedSubject->id,
            'class_id' => $classRoom->id,
            'max_marks' => 100,
        ]);
        Mark::create([
            'exam_subject_id' => $unapprovedExamSubject->id,
            'student_id' => $student->id,
            'marks_obtained' => 75,
            'status' => 'Pending',
        ]);

        ResultPublication::create([
            'exam_id' => $publishedExam->id,
            'class_id' => $classRoom->id,
            'status' => 'Published',
            'published_at' => now(),
        ]);

        $unpublishedExam = Exam::create([
            'academic_year_id' => $publishedExam->academic_year_id,
            'term_id' => $publishedExam->term_id,
            'name' => 'Unpublished Exam',
            'exam_type' => 'Midterm',
        ]);
        $unpublishedExamSubject = ExamSubject::create([
            'exam_id' => $unpublishedExam->id,
            'subject_id' => $subject->id,
            'class_id' => $classRoom->id,
            'max_marks' => 100,
        ]);
        Mark::create([
            'exam_subject_id' => $unpublishedExamSubject->id,
            'student_id' => $student->id,
            'marks_obtained' => 90,
            'status' => 'Approved',
        ]);
        ResultPublication::create([
            'exam_id' => $unpublishedExam->id,
            'class_id' => $classRoom->id,
            'status' => 'Draft',
        ]);

        return [$publishedExam, $approvedMark];
    }
}
