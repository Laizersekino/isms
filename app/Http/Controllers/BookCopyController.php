<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\BookCopy;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BookCopyController extends Controller implements HasMiddleware
{
    private const COPY_STATUSES = ['available', 'borrowed', 'lost', 'damaged', 'retired'];

    private const CONDITIONS = ['new', 'good', 'fair', 'poor', 'damaged'];

    public static function middleware(): array
    {
        return [
            new Middleware('permission:book_copies.view', only: ['index', 'show']),
            new Middleware('permission:book_copies.create', only: ['create', 'store']),
            new Middleware('permission:book_copies.update', only: ['edit', 'update']),
            new Middleware('permission:book_copies.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(self::COPY_STATUSES)],
        ]);
        $search = trim($filters['search'] ?? '');
        $status = $filters['status'] ?? '';

        $bookCopies = BookCopy::query()
            ->with('book:id,title')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('barcode', 'like', '%'.$search.'%')
                        ->orWhere('copy_number', 'like', '%'.$search.'%')
                        ->orWhereHas('book', function ($bookQuery) use ($search): void {
                            $bookQuery->where('title', 'like', '%'.$search.'%');
                        });
                });
            })
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->orderBy('copy_number')
            ->paginate(15)
            ->withQueryString();

        return view('book_copies.index', compact('bookCopies', 'search', 'status'));
    }

    public function create(): View
    {
        $books = Book::query()->orderBy('title')->get(['id', 'title', 'status']);

        return view('book_copies.create', compact('books'));
    }

    public function store(Request $request): RedirectResponse
    {
        $bookCopy = BookCopy::create($request->validate($this->rules($request)));

        return redirect()
            ->route('book-copies.show', $bookCopy)
            ->with('success', 'Book copy created successfully.');
    }

    public function show(BookCopy $bookCopy): View
    {
        $bookCopy->load([
            'book',
            'currentBorrowing.student',
            'currentBorrowing.teacher',
        ]);

        return view('book_copies.show', compact('bookCopy'));
    }

    public function edit(BookCopy $bookCopy): View
    {
        $books = Book::query()->orderBy('title')->get(['id', 'title', 'status']);

        return view('book_copies.edit', compact('bookCopy', 'books'));
    }

    public function update(Request $request, BookCopy $bookCopy): RedirectResponse
    {
        $bookCopy->update($request->validate($this->rules($request, $bookCopy)));

        return redirect()
            ->route('book-copies.show', $bookCopy)
            ->with('success', 'Book copy updated successfully.');
    }

    public function destroy(BookCopy $bookCopy): RedirectResponse
    {
        DB::transaction(function () use ($bookCopy): void {
            $bookCopy = BookCopy::query()->lockForUpdate()->findOrFail($bookCopy->id);

            if ($bookCopy->borrowings()->exists()) {
                throw ValidationException::withMessages([
                    'delete' => 'This book copy cannot be deleted because borrowing history exists.',
                ]);
            }

            $bookCopy->delete();
        });

        return redirect()
            ->route('book-copies.index')
            ->with('success', 'Book copy deleted successfully.');
    }

    private function rules(Request $request, ?BookCopy $bookCopy = null): array
    {
        return [
            'book_id' => ['required', 'exists:books,id'],
            'copy_number' => [
                'required',
                'string',
                'max:50',
                Rule::unique('book_copies', 'copy_number')
                    ->where(fn ($query) => $query->where('book_id', $request->input('book_id')))
                    ->ignore($bookCopy?->id),
            ],
            'barcode' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('book_copies', 'barcode')->ignore($bookCopy?->id),
            ],
            'condition' => ['required', Rule::in(self::CONDITIONS)],
            'status' => ['required', Rule::in(self::COPY_STATUSES)],
            'acquisition_date' => ['nullable', 'date'],
            'purchase_price' => ['nullable', 'numeric', 'min:0'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
