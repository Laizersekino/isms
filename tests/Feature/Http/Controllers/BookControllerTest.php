<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Book;
use App\Models\BookCopy;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_users_are_redirected_to_login(): void
    {
        $this->get(route('books.index'))->assertRedirect(route('login'));
        $this->get(route('books.create'))->assertRedirect(route('login'));
    }

    public function test_user_without_books_view_permission_receives_forbidden_response(): void
    {
        $user = $this->userWithPermissions([]);

        $this->actingAs($user)
            ->get(route('books.index'))
            ->assertForbidden();
    }

    public function test_books_view_permission_renders_paginated_index_and_search_results(): void
    {
        $user = $this->userWithPermissions(['books.view']);
        $book = $this->createBook(['title' => 'The Reading Garden']);
        $this->createBook(['title' => 'Mathematics Today', 'isbn' => '978-0-12345-678-9']);

        $this->actingAs($user)
            ->get(route('books.index', ['search' => 'Reading']))
            ->assertSee('The Reading Garden')
            ->assertDontSee('Mathematics Today')
            ->assertSee(route('books.show', $book));
    }

    public function test_books_index_displays_pagination_for_more_than_one_page_of_books(): void
    {
        $user = $this->userWithPermissions(['books.view']);

        for ($index = 1; $index <= 16; $index++) {
            $this->createBook([
                'title' => 'Book '.$index,
                'isbn' => 'ISBN-'.$index,
            ]);
        }

        $this->actingAs($user)
            ->get(route('books.index'))
            ->assertSee('page=2');
    }

    public function test_books_view_permission_renders_details_with_copy_count(): void
    {
        $user = $this->userWithPermissions(['books.view']);
        $book = $this->createBook();
        $this->createBookCopy($book);

        $this->actingAs($user)
            ->get(route('books.show', $book))
            ->assertSee($book->title)
            ->assertSee('Copies: 1');
    }

    public function test_books_create_permission_renders_create_form(): void
    {
        $user = $this->userWithPermissions(['books.create']);

        $this->actingAs($user)
            ->get(route('books.create'))
            ->assertSee('Add Book')
            ->assertSee('name="title"', false)
            ->assertSee('name="author"', false);
    }

    public function test_books_update_permission_renders_edit_form(): void
    {
        $user = $this->userWithPermissions(['books.update']);
        $book = $this->createBook();

        $this->actingAs($user)
            ->get(route('books.edit', $book))
            ->assertSee('Edit Book')
            ->assertSee('The Reading Garden');
    }

    public function test_books_create_permission_stores_a_valid_book(): void
    {
        $user = $this->userWithPermissions(['books.create']);

        $this->actingAs($user)
            ->post(route('books.store'), $this->validPayload())
            ->assertRedirect(route('books.show', Book::query()->first()))
            ->assertSessionHas('success', 'Book created successfully.');

        $this->assertDatabaseHas('books', [
            'title' => 'The Reading Garden',
            'isbn' => '978-0-12345-678-9',
            'author' => 'A. Writer',
            'publication_year' => 2020,
            'status' => 'active',
        ]);
    }

    public function test_book_store_rejects_missing_required_fields_invalid_status_and_year(): void
    {
        $user = $this->userWithPermissions(['books.create']);
        $invalidCases = [
            ['title', ['title' => '']],
            ['author', ['author' => '']],
            ['status', ['status' => 'deleted']],
            ['publication_year', ['publication_year' => 999]],
            ['publication_year', ['publication_year' => now()->year + 1]],
        ];

        $this->actingAs($user);

        foreach ($invalidCases as [$errorField, $overrides]) {
            $this->post(route('books.store'), array_replace($this->validPayload(), $overrides))
                ->assertSessionHasErrors($errorField);
        }

        $this->assertDatabaseCount('books', 0);
    }

    public function test_book_store_rejects_duplicate_isbn(): void
    {
        $user = $this->userWithPermissions(['books.create']);
        $this->createBook(['isbn' => '978-0-12345-678-9']);

        $this->actingAs($user)
            ->from(route('books.create'))
            ->post(route('books.store'), $this->validPayload())
            ->assertRedirect(route('books.create'))
            ->assertSessionHasErrors('isbn');

        $this->assertDatabaseCount('books', 1);
    }

    public function test_books_update_permission_updates_a_book_and_allows_its_existing_isbn(): void
    {
        $user = $this->userWithPermissions(['books.update']);
        $book = $this->createBook(['isbn' => '978-0-12345-678-9']);

        $this->actingAs($user)
            ->put(route('books.update', $book), array_replace(
                $this->validPayload(),
                ['title' => 'Updated title']
            ))
            ->assertRedirect(route('books.show', $book))
            ->assertSessionHas('success', 'Book updated successfully.');

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => 'Updated title',
            'isbn' => '978-0-12345-678-9',
        ]);
    }

    public function test_books_delete_permission_deletes_a_book_without_copies(): void
    {
        $user = $this->userWithPermissions(['books.delete']);
        $book = $this->createBook();

        $this->actingAs($user)
            ->delete(route('books.destroy', $book))
            ->assertRedirect(route('books.index'))
            ->assertSessionHas('success', 'Book deleted successfully.');

        $this->assertDatabaseMissing('books', ['id' => $book->id]);
    }

    public function test_books_delete_permission_cannot_delete_a_book_with_copies(): void
    {
        $user = $this->userWithPermissions(['books.delete']);
        $book = $this->createBook();
        $copy = $this->createBookCopy($book);

        $this->actingAs($user)
            ->from(route('books.index'))
            ->delete(route('books.destroy', $book))
            ->assertRedirect(route('books.index'))
            ->assertSessionHasErrors([
                'delete' => 'This book cannot be deleted because copies exist. Change its status to archived to retain its library history.',
            ]);

        $this->assertModelExists($book);
        $this->assertModelExists($copy);
    }

    public function test_user_without_books_delete_permission_receives_forbidden_response(): void
    {
        $user = $this->userWithPermissions(['books.view']);
        $book = $this->createBook();

        $this->actingAs($user)
            ->delete(route('books.destroy', $book))
            ->assertForbidden();

        $this->assertModelExists($book);
    }

    public function test_library_permission_migration_grants_books_permissions_to_expected_roles(): void
    {
        $roles = collect([
            'Super Administrator',
            'School Administrator',
            'Librarian',
        ])->mapWithKeys(fn (string $name): array => [
            $name => Role::create(['name' => $name]),
        ]);

        $migration = require database_path(
            'migrations/2026_10_02_075220_add_library_books_permissions.php'
        );
        $migration->up();

        foreach (['Super Administrator', 'School Administrator'] as $roleName) {
            $this->assertSame(
                4,
                $roles[$roleName]->fresh()->permissions()
                    ->whereIn('name', [
                        'books.view',
                        'books.create',
                        'books.update',
                        'books.delete',
                    ])
                    ->count()
            );
        }

        $librarianPermissions = $roles['Librarian']->fresh()->permissions()
            ->whereIn('name', [
                'books.view',
                'books.create',
                'books.update',
                'books.delete',
            ])
            ->pluck('name')
            ->all();

        $this->assertSame(['books.create', 'books.update', 'books.view'], $librarianPermissions);
    }

    private function userWithPermissions(array $permissionNames): User
    {
        $user = User::factory()->create();
        $role = Role::create(['name' => 'Test Librarian']);

        foreach ($permissionNames as $permissionName) {
            $permission = Permission::firstOrCreate(['name' => $permissionName]);
            $role->permissions()->attach($permission->id);
        }

        $user->roles()->attach($role->id);

        return $user;
    }

    private function createBook(array $overrides = []): Book
    {
        return Book::create(array_replace([
            'title' => 'The Reading Garden',
            'isbn' => null,
            'author' => 'A. Writer',
            'publisher' => 'School Press',
            'category' => 'Literature',
            'edition' => 'First',
            'publication_year' => 2020,
            'description' => 'A sample book description.',
            'status' => 'active',
        ], $overrides));
    }

    private function createBookCopy(Book $book): BookCopy
    {
        return BookCopy::create([
            'book_id' => $book->id,
            'copy_number' => 'COPY-'.$book->id,
            'condition' => 'good',
            'status' => 'available',
        ]);
    }

    private function validPayload(): array
    {
        return [
            'title' => 'The Reading Garden',
            'isbn' => '978-0-12345-678-9',
            'author' => 'A. Writer',
            'publisher' => 'School Press',
            'category' => 'Literature',
            'edition' => 'First',
            'publication_year' => 2020,
            'description' => 'A sample book description.',
            'status' => 'active',
        ];
    }
}
