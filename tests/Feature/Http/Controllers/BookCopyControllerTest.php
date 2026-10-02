<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Book;
use App\Models\BookBorrowing;
use App\Models\BookCopy;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookCopyControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_users_are_redirected_to_login(): void
    {
        $this->get(route('book-copies.index'))->assertRedirect(route('login'));
        $this->get(route('book-copies.create'))->assertRedirect(route('login'));
    }

    public function test_user_without_book_copy_view_permission_receives_forbidden_response(): void
    {
        $user = $this->userWithPermissions([]);

        $this->actingAs($user)
            ->get(route('book-copies.index'))
            ->assertForbidden();
    }

    public function test_book_copies_view_permission_searches_and_filters_copies(): void
    {
        $user = $this->userWithPermissions(['book_copies.view']);
        $firstBook = $this->createBook('The Reading Garden');
        $secondBook = $this->createBook('Science Today');
        $matchingCopy = $this->createBookCopy($firstBook, [
            'copy_number' => 'LIB-101',
            'barcode' => 'BAR-101',
            'status' => 'available',
        ]);
        $this->createBookCopy($secondBook, [
            'copy_number' => 'LIB-102',
            'barcode' => 'BAR-102',
            'status' => 'retired',
        ]);

        $this->actingAs($user)
            ->get(route('book-copies.index', [
                'search' => 'Reading',
                'status' => 'available',
            ]))
            ->assertSee('The Reading Garden')
            ->assertSee('LIB-101')
            ->assertDontSee('Science Today')
            ->assertDontSee('LIB-102')
            ->assertSee(route('book-copies.show', $matchingCopy));
    }

    public function test_book_copies_index_supports_barcode_search_and_pagination(): void
    {
        $user = $this->userWithPermissions(['book_copies.view']);
        $book = $this->createBook('Reference Book');
        $matchingCopy = $this->createBookCopy($book, [
            'copy_number' => 'REF-001',
            'barcode' => 'SCAN-001',
        ]);

        for ($index = 2; $index <= 17; $index++) {
            $this->createBookCopy($book, [
                'copy_number' => 'REF-'.str_pad((string) $index, 3, '0', STR_PAD_LEFT),
                'barcode' => 'SCAN-'.str_pad((string) $index, 3, '0', STR_PAD_LEFT),
            ]);
        }

        $this->actingAs($user)
            ->get(route('book-copies.index', ['search' => 'SCAN-001']))
            ->assertSee('REF-001')
            ->assertDontSee('REF-002');

        $this->get(route('book-copies.index'))
            ->assertSee('page=2');
    }

    public function test_book_copies_create_permission_renders_create_form_with_book_options(): void
    {
        $user = $this->userWithPermissions(['book_copies.create']);
        $book = $this->createBook('Reference Book');

        $this->actingAs($user)
            ->get(route('book-copies.create'))
            ->assertSee('Add Book Copy')
            ->assertSee('Reference Book')
            ->assertSee('name="copy_number"', false);
    }

    public function test_book_copies_create_permission_stores_a_valid_copy(): void
    {
        $user = $this->userWithPermissions(['book_copies.create']);
        $book = $this->createBook();

        $this->actingAs($user)
            ->post(route('book-copies.store'), $this->validPayload($book))
            ->assertRedirect(route('book-copies.show', BookCopy::query()->first()))
            ->assertSessionHas('success', 'Book copy created successfully.');

        $this->assertDatabaseHas('book_copies', [
            'book_id' => $book->id,
            'copy_number' => 'COPY-001',
            'barcode' => 'BARCODE-001',
            'condition' => 'good',
            'status' => 'available',
            'purchase_price' => 12.5,
        ]);
    }

    public function test_book_copy_store_rejects_invalid_required_fields_and_values(): void
    {
        $user = $this->userWithPermissions(['book_copies.create']);
        $book = $this->createBook();
        $invalidCases = [
            ['book_id', ['book_id' => '']],
            ['copy_number', ['copy_number' => '']],
            ['condition', ['condition' => 'excellent']],
            ['status', ['status' => 'checked_out']],
            ['acquisition_date', ['acquisition_date' => 'not-a-date']],
            ['purchase_price', ['purchase_price' => -1]],
            ['remarks', ['remarks' => str_repeat('x', 1001)]],
        ];

        $this->actingAs($user);

        foreach ($invalidCases as [$errorField, $overrides]) {
            $this->post(route('book-copies.store'), array_replace(
                $this->validPayload($book),
                $overrides
            ))->assertSessionHasErrors($errorField);
        }

        $this->assertDatabaseCount('book_copies', 0);
    }

    public function test_copy_number_must_be_unique_within_the_same_book(): void
    {
        $user = $this->userWithPermissions(['book_copies.create']);
        $book = $this->createBook();
        $this->createBookCopy($book, ['copy_number' => 'DUP-001']);

        $this->actingAs($user)
            ->from(route('book-copies.create'))
            ->post(route('book-copies.store'), $this->validPayload($book, [
                'copy_number' => 'DUP-001',
            ]))
            ->assertRedirect(route('book-copies.create'))
            ->assertSessionHasErrors('copy_number');

        $this->assertDatabaseCount('book_copies', 1);
    }

    public function test_same_copy_number_is_allowed_for_different_books(): void
    {
        $user = $this->userWithPermissions(['book_copies.create']);
        $firstBook = $this->createBook('First Book');
        $secondBook = $this->createBook('Second Book');
        $this->createBookCopy($firstBook, ['copy_number' => 'DUP-001']);

        $this->actingAs($user)
            ->post(route('book-copies.store'), $this->validPayload($secondBook, [
                'copy_number' => 'DUP-001',
            ]))
            ->assertRedirect();

        $this->assertDatabaseHas('book_copies', [
            'book_id' => $secondBook->id,
            'copy_number' => 'DUP-001',
        ]);
    }

    public function test_barcode_must_be_globally_unique(): void
    {
        $user = $this->userWithPermissions(['book_copies.create']);
        $firstBook = $this->createBook('First Book');
        $secondBook = $this->createBook('Second Book');
        $this->createBookCopy($firstBook, ['barcode' => 'GLOBAL-001']);

        $this->actingAs($user)
            ->from(route('book-copies.create'))
            ->post(route('book-copies.store'), $this->validPayload($secondBook, [
                'barcode' => 'GLOBAL-001',
            ]))
            ->assertRedirect(route('book-copies.create'))
            ->assertSessionHasErrors('barcode');

        $this->assertDatabaseCount('book_copies', 1);
    }

    public function test_book_copies_view_permission_shows_copy_and_current_borrowing(): void
    {
        $user = $this->userWithPermissions(['book_copies.view']);
        $book = $this->createBook('Reference Book');
        $copy = $this->createBookCopy($book);
        $student = Student::create([
            'admission_number' => 'ST-101',
            'first_name' => 'Alex',
            'last_name' => 'Reader',
            'date_of_birth' => '2010-01-01',
            'gender' => 'Male',
            'status' => 'active',
        ]);
        $borrowing = $this->createBorrowing($copy, ['student_id' => $student->id]);

        $this->actingAs($user)
            ->get(route('book-copies.show', $copy))
            ->assertSee('Reference Book')
            ->assertSee($copy->copy_number)
            ->assertSee('Alex Reader (Student)')
            ->assertSee($borrowing->borrowed_date->format('Y-m-d'));
    }

    public function test_book_copies_update_permission_updates_a_copy_and_allows_its_existing_barcode(): void
    {
        $user = $this->userWithPermissions(['book_copies.update']);
        $book = $this->createBook();
        $copy = $this->createBookCopy($book, [
            'copy_number' => 'COPY-001',
            'barcode' => 'BARCODE-001',
        ]);

        $this->actingAs($user)
            ->put(route('book-copies.update', $copy), $this->validPayload($book, [
                'copy_number' => 'COPY-002',
                'barcode' => 'BARCODE-001',
                'status' => 'retired',
            ]))
            ->assertRedirect(route('book-copies.show', $copy))
            ->assertSessionHas('success', 'Book copy updated successfully.');

        $this->assertDatabaseHas('book_copies', [
            'id' => $copy->id,
            'copy_number' => 'COPY-002',
            'barcode' => 'BARCODE-001',
            'status' => 'retired',
        ]);
    }

    public function test_book_copies_update_permission_renders_edit_form(): void
    {
        $user = $this->userWithPermissions(['book_copies.update']);
        $book = $this->createBook('Reference Book');
        $copy = $this->createBookCopy($book);

        $this->actingAs($user)
            ->get(route('book-copies.edit', $copy))
            ->assertSee('Edit Book Copy')
            ->assertSee('Reference Book')
            ->assertSee($copy->copy_number);
    }

    public function test_book_copies_delete_permission_deletes_a_copy_without_borrowings(): void
    {
        $user = $this->userWithPermissions(['book_copies.delete']);
        $copy = $this->createBookCopy($this->createBook());

        $this->actingAs($user)
            ->delete(route('book-copies.destroy', $copy))
            ->assertRedirect(route('book-copies.index'))
            ->assertSessionHas('success', 'Book copy deleted successfully.');

        $this->assertDatabaseMissing('book_copies', ['id' => $copy->id]);
    }

    public function test_book_copy_with_borrowing_history_cannot_be_deleted(): void
    {
        $user = $this->userWithPermissions(['book_copies.delete']);
        $copy = $this->createBookCopy($this->createBook());
        $borrowing = $this->createBorrowing($copy, [
            'status' => 'returned',
            'returned_date' => '2026-09-20',
        ]);

        $this->actingAs($user)
            ->from(route('book-copies.index'))
            ->delete(route('book-copies.destroy', $copy))
            ->assertRedirect(route('book-copies.index'))
            ->assertSessionHasErrors([
                'delete' => 'This book copy cannot be deleted because borrowing history exists.',
            ]);

        $this->assertModelExists($copy);
        $this->assertModelExists($borrowing);
    }

    public function test_user_without_book_copy_delete_permission_receives_forbidden_response(): void
    {
        $user = $this->userWithPermissions(['book_copies.view']);
        $copy = $this->createBookCopy($this->createBook());

        $this->actingAs($user)
            ->delete(route('book-copies.destroy', $copy))
            ->assertForbidden();

        $this->assertModelExists($copy);
    }

    public function test_retired_copies_are_excluded_from_available_for_borrowing_scope(): void
    {
        $book = $this->createBook();
        $availableCopy = $this->createBookCopy($book, ['copy_number' => 'AVAILABLE-001']);
        $retiredCopy = $this->createBookCopy($book, [
            'copy_number' => 'RETIRED-001',
            'status' => 'retired',
        ]);

        $this->assertSame(
            [$availableCopy->id],
            BookCopy::query()->availableForBorrowing()->pluck('id')->all()
        );
        $this->assertModelExists($retiredCopy);
    }

    public function test_book_copy_permission_migration_grants_delete_only_to_administrators(): void
    {
        $roles = collect([
            'Super Administrator',
            'School Administrator',
            'Librarian',
        ])->mapWithKeys(fn (string $name): array => [
            $name => Role::create(['name' => $name]),
        ]);

        $migration = require database_path(
            'migrations/2026_10_02_080515_add_library_book_copies_permissions.php'
        );
        $migration->up();

        foreach (['Super Administrator', 'School Administrator'] as $roleName) {
            $this->assertSame(
                4,
                $roles[$roleName]->fresh()->permissions()
                    ->whereIn('name', [
                        'book_copies.view',
                        'book_copies.create',
                        'book_copies.update',
                        'book_copies.delete',
                    ])
                    ->count()
            );
        }

        $librarianPermissions = $roles['Librarian']->fresh()->permissions()
            ->whereIn('name', [
                'book_copies.view',
                'book_copies.create',
                'book_copies.update',
                'book_copies.delete',
            ])
            ->pluck('name')
            ->sort()
            ->values()
            ->all();

        $this->assertSame([
            'book_copies.create',
            'book_copies.update',
            'book_copies.view',
        ], $librarianPermissions);
    }

    private function userWithPermissions(array $permissionNames): User
    {
        $user = User::factory()->create();
        $role = Role::create(['name' => 'Test Book Copy Librarian']);

        foreach ($permissionNames as $permissionName) {
            $permission = Permission::firstOrCreate(['name' => $permissionName]);
            $role->permissions()->attach($permission->id);
        }

        $user->roles()->attach($role->id);

        return $user;
    }

    private function createBook(string $title = 'Library Book'): Book
    {
        return Book::create([
            'title' => $title,
            'author' => 'A. Writer',
            'status' => 'active',
        ]);
    }

    private function createBookCopy(Book $book, array $overrides = []): BookCopy
    {
        return BookCopy::create(array_replace([
            'book_id' => $book->id,
            'copy_number' => 'COPY-'.$book->id.'-'.(BookCopy::query()->count() + 1),
            'barcode' => null,
            'condition' => 'good',
            'status' => 'available',
            'acquisition_date' => '2026-01-01',
            'purchase_price' => 12.50,
            'remarks' => 'Catalogued copy.',
        ], $overrides));
    }

    private function createBorrowing(BookCopy $bookCopy, array $overrides = []): BookBorrowing
    {
        return BookBorrowing::create(array_replace([
            'book_copy_id' => $bookCopy->id,
            'borrowed_date' => '2026-09-01',
            'due_date' => '2026-09-15',
            'status' => 'borrowed',
        ], $overrides));
    }

    private function validPayload(Book $book, array $overrides = []): array
    {
        return array_replace([
            'book_id' => $book->id,
            'copy_number' => 'COPY-001',
            'barcode' => 'BARCODE-001',
            'condition' => 'good',
            'status' => 'available',
            'acquisition_date' => '2026-01-01',
            'purchase_price' => '12.50',
            'remarks' => 'Catalogued copy.',
        ], $overrides);
    }
}
