<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Book;
use App\Models\BookBorrowing;
use App\Models\BookCopy;
use App\Models\LibraryFine;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LibraryFineControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_users_are_redirected_to_login(): void
    {
        $fine = $this->createFine();

        $this->get(route('library-fines.index'))->assertRedirect(route('login'));
        $this->get(route('library-fines.show', $fine))->assertRedirect(route('login'));
    }

    public function test_user_without_fines_view_permission_receives_forbidden_response(): void
    {
        $user = $this->userWithPermissions([]);

        $this->actingAs($user)
            ->get(route('library-fines.index'))
            ->assertForbidden();
    }

    public function test_fines_view_permission_lists_fines_with_currency_and_status_filter(): void
    {
        $user = $this->userWithPermissions(['fines.view']);
        $student = $this->createStudent();
        $target = $this->createFine([
            'student_id' => $student->id,
            'amount' => '1500.00',
            'status' => 'unpaid',
        ]);
        $otherStudent = $this->createStudent();
        $otherFine = $this->createFine([
            'student_id' => $otherStudent->id,
            'status' => 'paid',
            'issued_date' => '2026-09-01',
        ]);

        $this->actingAs($user)
            ->get(route('library-fines.index', [
                'status' => 'unpaid',
                'student_id' => $student->id,
                'date_from' => '2026-10-01',
                'date_to' => '2026-10-31',
            ]))
            ->assertOk()
            ->assertSee(route('library-fines.show', $target))
            ->assertSee('TZS 1,500.00')
            ->assertSee('Unpaid')
            ->assertDontSee(route('library-fines.show', $otherFine));
    }

    public function test_fines_index_filters_for_teacher_borrowers(): void
    {
        $user = $this->userWithPermissions(['fines.view']);
        $teacher = $this->createTeacher();
        $target = $this->createFine(['teacher_id' => $teacher->id]);
        $studentFine = $this->createFine(['student_id' => $this->createStudent()->id]);

        $this->actingAs($user)
            ->get(route('library-fines.index', ['teacher_id' => $teacher->id]))
            ->assertSee(route('library-fines.show', $target))
            ->assertDontSee(route('library-fines.show', $studentFine));
    }

    public function test_fines_view_permission_shows_fine_and_borrowing_details(): void
    {
        $user = $this->userWithPermissions(['fines.view']);
        $student = $this->createStudent();
        $fine = $this->createFine(['student_id' => $student->id]);

        $this->actingAs($user)
            ->get(route('library-fines.show', $fine))
            ->assertOk()
            ->assertSee($fine->borrowing->bookCopy->book->title)
            ->assertSee($fine->borrowing->bookCopy->copy_number)
            ->assertSee($student->first_name.' '.$student->last_name)
            ->assertSee('TZS 500.00')
            ->assertSee('Waived');
    }

    public function test_fines_pay_permission_marks_an_unpaid_fine_as_paid(): void
    {
        $this->travelTo('2026-10-02');

        $user = $this->userWithPermissions(['fines.pay']);
        $fine = $this->createFine();

        $this->actingAs($user)
            ->post(route('library-fines.pay', $fine))
            ->assertRedirect(route('library-fines.show', $fine))
            ->assertSessionHas('success', 'Fine payment recorded successfully.');

        $fine->refresh();
        $this->assertSame('paid', $fine->status);
        $this->assertSame('2026-10-02', $fine->paid_date->format('Y-m-d'));
        $this->assertSame($user->id, $fine->recorded_by);
        $this->assertSame($user->id, $fine->paid_by);
    }

    public function test_paid_fine_cannot_be_paid_again(): void
    {
        $user = $this->userWithPermissions(['fines.pay']);
        $fine = $this->createFine([
            'status' => 'paid',
            'paid_date' => '2026-10-01',
        ]);

        $this->actingAs($user)
            ->from(route('library-fines.show', $fine))
            ->post(route('library-fines.pay', $fine))
            ->assertRedirect(route('library-fines.show', $fine))
            ->assertSessionHasErrors('fine');

        $this->assertSame('2026-10-01', $fine->fresh()->paid_date->format('Y-m-d'));
    }

    public function test_waived_fine_cannot_be_paid(): void
    {
        $user = $this->userWithPermissions(['fines.pay']);
        $fine = $this->createFine(['status' => 'waived']);

        $this->actingAs($user)
            ->from(route('library-fines.show', $fine))
            ->post(route('library-fines.pay', $fine))
            ->assertSessionHasErrors('fine');

        $this->assertSame('waived', $fine->fresh()->status);
        $this->assertNull($fine->fresh()->paid_date);
    }

    public function test_fines_waive_permission_waives_an_unpaid_fine_with_a_reason(): void
    {
        $this->travelTo('2026-10-02');

        $user = $this->userWithPermissions(['fines.waive']);
        $fine = $this->createFine();
        $reason = 'Approved after review.';

        $this->actingAs($user)
            ->post(route('library-fines.waive', $fine), ['reason' => $reason])
            ->assertRedirect(route('library-fines.show', $fine))
            ->assertSessionHas('success', 'Fine waived successfully.');

        $fine->refresh();
        $this->assertSame('waived', $fine->status);
        $this->assertSame($reason, $fine->remarks);
        $this->assertSame($user->id, $fine->recorded_by);
        $this->assertSame($user->id, $fine->waived_by);
        $this->assertSame('2026-10-02', $fine->waived_date->format('Y-m-d'));
        $this->assertNull($fine->paid_date);
    }

    public function test_paid_fine_cannot_be_waived(): void
    {
        $user = $this->userWithPermissions(['fines.waive']);
        $fine = $this->createFine([
            'status' => 'paid',
            'paid_date' => '2026-10-01',
        ]);

        $this->actingAs($user)
            ->from(route('library-fines.show', $fine))
            ->post(route('library-fines.waive', $fine), ['reason' => 'Approved after review.'])
            ->assertSessionHasErrors('fine');

        $this->assertSame('paid', $fine->fresh()->status);
        $this->assertNull($fine->fresh()->waived_by);
    }

    public function test_already_waived_fine_cannot_be_waived_again(): void
    {
        $user = $this->userWithPermissions(['fines.waive']);
        $fine = $this->createFine([
            'status' => 'waived',
            'remarks' => 'Previously approved.',
            'waived_by' => User::factory()->create()->id,
            'waived_date' => '2026-10-01',
        ]);

        $this->actingAs($user)
            ->from(route('library-fines.show', $fine))
            ->post(route('library-fines.waive', $fine), ['reason' => 'Another waiver reason.'])
            ->assertSessionHasErrors('fine');

        $this->assertSame('Previously approved.', $fine->fresh()->remarks);
    }

    public function test_waiving_requires_a_reason_of_at_least_ten_characters(): void
    {
        $user = $this->userWithPermissions(['fines.waive']);
        $fine = $this->createFine();

        $this->actingAs($user)
            ->from(route('library-fines.show', $fine))
            ->post(route('library-fines.waive', $fine))
            ->assertSessionHasErrors('reason');

        $this->post(route('library-fines.waive', $fine), ['reason' => 'Too short'])
            ->assertSessionHasErrors('reason');

        $this->assertSame('unpaid', $fine->fresh()->status);
        $this->assertNull($fine->fresh()->remarks);
    }

    public function test_user_without_fines_waive_permission_is_forbidden(): void
    {
        $user = $this->userWithPermissions(['fines.view']);
        $fine = $this->createFine();

        $this->actingAs($user)
            ->post(route('library-fines.waive', $fine), ['reason' => 'Approved after review.'])
            ->assertForbidden();

        $this->assertSame('unpaid', $fine->fresh()->status);
    }

    public function test_user_without_fines_pay_permission_is_forbidden(): void
    {
        $user = $this->userWithPermissions(['fines.view']);
        $fine = $this->createFine();

        $this->actingAs($user)
            ->post(route('library-fines.pay', $fine))
            ->assertForbidden();

        $this->assertSame('unpaid', $fine->fresh()->status);
    }

    public function test_fines_permissions_migration_grants_view_pay_and_waive_to_responsible_roles(): void
    {
        $roles = collect([
            'Super Administrator',
            'School Administrator',
            'Librarian',
            'Accountant',
            'Finance Officer',
        ])->mapWithKeys(fn (string $name): array => [
            $name => Role::create(['name' => $name]),
        ]);

        $migration = require database_path(
            'migrations/2026_10_02_120000_add_library_fines_permissions.php'
        );
        $migration->up();

        foreach (['Super Administrator', 'School Administrator'] as $roleName) {
            $this->assertSame(
                ['fines.pay', 'fines.view', 'fines.waive'],
                $roles[$roleName]->fresh()->permissions()
                    ->whereIn('name', ['fines.view', 'fines.pay', 'fines.waive'])
                    ->orderBy('name')
                    ->pluck('name')
                    ->all()
            );
        }

        foreach (['Accountant', 'Finance Officer'] as $roleName) {
            $this->assertSame(
                ['fines.pay', 'fines.view'],
                $roles[$roleName]->fresh()->permissions()
                    ->whereIn('name', ['fines.view', 'fines.pay', 'fines.waive'])
                    ->orderBy('name')
                    ->pluck('name')
                    ->all()
            );
        }

        $this->assertSame(
            ['fines.view'],
            $roles['Librarian']->fresh()->permissions()
                ->whereIn('name', ['fines.view', 'fines.pay', 'fines.waive'])
                ->orderBy('name')
                ->pluck('name')
                ->all()
        );
    }

    private function userWithPermissions(array $permissionNames): User
    {
        $user = User::factory()->create();
        $role = Role::create(['name' => 'Test Fine Role']);

        foreach ($permissionNames as $permissionName) {
            $permission = Permission::firstOrCreate(['name' => $permissionName]);
            $role->permissions()->attach($permission->id);
        }

        $user->roles()->attach($role->id);

        return $user;
    }

    private function createFine(array $overrides = []): LibraryFine
    {
        $borrowingAttributes = [
            'student_id' => $overrides['student_id'] ?? null,
            'teacher_id' => $overrides['teacher_id'] ?? null,
        ];
        unset($overrides['student_id'], $overrides['teacher_id']);

        $book = Book::create([
            'title' => 'Library Fine Book '.(Book::query()->count() + 1),
            'author' => 'Fine Test Author',
            'status' => 'active',
        ]);
        $copy = BookCopy::create([
            'book_id' => $book->id,
            'copy_number' => 'FINE-COPY-'.$book->id,
            'condition' => 'good',
            'status' => 'borrowed',
        ]);
        $borrowing = BookBorrowing::create(array_replace([
            'book_copy_id' => $copy->id,
            'borrowed_date' => '2026-09-15',
            'due_date' => '2026-09-29',
            'status' => 'returned',
            'returned_date' => '2026-10-01',
        ], $borrowingAttributes));

        return LibraryFine::create(array_replace([
            'book_borrowing_id' => $borrowing->id,
            'amount' => '500.00',
            'reason' => 'overdue',
            'status' => 'unpaid',
            'issued_date' => '2026-10-01',
            'recorded_by' => null,
            'remarks' => null,
        ], $overrides));
    }

    private function createStudent(): Student
    {
        $number = Student::query()->count() + 1;

        return Student::create([
            'admission_number' => 'FINE-ST-'.$number,
            'first_name' => 'Fine',
            'last_name' => 'Student '.$number,
            'date_of_birth' => '2010-01-01',
            'gender' => 'Female',
            'status' => 'active',
        ]);
    }

    private function createTeacher(): Teacher
    {
        $number = Teacher::query()->count() + 1;

        return Teacher::create([
            'employee_number' => 'FINE-EMP-'.$number,
            'first_name' => 'Fine',
            'last_name' => 'Teacher '.$number,
            'status' => 'active',
        ]);
    }
}
