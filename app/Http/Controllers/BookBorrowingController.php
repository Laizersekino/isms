<?php

namespace App\Http\Controllers;

use App\Models\BookBorrowing;
use App\Models\BookCopy;
use App\Models\LibraryFine;
use App\Models\Student;
use App\Models\Teacher;
use App\Services\LibraryFineCalculator;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BookBorrowingController extends Controller implements HasMiddleware
{
    private const STATUSES = ['borrowed', 'returned'];

    private const BORROWER_TYPES = ['student', 'teacher'];

    public static function middleware(): array
    {
        return [
            new Middleware('permission:borrowings.view', only: ['index', 'show']),
            new Middleware('permission:borrowings.issue', only: ['create', 'store']),
            new Middleware('permission:borrowings.return', only: ['returnBook']),
            new Middleware('permission:borrowings.renew', only: ['renew']),
        ];
    }

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::in(self::STATUSES)],
            'overdue' => ['nullable', 'boolean'],
            'student_id' => ['nullable', 'integer', 'exists:students,id'],
            'teacher_id' => ['nullable', 'integer', 'exists:teachers,id'],
        ]);

        $borrowings = BookBorrowing::query()
            ->with(['bookCopy.book', 'student', 'teacher'])
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when(($filters['overdue'] ?? false), function (Builder $query): void {
                $query->where('status', 'borrowed')
                    ->whereDate('due_date', '<', today()->toDateString());
            })
            ->when($filters['student_id'] ?? null, fn (Builder $query, int $studentId) => $query->where('student_id', $studentId))
            ->when($filters['teacher_id'] ?? null, fn (Builder $query, int $teacherId) => $query->where('teacher_id', $teacherId))
            ->latest('borrowed_date')
            ->paginate(15)
            ->withQueryString();

        return view('book_borrowings.index', compact('borrowings', 'filters'));
    }

    public function create(): View
    {
        $availableCopies = BookCopy::query()
            ->availableForBorrowing()
            ->with('book:id,title')
            ->orderBy('copy_number')
            ->get();
        $students = Student::query()
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get(['id', 'admission_number', 'first_name', 'last_name']);
        $teachers = Teacher::query()
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get(['id', 'employee_number', 'first_name', 'last_name']);

        return view('book_borrowings.create', compact('availableCopies', 'students', 'teachers'));
    }

    public function store(Request $request): RedirectResponse
    {
        $borrowerType = $request->input('borrower_type');
        $borrowerTable = $borrowerType === 'teacher' ? 'teachers' : 'students';
        $validated = $request->validate([
            'book_copy_id' => ['required', 'integer', 'exists:book_copies,id'],
            'borrower_type' => ['required', Rule::in(self::BORROWER_TYPES)],
            'borrower_id' => ['required', 'integer', Rule::exists($borrowerTable, 'id')],
            'borrowed_date' => ['required', 'date', 'before_or_equal:today'],
            'due_date' => ['required', 'date', 'after:borrowed_date'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        $bookBorrowing = DB::transaction(function () use ($validated): BookBorrowing {
            $bookCopy = BookCopy::query()
                ->lockForUpdate()
                ->findOrFail($validated['book_copy_id']);

            if ($bookCopy->status !== 'available') {
                throw ValidationException::withMessages([
                    'book_copy_id' => 'This book copy is no longer available for borrowing.',
                ]);
            }

            $this->lockBorrower($validated['borrower_type'], (int) $validated['borrower_id']);

            if ($this->borrowerHasOverdueBorrowings(
                $validated['borrower_type'],
                (int) $validated['borrower_id']
            )) {
                throw ValidationException::withMessages([
                    'borrower_id' => 'This borrower has overdue books and cannot borrow another book.',
                ]);
            }

            $maximumBooks = (int) config('library.max_books_per_user', 3);
            if ($this->activeBorrowingCount(
                $validated['borrower_type'],
                (int) $validated['borrower_id']
            ) >= $maximumBooks) {
                throw ValidationException::withMessages([
                    'borrower_id' => "This borrower has reached the limit of {$maximumBooks} active books.",
                ]);
            }

            $bookBorrowing = BookBorrowing::create([
                'book_copy_id' => $bookCopy->id,
                'student_id' => $validated['borrower_type'] === 'student' ? $validated['borrower_id'] : null,
                'teacher_id' => $validated['borrower_type'] === 'teacher' ? $validated['borrower_id'] : null,
                'issued_by' => auth()->id(),
                'borrowed_date' => $validated['borrowed_date'],
                'due_date' => $validated['due_date'],
                'status' => 'borrowed',
                'remarks' => $validated['remarks'] ?? null,
            ]);

            $bookCopy->update(['status' => 'borrowed']);

            return $bookBorrowing;
        });

        return redirect()
            ->route('book-borrowings.show', $bookBorrowing)
            ->with('success', 'Book issued successfully.');
    }

    public function show(BookBorrowing $bookBorrowing): View
    {
        $bookBorrowing->load([
            'bookCopy.book',
            'student',
            'teacher',
            'issuedBy',
            'fines.recordedBy',
        ]);

        return view('book_borrowings.show', compact('bookBorrowing'));
    }

    public function returnBook(BookBorrowing $bookBorrowing, LibraryFineCalculator $fineCalculator): RedirectResponse
    {
        DB::transaction(function () use ($bookBorrowing, $fineCalculator): void {
            $lockedBorrowing = BookBorrowing::query()
                ->lockForUpdate()
                ->findOrFail($bookBorrowing->id);

            if ($lockedBorrowing->status !== 'borrowed' || $lockedBorrowing->returned_date !== null) {
                throw ValidationException::withMessages([
                    'borrowing' => 'This borrowing has already been returned.',
                ]);
            }

            $bookCopy = BookCopy::query()
                ->lockForUpdate()
                ->findOrFail($lockedBorrowing->book_copy_id);
            $returnedAt = CarbonImmutable::today();

            $lockedBorrowing->update([
                'returned_date' => $returnedAt->toDateString(),
                'status' => 'returned',
            ]);
            $bookCopy->update(['status' => 'available']);

            if ($lockedBorrowing->due_date->lt($returnedAt)) {
                LibraryFine::create([
                    'book_borrowing_id' => $lockedBorrowing->id,
                    'amount' => $fineCalculator->amountFor($lockedBorrowing, $returnedAt),
                    'reason' => 'overdue',
                    'status' => 'unpaid',
                    'issued_date' => $returnedAt->toDateString(),
                    'recorded_by' => auth()->id(),
                ]);
            }
        });

        return redirect()
            ->route('book-borrowings.show', $bookBorrowing)
            ->with('success', 'Book returned successfully.');
    }

    public function renew(BookBorrowing $bookBorrowing): RedirectResponse
    {
        DB::transaction(function () use ($bookBorrowing): void {
            $lockedBorrowing = BookBorrowing::query()
                ->lockForUpdate()
                ->findOrFail($bookBorrowing->id);

            if ($lockedBorrowing->status !== 'borrowed' || $lockedBorrowing->returned_date !== null) {
                throw ValidationException::withMessages([
                    'borrowing' => 'Only an active borrowing can be renewed.',
                ]);
            }

            $borrowerType = $lockedBorrowing->student_id !== null ? 'student' : 'teacher';
            $borrowerId = (int) ($lockedBorrowing->student_id ?? $lockedBorrowing->teacher_id);
            BookCopy::query()
                ->lockForUpdate()
                ->findOrFail($lockedBorrowing->book_copy_id);
            $this->lockBorrower($borrowerType, $borrowerId);

            if ($this->borrowerHasOverdueBorrowings($borrowerType, $borrowerId)) {
                throw ValidationException::withMessages([
                    'borrowing' => 'This borrower has overdue books and cannot renew this borrowing.',
                ]);
            }

            $maximumRenewals = (int) config('library.max_renewals', 2);
            if ($lockedBorrowing->renewal_count >= $maximumRenewals) {
                throw ValidationException::withMessages([
                    'borrowing' => "This borrowing has reached the maximum of {$maximumRenewals} renewals.",
                ]);
            }

            $reservedByAnotherBorrower = BookBorrowing::query()
                ->where('book_copy_id', $lockedBorrowing->book_copy_id)
                ->where('status', 'reserved')
                ->whereKeyNot($lockedBorrowing->id)
                ->exists();

            if ($reservedByAnotherBorrower) {
                throw ValidationException::withMessages([
                    'borrowing' => 'This book copy is reserved by another borrower and cannot be renewed.',
                ]);
            }

            $lockedBorrowing->update([
                'due_date' => $lockedBorrowing->due_date
                    ->addDays((int) config('library.loan_period_days', 14))
                    ->toDateString(),
                'renewal_count' => $lockedBorrowing->renewal_count + 1,
            ]);
        });

        return redirect()
            ->route('book-borrowings.show', $bookBorrowing)
            ->with('success', 'Borrowing renewed successfully.');
    }

    private function lockBorrower(string $borrowerType, int $borrowerId): void
    {
        $model = $borrowerType === 'student' ? Student::class : Teacher::class;
        $model::query()->lockForUpdate()->findOrFail($borrowerId);
    }

    private function borrowerHasOverdueBorrowings(string $borrowerType, int $borrowerId): bool
    {
        return $this->borrowerBorrowings($borrowerType, $borrowerId)
            ->where('status', 'borrowed')
            ->whereNull('returned_date')
            ->whereDate('due_date', '<', today()->toDateString())
            ->exists();
    }

    private function activeBorrowingCount(string $borrowerType, int $borrowerId): int
    {
        return $this->borrowerBorrowings($borrowerType, $borrowerId)
            ->where('status', 'borrowed')
            ->whereNull('returned_date')
            ->count();
    }

    private function borrowerBorrowings(string $borrowerType, int $borrowerId): Builder
    {
        $foreignKey = $borrowerType === 'student' ? 'student_id' : 'teacher_id';

        return BookBorrowing::query()->where($foreignKey, $borrowerId);
    }
}
