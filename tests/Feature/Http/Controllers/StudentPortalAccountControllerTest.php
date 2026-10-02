<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\ClassRoom;
use App\Models\Exam;
use App\Models\ExamSubject;
use App\Models\Mark;
use App\Models\Permission;
use App\Models\ResultPublication;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Subject;
use App\Models\Term;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class StudentPortalAccountControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_create_a_student_account_and_send_a_password_setup_link(): void
    {
        Notification::fake([ResetPassword::class]);
        Log::spy();

        $admin = $this->createAdminUser();
        $student = $this->createStudent('ST-100', 'Alex', 'Learner', 'alex@example.test');

        $this->actingAs($admin)
            ->post(route('students.portal-account.store', $student))
            ->assertRedirect()
            ->assertSessionHas('success');

        $user = User::query()->where('email', $student->email)->firstOrFail();

        $this->assertTrue($user->hasRole('Student'));
        $this->assertNotSame('password', $user->password);
        Notification::assertSentTo($user, ResetPassword::class);
        Log::shouldHaveReceived('info')
            ->with('Student portal account action completed.', [
                'action' => 'created',
                'actor_user_id' => $admin->id,
                'student_id' => $student->id,
                'portal_user_id' => $user->id,
            ])
            ->once();
    }

    public function test_password_setup_link_can_be_used_to_set_the_student_password(): void
    {
        Notification::fake([ResetPassword::class]);

        $admin = $this->createAdminUser();
        $student = $this->createStudent('ST-101', 'Jordan', 'Learner', 'jordan@example.test');

        $this->actingAs($admin)
            ->post(route('students.portal-account.store', $student))
            ->assertSessionHas('success');

        $user = User::query()->where('email', $student->email)->firstOrFail();
        $token = null;
        Notification::assertSentTo(
            $user,
            ResetPassword::class,
            function (ResetPassword $notification) use (&$token): bool {
                $token = $notification->token;

                return true;
            }
        );
        $this->assertNotNull($token);
        auth()->logout();

        $this->get(route('password.reset', [
            'token' => $token,
            'email' => $user->email,
        ]))
            ->assertOk()
            ->assertSee('Set your password');

        $newPassword = 'student-portal-password';
        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => $newPassword,
            'password_confirmation' => $newPassword,
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check($newPassword, $user->fresh()->password));
    }

    public function test_existing_student_account_gets_a_reset_link_without_creating_another_user(): void
    {
        Notification::fake([ResetPassword::class]);

        $admin = $this->createAdminUser();
        $student = $this->createStudent('ST-102', 'Morgan', 'Learner', 'morgan@example.test');
        $user = User::factory()->create(['email' => $student->email]);
        $studentRole = Role::query()->where('name', 'Student')->firstOrFail();
        $user->roles()->attach($studentRole->id);

        $this->actingAs($admin)
            ->post(route('students.portal-account.store', $student))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(1, User::query()->where('email', $student->email)->count());
        Notification::assertSentTo($user, ResetPassword::class);

        $this->actingAs($admin)
            ->get(route('students.index'))
            ->assertSee('Send password setup link');
    }

    public function test_existing_non_student_account_is_not_modified_or_assigned_the_student_role(): void
    {
        Notification::fake([ResetPassword::class]);

        $admin = $this->createAdminUser();
        $student = $this->createStudent('ST-103', 'Taylor', 'Learner', 'taylor@example.test');
        $user = User::factory()->create(['email' => $student->email]);
        $staffRole = Role::create(['name' => 'Staff']);
        $user->roles()->attach($staffRole->id);

        $this->actingAs($admin)
            ->post(route('students.portal-account.store', $student))
            ->assertRedirect()
            ->assertSessionHasErrors('student');

        $this->assertFalse($user->fresh()->hasRole('Student'));
        $this->assertSame(1, User::query()->where('email', $student->email)->count());
        Notification::assertNothingSent();
    }

    public function test_student_portal_account_with_an_additional_role_is_rejected(): void
    {
        Notification::fake([ResetPassword::class]);

        $admin = $this->createAdminUser();
        $student = $this->createStudent('ST-111', 'Casey', 'Learner', 'casey@example.test');
        $user = User::factory()->create(['email' => $student->email]);
        $user->roles()->attach(Role::query()->where('name', 'Student')->firstOrFail()->id);
        $parentRole = Role::create(['name' => 'Parent']);
        $user->roles()->attach($parentRole->id);

        $this->actingAs($admin)
            ->post(route('students.portal-account.store', $student))
            ->assertRedirect()
            ->assertSessionHasErrors('student');

        Notification::assertNothingSent();
    }

    public function test_student_account_cannot_be_provisioned_without_an_email(): void
    {
        $admin = $this->createAdminUser();
        $student = $this->createStudent('ST-104', 'Riley', 'Learner', null);

        $this->actingAs($admin)
            ->post(route('students.portal-account.store', $student))
            ->assertRedirect()
            ->assertSessionHasErrors('student');

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseMissing('users', ['email' => null]);
    }

    public function test_student_account_cannot_be_provisioned_for_an_ambiguous_email_mapping(): void
    {
        $admin = $this->createAdminUser();
        $student = $this->createStudent('ST-106', 'Jamie', 'Learner', 'duplicate@example.test');
        $this->createStudent('ST-107', 'Jamie', 'Learner', 'duplicate@example.test');

        $this->actingAs($admin)
            ->post(route('students.portal-account.store', $student))
            ->assertRedirect()
            ->assertSessionHasErrors('student');

        $this->assertDatabaseCount('users', 1);
    }

    public function test_staff_without_student_update_permission_cannot_provision_accounts(): void
    {
        $user = User::factory()->create();
        $student = $this->createStudent('ST-105', 'Jamie', 'Learner', 'jamie@example.test');

        $this->actingAs($user)
            ->post(route('students.portal-account.store', $student))
            ->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => $student->email]);
    }

    public function test_students_update_without_portal_permission_cannot_provision_accounts(): void
    {
        Notification::fake([ResetPassword::class]);

        $user = $this->userWithPermissions('Student Editor', ['students.update', 'students.view']);
        $student = $this->createStudent('ST-112', 'Jamie', 'Learner', 'jamie@example.test');

        $this->actingAs($user)
            ->post(route('students.portal-account.store', $student))
            ->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => $student->email]);
        Notification::assertNothingSent();
    }

    public function test_changing_a_student_email_does_not_create_or_provision_an_account(): void
    {
        Notification::fake([ResetPassword::class]);

        $editor = $this->userWithPermissions('Student Editor', ['students.update']);
        $student = $this->createStudent('ST-113', 'Jordan', 'Learner', 'old@example.test');

        $this->actingAs($editor)
            ->put(route('students.update', $student), [
                'admission_number' => $student->admission_number,
                'first_name' => $student->first_name,
                'middle_name' => null,
                'last_name' => $student->last_name,
                'date_of_birth' => '2012-01-01',
                'gender' => 'Female',
                'phone' => null,
                'email' => 'new@example.test',
                'address' => null,
                'status' => 'active',
            ])
            ->assertRedirect(route('students.index'));

        $this->assertSame('new@example.test', $student->fresh()->email);
        $this->assertDatabaseMissing('users', ['email' => 'new@example.test']);
        Notification::assertNothingSent();
    }

    public function test_student_email_mapping_is_checked_without_case_sensitivity(): void
    {
        $admin = $this->createAdminUser();
        $student = $this->createStudent('ST-114', 'Taylor', 'Learner', 'duplicate@example.test');
        $this->createStudent('ST-115', 'Riley', 'Learner', 'Duplicate@Example.Test');

        $this->actingAs($admin)
            ->post(route('students.portal-account.store', $student))
            ->assertRedirect()
            ->assertSessionHasErrors('student');

        $this->assertDatabaseMissing('users', ['email' => $student->email]);
    }

    public function test_provisioned_student_can_set_password_log_in_and_view_only_own_results(): void
    {
        Notification::fake([ResetPassword::class]);

        $admin = $this->createAdminUser();
        $student = $this->createStudent('ST-116', 'Alex', 'Learner', 'alex.portal@example.test');
        $otherStudent = $this->createStudent('ST-117', 'Taylor', 'Learner', 'other@example.test');
        [$exam, $ownMark] = $this->createPublishedResults($student, $otherStudent);

        $this->actingAs($admin)
            ->post(route('students.portal-account.store', $student))
            ->assertSessionHas('success');

        $user = User::query()->where('email', $student->email)->firstOrFail();
        $token = null;
        Notification::assertSentTo(
            $user,
            ResetPassword::class,
            function (ResetPassword $notification) use (&$token): bool {
                $token = $notification->token;

                return true;
            }
        );
        $this->assertNotNull($token);
        auth()->logout();

        $password = 'student-portal-password';
        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => $password,
            'password_confirmation' => $password,
        ])->assertRedirect(route('login'));

        $this->post(route('login.submit'), [
            'email' => $user->email,
            'password' => $password,
        ])->assertRedirect('/dashboard');

        $response = $this->get(route('student-results.index', ['exam_id' => $exam->id]));

        $response->assertOk();
        $this->assertSame([$student->id], $response->viewData('students')->modelKeys());
        $this->assertSame([$ownMark->id], $response->viewData('resultRows')->modelKeys());
        $this->get(route('student-results.print', [
            'student' => $otherStudent,
            'exam_id' => $exam->id,
        ]))->assertNotFound();
    }

    public function test_student_list_offers_provisioning_for_a_unique_student_email(): void
    {
        $admin = $this->createAdminUser();
        $student = $this->createStudent('ST-108', 'Alex', 'Learner', 'alex@example.test');

        $this->actingAs($admin)
            ->get(route('students.index'))
            ->assertSee(route('students.portal-account.store', $student))
            ->assertSee('Create portal account');
    }

    public function test_student_list_hides_provisioning_for_students_update_only_users(): void
    {
        $editor = $this->userWithPermissions('Student Editor', ['students.update', 'students.view']);
        $student = $this->createStudent('ST-118', 'Morgan', 'Learner', 'morgan@example.test');

        $this->actingAs($editor)
            ->get(route('students.index'))
            ->assertOk()
            ->assertDontSee(route('students.portal-account.store', $student))
            ->assertDontSee('Create portal account');
    }

    public function test_student_list_hides_provisioning_for_ambiguous_student_emails(): void
    {
        $admin = $this->createAdminUser();
        $this->createStudent('ST-109', 'Taylor', 'Learner', 'duplicate@example.test');
        $this->createStudent('ST-110', 'Jordan', 'Learner', 'Duplicate@Example.Test');

        $this->actingAs($admin)
            ->get(route('students.index'))
            ->assertDontSee('Create portal account');
    }

    private function createAdminUser(): User
    {
        $user = User::factory()->create();
        Role::firstOrCreate(['name' => 'Student']);
        $role = Role::create(['name' => 'Student Account Admin']);
        foreach ([
            'students.update',
            'students.view',
            'students.portal_accounts.manage',
        ] as $permissionName) {
            $permission = Permission::firstOrCreate(['name' => $permissionName]);
            $role->permissions()->attach($permission->id);
        }
        $user->roles()->attach($role->id);

        return $user;
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

    private function createStudent(
        string $admissionNumber,
        string $firstName,
        string $lastName,
        ?string $email
    ): Student {
        return Student::create([
            'admission_number' => $admissionNumber,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
        ]);
    }

    private function createPublishedResults(Student $student, Student $otherStudent): array
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
            'end_date' => '2026-12-31',
        ]);
        $classRoom = ClassRoom::create(['name' => 'Class 1']);
        $subject = Subject::create([
            'code' => 'PORTAL-MATH',
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

        foreach ([$student, $otherStudent] as $enrolledStudent) {
            StudentEnrollment::create([
                'student_id' => $enrolledStudent->id,
                'academic_year_id' => $academicYear->id,
                'class_id' => $classRoom->id,
                'status' => 'active',
            ]);
        }

        $ownMark = Mark::create([
            'exam_subject_id' => $examSubject->id,
            'student_id' => $student->id,
            'marks_obtained' => 88,
            'status' => 'Approved',
        ]);
        Mark::create([
            'exam_subject_id' => $examSubject->id,
            'student_id' => $otherStudent->id,
            'marks_obtained' => 92,
            'status' => 'Approved',
        ]);
        ResultPublication::create([
            'exam_id' => $exam->id,
            'class_id' => $classRoom->id,
            'status' => 'Published',
            'published_at' => now(),
        ]);

        return [$exam, $ownMark];
    }
}
