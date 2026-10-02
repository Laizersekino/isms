<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BookController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:books.view', only: ['index', 'show']),
            new Middleware('permission:books.create', only: ['create', 'store']),
            new Middleware('permission:books.update', only: ['edit', 'update']),
            new Middleware('permission:books.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request): View
    {
        $search = $request->string('search')->trim()->toString();

        $books = Book::query()
            ->withCount('copies')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('title', 'like', '%'.$search.'%')
                        ->orWhere('isbn', 'like', '%'.$search.'%')
                        ->orWhere('author', 'like', '%'.$search.'%')
                        ->orWhere('publisher', 'like', '%'.$search.'%')
                        ->orWhere('category', 'like', '%'.$search.'%');
                });
            })
            ->orderBy('title')
            ->paginate(15)
            ->withQueryString();

        return view('books.index', compact('books', 'search'));
    }

    public function create(): View
    {
        return view('books.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $book = Book::create($request->validate($this->rules()));

        return redirect()
            ->route('books.show', $book)
            ->with('success', 'Book created successfully.');
    }

    public function show(Book $book): View
    {
        $book->loadCount('copies');

        return view('books.show', compact('book'));
    }

    public function edit(Book $book): View
    {
        return view('books.edit', compact('book'));
    }

    public function update(Request $request, Book $book): RedirectResponse
    {
        $book->update($request->validate($this->rules($book)));

        return redirect()
            ->route('books.show', $book)
            ->with('success', 'Book updated successfully.');
    }

    public function destroy(Book $book): RedirectResponse
    {
        DB::transaction(function () use ($book): void {
            $book = Book::query()->lockForUpdate()->findOrFail($book->id);

            if ($book->copies()->exists()) {
                throw ValidationException::withMessages([
                    'delete' => 'This book cannot be deleted because copies exist. Change its status to archived to retain its library history.',
                ]);
            }

            $book->delete();
        });

        return redirect()
            ->route('books.index')
            ->with('success', 'Book deleted successfully.');
    }

    private function rules(?Book $book = null): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'isbn' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('books', 'isbn')->ignore($book?->id),
            ],
            'author' => ['required', 'string', 'max:255'],
            'publisher' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
            'edition' => ['nullable', 'string', 'max:50'],
            'publication_year' => [
                'nullable',
                'integer',
                'between:1000,'.now()->year,
            ],
            'description' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', Rule::in(['active', 'inactive', 'archived'])],
        ];
    }
}
