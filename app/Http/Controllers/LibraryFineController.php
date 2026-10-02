<?php

namespace App\Http\Controllers;

use App\Models\LibraryFine;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class LibraryFineController extends Controller implements HasMiddleware
{
    private const STATUSES = ['unpaid', 'paid', 'waived'];

    public static function middleware(): array
    {
        return [
            new Middleware('permission:fines.view', only: ['index', 'show']),
            new Middleware('permission:fines.pay', only: ['pay']),
            new Middleware('permission:fines.waive', only: ['waive']),
        ];
    }

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::in(self::STATUSES)],
            'student_id' => ['nullable', 'integer', 'exists:students,id'],
            'teacher_id' => ['nullable', 'integer', 'exists:teachers,id'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $fines = LibraryFine::query()
            ->with([
                'borrowing.bookCopy.book',
                'borrowing.student',
                'borrowing.teacher',
            ])
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['student_id'] ?? null, function (Builder $query, int $studentId): void {
                $query->whereHas('borrowing', fn (Builder $borrowingQuery) => $borrowingQuery->where('student_id', $studentId));
            })
            ->when($filters['teacher_id'] ?? null, function (Builder $query, int $teacherId): void {
                $query->whereHas('borrowing', fn (Builder $borrowingQuery) => $borrowingQuery->where('teacher_id', $teacherId));
            })
            ->when($filters['date_from'] ?? null, fn (Builder $query, string $date) => $query->whereDate('issued_date', '>=', $date))
            ->when($filters['date_to'] ?? null, fn (Builder $query, string $date) => $query->whereDate('issued_date', '<=', $date))
            ->latest('issued_date')
            ->paginate(15)
            ->withQueryString();

        return view('library_fines.index', compact('fines', 'filters'));
    }

    public function show(LibraryFine $libraryFine): View
    {
        $libraryFine->load([
            'borrowing.bookCopy.book',
            'borrowing.student',
            'borrowing.teacher',
            'recordedBy',
            'paidBy',
            'waivedBy',
        ]);

        return view('library_fines.show', compact('libraryFine'));
    }

    public function pay(LibraryFine $libraryFine): RedirectResponse
    {
        DB::transaction(function () use ($libraryFine): void {
            $fine = LibraryFine::query()
                ->lockForUpdate()
                ->findOrFail($libraryFine->id);

            if ($fine->status !== 'unpaid') {
                throw ValidationException::withMessages([
                    'fine' => 'Only an unpaid fine can be marked as paid.',
                ]);
            }

            $fine->update([
                'status' => 'paid',
                'paid_date' => today()->toDateString(),
                'paid_by' => auth()->id(),
                'recorded_by' => auth()->id(),
            ]);
        });

        return redirect()
            ->route('library-fines.show', $libraryFine)
            ->with('success', 'Fine payment recorded successfully.');
    }

    public function waive(Request $request, LibraryFine $libraryFine): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        DB::transaction(function () use ($libraryFine, $validated): void {
            $fine = LibraryFine::query()
                ->lockForUpdate()
                ->findOrFail($libraryFine->id);

            if ($fine->status !== 'unpaid') {
                throw ValidationException::withMessages([
                    'fine' => 'Only an unpaid fine can be waived.',
                ]);
            }

            $fine->update([
                'status' => 'waived',
                'remarks' => $validated['reason'],
                'recorded_by' => auth()->id(),
                'waived_by' => auth()->id(),
                'waived_date' => today()->toDateString(),
            ]);
        });

        return redirect()
            ->route('library-fines.show', $libraryFine)
            ->with('success', 'Fine waived successfully.');
    }
}
