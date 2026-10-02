<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\ClassRoom;
use App\Models\FeeStructure;
use App\Models\StudentEnrollment;
use App\Models\StudentFee;
use App\Models\Term;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StudentFeeController extends Controller implements HasMiddleware
{
    private const STATUSES = ['unpaid', 'partially_paid', 'paid', 'waived'];

    public static function middleware(): array
    {
        return [
            new Middleware('permission:student_fees.view', only: ['index', 'show']),
            new Middleware('permission:student_fees.generate', only: ['generateForm', 'generate']),
            new Middleware('permission:student_fees.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'student_id' => ['nullable', 'integer', 'exists:students,id'],
            'fee_structure_id' => ['nullable', 'integer', 'exists:fee_structures,id'],
            'status' => ['nullable', Rule::in(self::STATUSES)],
            'term_id' => ['nullable', 'integer', 'exists:terms,id'],
            'academic_year_id' => ['nullable', 'integer', 'exists:academic_years,id'],
            'search' => ['nullable', 'string', 'max:255'],
        ]);
        $search = trim($filters['search'] ?? '');

        $studentFees = StudentFee::query()
            ->with(['student', 'feeStructure.academicYear', 'feeStructure.term', 'feeStructureItem'])
            ->when($filters['student_id'] ?? null, fn (Builder $query, int $id) => $query->where('student_id', $id))
            ->when($filters['fee_structure_id'] ?? null, fn (Builder $query, int $id) => $query->where('fee_structure_id', $id))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['term_id'] ?? null, function (Builder $query, int $id): void {
                $query->whereHas('feeStructure', fn (Builder $structureQuery) => $structureQuery->where('term_id', $id));
            })
            ->when($filters['academic_year_id'] ?? null, function (Builder $query, int $id): void {
                $query->whereHas('feeStructure', fn (Builder $structureQuery) => $structureQuery->where('academic_year_id', $id));
            })
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->whereHas('student', function (Builder $studentQuery) use ($search): void {
                        $studentQuery->where('admission_number', 'like', '%'.$search.'%')
                            ->orWhere('first_name', 'like', '%'.$search.'%')
                            ->orWhere('last_name', 'like', '%'.$search.'%');
                    })->orWhereHas('feeStructure', function (Builder $structureQuery) use ($search): void {
                        $structureQuery->where('name', 'like', '%'.$search.'%');
                    })->orWhereHas('feeStructureItem', function (Builder $itemQuery) use ($search): void {
                        $itemQuery->where('name', 'like', '%'.$search.'%');
                    });
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $academicYears = AcademicYear::query()->orderByDesc('name')->get(['id', 'name']);
        $terms = Term::query()->with('academicYear:id,name')->orderBy('start_date')->get(['id', 'academic_year_id', 'name']);
        $feeStructures = FeeStructure::query()->orderBy('name')->get(['id', 'name']);

        return view('student_fees.index', compact(
            'studentFees',
            'filters',
            'search',
            'academicYears',
            'terms',
            'feeStructures'
        ));
    }

    public function show(StudentFee $studentFee): View
    {
        $studentFee->load([
            'student',
            'feeStructure.academicYear',
            'feeStructure.term',
            'feeStructure.classRoom',
            'feeStructureItem',
            'createdBy',
            'payments.recordedBy',
            'payments.reversedBy',
        ]);

        return view('student_fees.show', compact('studentFee'));
    }

    public function generateForm(): View
    {
        $academicYears = AcademicYear::query()->orderByDesc('name')->get(['id', 'name']);
        $terms = Term::query()->with('academicYear:id,name')->orderBy('start_date')->get(['id', 'academic_year_id', 'name']);
        $classes = ClassRoom::query()->orderBy('name')->get(['id', 'name']);

        return view('student_fees.generate', compact('academicYears', 'terms', 'classes'));
    }

    public function generate(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'academic_year_id' => ['required', 'integer', 'exists:academic_years,id'],
            'term_id' => [
                'required',
                'integer',
                Rule::exists('terms', 'id')->where(
                    fn ($query) => $query->where('academic_year_id', $request->input('academic_year_id'))
                ),
            ],
            'class_id' => ['required', 'integer', 'exists:classes,id'],
            'due_date' => ['required', 'date', 'after_or_equal:today'],
        ]);

        $result = DB::transaction(function () use ($validated): array {
            $feeStructure = FeeStructure::query()
                ->active()
                ->where('academic_year_id', $validated['academic_year_id'])
                ->where('term_id', $validated['term_id'])
                ->where('class_id', $validated['class_id'])
                ->lockForUpdate()
                ->with('items')
                ->first();

            if ($feeStructure === null) {
                throw ValidationException::withMessages([
                    'fee_structure' => 'No active fee structure for this class/term/year.',
                ]);
            }

            $items = $feeStructure->items;
            $created = 0;
            $attempted = 0;
            $timestamp = now();

            StudentEnrollment::query()
                ->where('academic_year_id', $validated['academic_year_id'])
                ->where('class_id', $validated['class_id'])
                ->where('status', 'active')
                ->select(['id', 'student_id'])
                ->orderBy('id')
                ->chunkById(200, function ($enrollments) use (
                    $items,
                    $feeStructure,
                    $validated,
                    $timestamp,
                    &$created,
                    &$attempted
                ): void {
                    $rows = [];

                    foreach ($enrollments as $enrollment) {
                        foreach ($items as $item) {
                            $rows[] = [
                                'student_id' => $enrollment->student_id,
                                'fee_structure_id' => $feeStructure->id,
                                'fee_structure_item_id' => $item->id,
                                'amount' => $item->amount,
                                'paid_amount' => '0.00',
                                'balance' => $item->amount,
                                'status' => 'unpaid',
                                'due_date' => $validated['due_date'],
                                'created_by' => auth()->id(),
                                'created_at' => $timestamp,
                                'updated_at' => $timestamp,
                            ];
                        }
                    }

                    $attempted += count($rows);

                    if ($rows !== []) {
                        $created += DB::table('student_fees')->insertOrIgnore($rows);
                    }
                });

            return [
                'created' => $created,
                'skipped' => $attempted - $created,
            ];
        });

        return redirect()
            ->route('student-fees.index')
            ->with(
                'success',
                "Student fees generated: {$result['created']} created, {$result['skipped']} skipped (already existed)."
            );
    }

    public function destroy(StudentFee $studentFee): RedirectResponse
    {
        DB::transaction(function () use ($studentFee): void {
            $lockedFee = StudentFee::query()
                ->lockForUpdate()
                ->findOrFail($studentFee->id);

            if (Schema::hasTable('payments') && $lockedFee->payments()->exists()) {
                throw ValidationException::withMessages([
                    'delete' => 'This student fee cannot be deleted because payment records exist.',
                ]);
            }

            $lockedFee->delete();
        });

        return redirect()
            ->route('student-fees.index')
            ->with('success', 'Student fee deleted successfully.');
    }
}
