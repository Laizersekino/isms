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
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BookBorrowingControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-10-02 12:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_unauthenticated_users_are_redirected_to_login(): void
    {
        $this->get(route('book-borrowings.index'))->assertRedirect(route('login'));
        $this->get(route('book-borrowings.create'))->assertRedirect(route('login'));
    }

    public function test_user_without_borrowing_view_permission_receives_forbidden_response(): void
    {
        $user = $this->userWithPermissions([]);

        $this->actingAs($user)
            ->get(route('book-borrowings.index'))
            ->assertForbidden();
    }

    public function test_user_without_issue_permission_cannot_open_or_submit_issue_form(): void
    {
        $user = $this->userWithPermissions(['borrowings.view']);
        $copy = $this->createBookCopy();
        $student = $this->createStudent();

        $this->actingAs($user)
            ->get(route('book-borrowings.create'))
            ->assertForbidden();

        $this->post(route('book-borrowings.store'), $this->validPayload($copy, $student))
            ->assertForbidden();
    }

    public function test_view_permission_lists_borrowings_with_status_and_borrower_filters(): void
    {
        $user = $this->userWithPermissions(['borrowings.view']);
        $student = $this->createStudent();
        $teacher = $this->createTeacher();
        $studentBorrowing = $this->createBorrowing($this->createBookCopy(), [
            'student_id' => $student->id,
            'borrowed_date' => '2026-09-25',
            'due_date' => '2026-10-01',
        ]);
        $returnedBorrowing = $this->createBorrowing($this->createBookCopy(), [
            'teacher_id' => $teacher->id,
            'status' => 'returned',
            'returned_date' => '2026-09-30',
        ]);

        $this->actingAs($user)
            ->get(route('book-borrowings.index', [
                'status' => 'borrowed',
                'overdue' => '1',
                'student_id' => $student->id,
            ]))
            ->assertSee(route('book-borrowings.show', $studentBorrowing))
            ->assertSee('Overdue')
            ->assertDontSee($returnedBorrowing->bookCopy->copy_number);
    }

    public function test_issue_permission_renders_available_copies_and_borrower_options(): void
    {
        $user = $this->userWithPermissions(['borrowings.issue']);
        $copy = $this->createBookCopy();
        $student = $this->createStudent();
        $teacher = $this->createTeacher();
        $this->createBookCopy(['status' => 'retired']);

        $this->actingAs($user)
            ->get(route('book-borrowings.create'))
            ->assertSee($copy->copy_number)
            ->assertSee($student->admission_number)
            ->assertSee($teacher->employee_number)
            ->assertDontSee('COPY-2');
    }

    public function test_issue_creates_borrowing_and_marks_copy_borrowed(): void
    {
        $user = $this->userWithPermissions(['borrowings.issue']);
        $copy = $this->createBookCopy();
        $student = $this->createStudent();

        $this->actingAs($user)
            ->post(route('book-borrowings.store'), $this->validPayload($copy, $student))
            ->assertRedirect(route('book-borrowings.show', BookBorrowing::query()->first()))
            ->assertSessionHas('success', 'Book issued successfully.');

        $this->assertDatabaseHas('book_borrowings', [
            'book_copy_id' => $copy->id,
            'student_id' => $student->id,
            'teacher_id' => null,
            'issued_by' => $user->id,
            'status' => 'borrowed',
            'renewal_count' => 0,
        ]);
        $this->assertDatabaseHas('book_copies', [
            'id' => $copy->id,
            'status' => 'borrowed',
        ]);
    }

    public function test_issue_supports_a_teacher_as_borrower(): void
    {
        $user = $this->userWithPermissions(['borrowings.issue']);
        $copy = $this->createBookCopy();
        $teacher = $this->createTeacher();

        $this->actingAs($user)
            ->post(route('book-borrowings.store'), $this->validPayload($copy, $teacher, 'teacher'))
            ->assertRedirect();

        $this->assertDatabaseHas('book_borrowings', [
            'book_copy_id' => $copy->id,
            'student_id' => null,
            'teacher_id' => $teacher->id,
        ]);
    }

    public function test_issue_validation_rejects_unavailable_copy_and_invalid_dates(): void
    {
        $user = $this->userWithPermissions(['borrowings.issue']);
        $copy = $this->createBookCopy(['status' => 'borrowed']);
        $student = $this->createStudent();
        $payload = $this->validPayload($copy, $student);

        $this->actingAs($user)
            ->from(route('book-borrowings.create'))
            ->post(route('book-borrowings.store'), $payload)
            ->assertRedirect(route('book-borrowings.create'))
            ->assertSessionHasErrors('book_copy_id');

        $copy->update(['status' => 'available']);

        $this->from(route('book-borrowings.create'))
            ->post(route('book-borrowings.store'), array_replace($payload, [
                'borrowed_date' => '2026-10-03',
                'due_date' => '2026-10-04',
            ]))
            ->assertSessionHasErrors('borrowed_date');

        $this->from(route('book-borrowings.create'))
            ->post(route('book-borrowings.store'), array_replace($payload, [
                'due_date' => '2026-09-30',
            ]))
            ->assertSessionHasErrors('due_date');

        $this->assertDatabaseCount('book_borrowings', 0);
    }

    public function test_issue_validation_rejects_an_invalid_borrower_type_or_id(): void
    {
        $user = $this->userWithPermissions(['borrowings.issue']);
        $copy = $this->createBookCopy();
        $student = $this->createStudent();
        $payload = $this->validPayload($copy, $student);

        $this->actingAs($user)
            ->from(route('book-borrowings.create'))
            ->post(route('book-borrowings.store'), array_replace($payload, [
                'borrower_type' => 'parent',
            ]))
            ->assertSessionHasErrors('borrower_type');

        $this->from(route('book-borrowings.create'))
            ->post(route('book-borrowings.store'), array_replace($payload, [
                'borrower_id' => 999999,
            ]))
            ->assertSessionHasErrors('borrower_id');

        $this->assertDatabaseCount('book_borrowings', 0);
    }

    public function test_issue_fails_when_borrower_has_an_overdue_borrowing(): void
    {
        $user = $this->userWithPermissions(['borrowings.issue']);
        $student = $this->createStudent();
        $this->createBorrowing($this->createBookCopy(), [
            'student_id' => $student->id,
            'due_date' => '2026-10-01',
        ]);
        $copy = $this->createBookCopy();

        $this->actingAs($user)
            ->from(route('book-borrowings.create'))
            ->post(route('book-borrowings.store'), $this->validPayload($copy, $student))
            ->assertSessionHasErrors('borrower_id');

        $this->assertDatabaseCount('book_borrowings', 1);
        $this->assertDatabaseHas('book_copies', ['id' => $copy->id, 'status' => 'available']);
    }

    public function test_issue_fails_when_borrower_reaches_the_active_book_limit(): void
    {
        $user = $this->userWithPermissions(['borrowings.issue']);
        $student = $this->createStudent();

        for ($index = 0; $index < config('library.max_books_per_user'); $index++) {
            $this->createBorrowing($this->createBookCopy(), [
                'student_id' => $student->id,
                'due_date' => '2026-10-10',
            ]);
        }

        $copy = $this->createBookCopy();

        $this->actingAs($user)
            ->from(route('book-borrowings.create'))
            ->post(route('book-borrowings.store'), $this->validPayload($copy, $student))
            ->assertSessionHasErrors('borrower_id');

        $this->assertDatabaseCount('book_borrowings', config('library.max_books_per_user'));
        $this->assertDatabaseHas('book_copies', ['id' => $copy->id, 'status' => 'available']);
    }

    public function test_show_displays_borrowing_details_and_fine_records(): void
    {
        $user = $this->userWithPermissions(['borrowings.view']);
        $student = $this->createStudent();
        $borrowing = $this->createBorrowing($this->createBookCopy(), [
            'student_id' => $student->id,
        ]);
        LibraryFine::create([
            'book_borrowing_id' => $borrowing->id,
            'amount' => '1000.00',
            'reason' => 'overdue',
            'status' => 'unpaid',
            'issued_date' => '2026-10-02',
        ]);

        $this->actingAs($user)
            ->get(route('book-borrowings.show', $borrowing))
            ->assertSee($student->first_name.' '.$student->last_name)
            ->assertSee($borrowing->bookCopy->copy_number)
            ->assertSee('TZS 1,000.00')
            ->assertSee('Renew');
    }

    public function test_returning_on_due_date_releases_copy_without_creating_a_fine(): void
    {
        $user = $this->userWithPermissions(['borrowings.return']);
        $copy = $this->createBookCopy(['status' => 'borrowed']);
        $borrowing = $this->createBorrowing($copy, ['due_date' => '2026-10-02']);

        $this->actingAs($user)
            ->post(route('book-borrowings.return', $borrowing))
            ->assertRedirect(route('book-borrowings.show', $borrowing))
            ->assertSessionHas('success', 'Book returned successfully.');

        $borrowing->refresh();
        $this->assertSame('returned', $borrowing->status);
        $this->assertSame('2026-10-02', $borrowing->returned_date->format('Y-m-d'));
        $this->assertDatabaseHas('book_copies', ['id' => $copy->id, 'status' => 'available']);
        $this->assertDatabaseCount('library_fines', 0);
    }

    public function test_late_return_creates_an_unpaid_fine_for_each_overdue_day(): void
    {
        $user = $this->userWithPermissions(['borrowings.return']);
        $copy = $this->createBookCopy(['status' => 'borrowed']);
        $borrowing = $this->createBorrowing($copy, ['due_date' => '2026-09-30']);

        $this->actingAs($user)
            ->post(route('book-borrowings.return', $borrowing))
            ->assertRedirect(route('book-borrowings.show', $borrowing));

        $fine = LibraryFine::query()->sole();
        $this->assertSame((string) $borrowing->id, (string) $fine->book_borrowing_id);
        $this->assertSame('1000.00', $fine->amount);
        $this->assertSame('overdue', $fine->reason);
        $this->assertSame('unpaid', $fine->status);
        $this->assertSame('2026-10-02', $fine->issued_date->format('Y-m-d'));
        $this->assertSame($user->id, $fine->recorded_by);
        $this->assertDatabaseHas('book_copies', ['id' => $copy->id, 'status' => 'available']);
    }

    public function test_returning_an_already_returned_borrowing_is_rejected(): void
    {
        $user = $this->userWithPermissions(['borrowings.return']);
        $borrowing = $this->createBorrowing($this->createBookCopy(), [
            'status' => 'returned',
            'returned_date' => '2026-09-30',
        ]);

        $this->actingAs($user)
            ->from(route('book-borrowings.show', $borrowing))
            ->post(route('book-borrowings.return', $borrowing))
            ->assertRedirect(route('book-borrowings.show', $borrowing))
            ->assertSessionHasErrors('borrowing');

        $this->assertDatabaseCount('library_fines', 0);
    }

    public function test_return_route_requires_borrowings_return_permission(): void
    {
        $user = $this->userWithPermissions(['borrowings.view']);
        $borrowing = $this->createBorrowing($this->createBookCopy());

        $this->actingAs($user)
            ->post(route('book-borrowings.return', $borrowing))
            ->assertForbidden();

        $this->assertDatabaseHas('book_borrowings', [
            'id' => $borrowing->id,
            'status' => 'borrowed',
        ]);
    }

    public function test_renewal_extends_due_date_and_tracks_renewal_count(): void
    {
        $user = $this->userWithPermissions(['borrowings.renew']);
        $student = $this->createStudent();
        $borrowing = $this->createBorrowing($this->createBookCopy(), [
            'student_id' => $student->id,
            'due_date' => '2026-10-10',
        ]);

        $this->actingAs($user)
            ->post(route('book-borrowings.renew', $borrowing))
            ->assertRedirect(route('book-borrowings.show', $borrowing))
            ->assertSessionHas('success', 'Borrowing renewed successfully.');

        $borrowing->refresh();
        $this->assertSame('2026-10-24', $borrowing->due_date->format('Y-m-d'));
        $this->assertSame(1, $borrowing->renewal_count);
    }

    public function test_renewal_fails_when_maximum_renewals_is_reached(): void
    {
        $user = $this->userWithPermissions(['borrowings.renew']);
        $student = $this->createStudent();
        $borrowing = $this->createBorrowing($this->createBookCopy(), [
            'student_id' => $student->id,
            'due_date' => '2026-10-10',
            'renewal_count' => config('library.max_renewals'),
        ]);

        $this->actingAs($user)
            ->from(route('book-borrowings.show', $borrowing))
            ->post(route('book-borrowings.renew', $borrowing))
            ->assertSessionHasErrors('borrowing');

        $borrowing->refresh();
        $this->assertSame('2026-10-10', $borrowing->due_date->format('Y-m-d'));
        $this->assertSame(config('library.max_renewals'), $borrowing->renewal_count);
    }

    public function test_renewal_fails_when_borrower_has_an_overdue_borrowing(): void
    {
        $user = $this->userWithPermissions(['borrowings.renew']);
        $student = $this->createStudent();
        $borrowing = $this->createBorrowing($this->createBookCopy(), [
            'student_id' => $student->id,
            'due_date' => '2026-10-10',
        ]);
        $this->createBorrowing($this->createBookCopy(), [
            'student_id' => $student->id,
            'due_date' => '2026-10-01',
        ]);

        $this->actingAs($user)
            ->from(route('book-borrowings.show', $borrowing))
            ->post(route('book-borrowings.renew', $borrowing))
            ->assertSessionHasErrors('borrowing');

        $borrowing->refresh();
        $this->assertSame('2026-10-10', $borrowing->due_date->format('Y-m-d'));
        $this->assertSame(0, $borrowing->renewal_count);
    }

    public function test_renewal_fails_when_copy_is_reserved_by_another_borrower(): void
    {
        $user = $this->userWithPermissions(['borrowings.renew']);
        $student = $this->createStudent();
        $copy = $this->createBookCopy(['status' => 'borrowed']);
        $borrowing = $this->createBorrowing($copy, [
            'student_id' => $student->id,
            'due_date' => '2026-10-10',
        ]);
        $this->createBorrowing($copy, ['status' => 'reserved']);

        $this->actingAs($user)
            ->from(route('book-borrowings.show', $borrowing))
            ->post(route('book-borrowings.renew', $borrowing))
            ->assertSessionHasErrors('borrowing');

        $borrowing->refresh();
        $this->assertSame('2026-10-10', $borrowing->due_date->format('Y-m-d'));
        $this->assertSame(0, $borrowing->renewal_count);
    }

    public function test_renew_route_requires_borrowings_renew_permission(): void
    {
        $user = $this->userWithPermissions(['borrowings.view']);
        $borrowing = $this->createBorrowing($this->createBookCopy());

        $this->actingAs($user)
            ->post(route('book-borrowings.renew', $borrowing))
            ->assertForbidden();

        $this->assertDatabaseHas('book_borrowings', [
            'id' => $borrowing->id,
            'renewal_count' => 0,
        ]);
    }

    public function test_borrowing_permission_migration_replaces_legacy_permissions_with_scoped_grants(): void
    {
        $roles = collect([
            'Super Administrator',
            'School Administrator',
            'Librarian',
            'Teacher',
        ])->mapWithKeys(fn (string $name): array => [
            $name => Role::create(['name' => $name]),
        ]);
        $oldIssue = Permission::create(['name' => 'books.issue']);
        $oldReturn = Permission::create(['name' => 'books.return']);
        $roles['Super Administrator']->permissions()->attach([$oldIssue->id, $oldReturn->id]);
        $roles['Librarian']->permissions()->attach([$oldIssue->id, $oldReturn->id]);

        $migration = require database_path(
            'migrations/2026_10_02_090000_add_library_borrowings_permissions.php'
        );
        $migration->up();

        $this->assertDatabaseMissing('permissions', ['name' => 'books.issue']);
        $this->assertDatabaseMissing('permissions', ['name' => 'books.return']);

        foreach (['Super Administrator', 'School Administrator', 'Librarian'] as $roleName) {
            $this->assertSame(
                3,
                $roles[$roleName]->fresh()->permissions()
                    ->whereIn('name', [
                        'borrowings.issue',
                        'borrowings.return',
                        'borrowings.renew',
                    ])
                    ->count()
            );
        }

        $teacherPermissions = $roles['Teacher']->fresh()->permissions()
            ->whereIn('name', [
                'borrowings.view',
                'borrowings.issue',
                'borrowings.return',
                'borrowings.renew',
            ])
            ->pluck('name')
            ->all();

        $this->assertSame(['borrowings.view'], $teacherPermissions);
        $this->assertSame(
            1,
            $roles['Super Administrator']->fresh()->permissions()
                ->where('name', 'borrowings.view')
                ->count()
        );

        $this->assertSame(
            0,
            DB::table('role_permissions')
                ->whereIn('permission_id', [$oldIssue->id, $oldReturn->id])
                ->count()
        );
    }

    private function userWithPermissions(array $permissionNames): User
    {
        $user = User::factory()->create();
        $role = Role::create(['name' => 'Test Borrowing Role']);

        foreach ($permissionNames as $permissionName) {
            $permission = Permission::firstOrCreate(['name' => $permissionName]);
            $role->permissions()->attach($permission->id);
        }

        $user->roles()->attach($role->id);

        return $user;
    }

    private function createBookCopy(array $overrides = []): BookCopy
    {
        $book = Book::create([
            'title' => 'Borrowing Book '.(Book::query()->count() + 1),
            'author' => 'Library Author',
            'status' => 'active',
        ]);

        return BookCopy::create(array_replace([
            'book_id' => $book->id,
            'copy_number' => 'COPY-'.$book->id,
            'condition' => 'good',
            'status' => 'available',
        ], $overrides));
    }

    private function createStudent(): Student
    {
        $number = Student::query()->count() + 1;

        return Student::create([
            'admission_number' => 'ST-'.$number,
            'first_name' => 'Student',
            'last_name' => 'Reader '.$number,
            'date_of_birth' => '2010-01-01',
            'gender' => 'Female',
            'status' => 'active',
        ]);
    }

    private function createTeacher(): Teacher
    {
        $number = Teacher::query()->count() + 1;

        return Teacher::create([
            'employee_number' => 'EMP-'.$number,
            'first_name' => 'Teacher',
            'last_name' => 'Reader '.$number,
            'status' => 'active',
        ]);
    }

    private function createBorrowing(BookCopy $copy, array $overrides = []): BookBorrowing
    {
        return BookBorrowing::create(array_replace([
            'book_copy_id' => $copy->id,
            'student_id' => null,
            'teacher_id' => null,
            'borrowed_date' => '2026-10-01',
            'due_date' => '2026-10-15',
            'status' => 'borrowed',
            'renewal_count' => 0,
        ], $overrides));
    }

    private function validPayload(BookCopy $copy, Student|Teacher $borrower, string $borrowerType = 'student'): array
    {
        return [
            'book_copy_id' => $copy->id,
            'borrower_type' => $borrowerType,
            'borrower_id' => $borrower->id,
            'borrowed_date' => '2026-10-02',
            'due_date' => '2026-10-16',
            'remarks' => 'Issued for reading.',
        ];
    }
}
