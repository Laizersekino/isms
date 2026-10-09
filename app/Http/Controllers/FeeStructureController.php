<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\ClassRoom;
use App\Models\FeeStructure;
use App\Models\Term;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class FeeStructureController extends Controller implements HasMiddleware
{
    private const STATUSES = ['draft', 'active', 'archived'];
    private const PAYMENT_PLANS = ['full', 'termly', 'monthly', 'custom'];

    public static function middleware(): array
    {
        return [
            new Middleware('permission:fee_structures.view', only: ['index', 'show']),
            new Middleware('permission:fee_structures.create', only: ['create', 'store']),
            new Middleware('permission:fee_structures.update', only: ['edit', 'update']),
            new Middleware('permission:fee_structures.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'academic_year_id' => ['nullable', 'integer', 'exists:academic_years,id'],
            'term_id' => ['nullable', 'integer', 'exists:terms,id'],
            'class_id' => ['nullable', 'integer', 'exists:classes,id'],
            'status' => ['nullable', Rule::in(self::STATUSES)],
        ]);

        $feeStructures = FeeStructure::query()
            ->with(['academicYear', 'term', 'classRoom'])
            ->when($filters['academic_year_id'] ?? null, fn (Builder $query, int $id) => $query->where('academic_year_id', $id))
            ->when($filters['term_id'] ?? null, fn (Builder $query, int $id) => $query->where('term_id', $id))
            ->when($filters['class_id'] ?? null, fn (Builder $query, int $id) => $query->where('class_id', $id))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->orderByDesc('academic_year_id')
            ->orderBy('term_id')
            ->orderBy('class_id')
            ->paginate(15)
            ->withQueryString();

        $academicYears = AcademicYear::query()->orderByDesc('name')->get(['id', 'name']);
        $terms = Term::query()->with('academicYear:id,name')->orderBy('start_date')->get(['id', 'academic_year_id', 'name']);
        $classes = ClassRoom::query()->orderBy('name')->get(['id', 'name']);

        return view('fee_structures.index', compact(
            'feeStructures',
            'filters',
            'academicYears',
            'terms',
            'classes'
        ));
    }

    public function create(): View
    {
        return view('fee_structures.create', $this->formOptions());
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules($request));
        $this->assertCombinationAvailable(
            (int) $validated['academic_year_id'],
            (int) $validated['term_id'],
            (int) $validated['class_id']
        );

        $feeStructure = DB::transaction(function () use ($validated): FeeStructure {
            $feeStructure = FeeStructure::create([
                ...$this->structureAttributes($validated),
                'total_amount' => 0,
                'created_by' => auth()->id(),
            ]);
            $feeStructure->items()->createMany($validated['items']);
            $feeStructure->recalculateTotal();
            $feeStructure->refresh();
            $this->applyInstallmentCalculation($feeStructure, $validated);

            return $feeStructure;
        });

        return redirect()
            ->route('fee-structures.show', $feeStructure)
            ->with('success', 'Fee structure created successfully.');
    }

    public function show(FeeStructure $feeStructure): View
    {
        $feeStructure->load(['academicYear', 'term', 'classRoom', 'items', 'createdBy']);

        return view('fee_structures.show', compact('feeStructure'));
    }

    public function edit(FeeStructure $feeStructure): View
    {
        $feeStructure->load('items');

        return view('fee_structures.edit', [
            ...$this->formOptions(),
            'feeStructure' => $feeStructure,
        ]);
    }

    public function update(Request $request, FeeStructure $feeStructure): RedirectResponse
    {
        $validated = $request->validate($this->rules($request, $feeStructure));
        $this->assertCombinationAvailable(
            (int) $validated['academic_year_id'],
            (int) $validated['term_id'],
            (int) $validated['class_id'],
            $feeStructure->id
        );

        DB::transaction(function () use ($validated, $feeStructure): void {
            $lockedStructure = FeeStructure::query()
                ->lockForUpdate()
                ->findOrFail($feeStructure->id);
            $lockedStructure->update($this->structureAttributes($validated));
            $lockedStructure->items()->delete();
            $lockedStructure->items()->createMany($validated['items']);
            $lockedStructure->recalculateTotal();
            $lockedStructure->refresh();
            $this->applyInstallmentCalculation($lockedStructure, $validated);
        });

        return redirect()
            ->route('fee-structures.show', $feeStructure)
            ->with('success', 'Fee structure updated successfully.');
    }

    public function destroy(FeeStructure $feeStructure): RedirectResponse
    {
        DB::transaction(function () use ($feeStructure): void {
            $lockedStructure = FeeStructure::query()
                ->lockForUpdate()
                ->findOrFail($feeStructure->id);

            if ($lockedStructure->hasStudentFees()) {
                throw ValidationException::withMessages([
                    'delete' => 'This fee structure cannot be archived because student fee records exist.',
                ]);
            }

            $lockedStructure->delete();
        });

        return redirect()
            ->route('fee-structures.index')
            ->with('success', 'Fee structure archived successfully.');
    }

    private function formOptions(): array
    {
        return [
            'academicYears' => AcademicYear::query()->orderByDesc('name')->get(['id', 'name']),
            'terms' => Term::query()->with('academicYear:id,name')->orderBy('start_date')->get(['id', 'academic_year_id', 'name']),
            'classes' => ClassRoom::query()->orderBy('name')->get(['id', 'name']),
        ];
    }

    private function rules(Request $request, ?FeeStructure $feeStructure = null): array
    {
        return [
            'academic_year_id' => ['required', 'integer', 'exists:academic_years,id'],
            'term_id' => [
                'required',
                'integer',
                Rule::exists('terms', 'id')->where(
                    fn ($query) => $query->where('academic_year_id', $request->input('academic_year_id'))
                ),
            ],
            'class_id' => [
                'required',
                'integer',
                'exists:classes,id',
                Rule::unique('fee_structures', 'class_id')
                    ->where(fn ($query) => $query
                        ->where('academic_year_id', $request->input('academic_year_id'))
                        ->where('term_id', $request->input('term_id')))
                    ->ignore($feeStructure?->id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', Rule::in(self::STATUSES)],
            'payment_plan' => ['required', Rule::in(self::PAYMENT_PLANS)],
            'installment_count' => ['nullable', 'integer', 'min:1', 'max:12'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.name' => ['required', 'string', 'max:100'],
            'items.*.amount' => ['required', 'numeric', 'min:0'],
            'items.*.is_mandatory' => ['sometimes', 'boolean'],
            'items.*.due_date' => ['nullable', 'date'],
            'items.*.order' => ['nullable', 'integer'],
            'items.*.description' => ['nullable', 'string', 'max:500'],
        ];
    }

    private function structureAttributes(array $validated): array
    {
        return [
            'academic_year_id' => $validated['academic_year_id'],
            'term_id' => $validated['term_id'],
            'class_id' => $validated['class_id'],
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'status' => $validated['status'],
            'payment_plan' => $validated['payment_plan'],
            'installment_count' => $validated['installment_count'] ?? 3,
        ];
    }

    private function applyInstallmentCalculation(FeeStructure $feeStructure, array $validated): void
    {
        $plan = $validated['payment_plan'];
        $count = (int) ($validated['installment_count'] ?? 3);

        if ($plan === 'full') {
            $count = 1;
        }

        $feeStructure->update([
            'installment_count' => $count,
            'installment_amount' => $count > 0
                ? round((float) $feeStructure->total_amount / $count, 2)
                : null,
        ]);
    }

    private function assertCombinationAvailable(
        int $academicYearId,
        int $termId,
        int $classId,
        ?int $ignoreId = null
    ): void {
        $query = FeeStructure::withTrashed()
            ->where('academic_year_id', $academicYearId)
            ->where('term_id', $termId)
            ->where('class_id', $classId);

        if ($ignoreId !== null) {
            $query->whereKeyNot($ignoreId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'class_id' => 'A fee structure already exists for this academic year, term, and class.',
            ]);
        }
    }
}