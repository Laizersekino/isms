<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Student;
use App\Models\StudentFee;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PaymentController extends Controller implements HasMiddleware
{
    private const STATUSES = ['completed', 'reversed'];

    private const PAYMENT_METHODS = [
        'cash',
        'bank_transfer',
        'mobile_money',
        'cheque',
        'other',
    ];

    public static function middleware(): array
    {
        return [
            new Middleware('permission:payments.view', only: ['index', 'show']),
            new Middleware('permission:payments.create', only: ['create', 'store']),
            new Middleware('permission:payments.reverse', only: ['reverse']),
        ];
    }

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'student_id' => ['nullable', 'integer', 'exists:students,id'],
            'student_fee_id' => ['nullable', 'integer', 'exists:student_fees,id'],
            'status' => ['nullable', Rule::in(self::STATUSES)],
            'payment_method' => ['nullable', Rule::in(self::PAYMENT_METHODS)],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'search' => ['nullable', 'string', 'max:255'],
        ]);
        $search = trim($filters['search'] ?? '');

        $payments = Payment::query()
            ->with(['student', 'studentFee.feeStructureItem'])
            ->when($filters['student_id'] ?? null, fn (Builder $query, int $id) => $query->where('student_id', $id))
            ->when($filters['student_fee_id'] ?? null, fn (Builder $query, int $id) => $query->where('student_fee_id', $id))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['payment_method'] ?? null, fn (Builder $query, string $method) => $query->where('payment_method', $method))
            ->when($filters['date_from'] ?? null, fn (Builder $query, string $date) => $query->whereDate('paid_date', '>=', $date))
            ->when($filters['date_to'] ?? null, fn (Builder $query, string $date) => $query->whereDate('paid_date', '<=', $date))
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('receipt_number', 'like', '%'.$search.'%')
                        ->orWhere('reference_number', 'like', '%'.$search.'%');
                });
            })
            ->latest('paid_date')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('payments.index', compact('payments', 'filters', 'search'));
    }

    public function create(Request $request): View
    {
        $studentFees = StudentFee::query()
            ->whereIn('status', ['unpaid', 'partially_paid'])
            ->where('balance', '>', 0)
            ->whereHas('student', fn (Builder $query) => $query->where('status', 'active'))
            ->with(['student', 'feeStructureItem'])
            ->orderBy('student_id')
            ->orderBy('id')
            ->get();

        $selectedStudentFeeId = $request->query('student_fee_id');

        return view('payments.create', compact('studentFees', 'selectedStudentFeeId'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'student_fee_id' => ['required', 'integer', 'exists:student_fees,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['required', Rule::in(self::PAYMENT_METHODS)],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'paid_date' => ['required', 'date', 'before_or_equal:today'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        $payment = DB::transaction(function () use ($validated): Payment {
            $studentFee = StudentFee::query()
                ->lockForUpdate()
                ->find($validated['student_fee_id']);
            $student = $studentFee === null
                ? null
                : Student::query()->lockForUpdate()->find($studentFee->student_id);

            if (
                $studentFee === null
                || $student === null
                || $student->status !== 'active'
            ) {
                throw ValidationException::withMessages([
                    'student_fee_id' => 'The selected student fee does not belong to an active student.',
                ]);
            }

            if (! in_array($studentFee->status, ['unpaid', 'partially_paid'], true)) {
                throw ValidationException::withMessages([
                    'student_fee_id' => 'Only an unpaid or partially paid student fee can receive a payment.',
                ]);
            }

            $amount = number_format((float) $validated['amount'], 2, '.', '');
            $amountInCents = (int) round((float) $amount * 100);
            $balanceInCents = (int) round((float) $studentFee->balance * 100);

            if ($amountInCents > $balanceInCents) {
                throw ValidationException::withMessages([
                    'amount' => 'The payment amount cannot exceed the remaining balance.',
                ]);
            }

            $payment = Payment::create([
                'student_fee_id' => $studentFee->id,
                'student_id' => $studentFee->student_id,
                'amount' => $amount,
                'payment_method' => $validated['payment_method'],
                'reference_number' => $validated['reference_number'] ?? null,
                'receipt_number' => Payment::generateReceiptNumber(),
                'paid_date' => $validated['paid_date'],
                'recorded_by' => auth()->id(),
                'status' => 'completed',
                'remarks' => $validated['remarks'] ?? null,
            ]);

            $studentFee->paid_amount = number_format(
                ((int) round((float) $studentFee->paid_amount * 100) + $amountInCents) / 100,
                2,
                '.',
                ''
            );
            $studentFee->save();
            $studentFee->recalculateBalance();

            return $payment;
        });

        return redirect()
            ->route('payments.show', $payment)
            ->with('success', 'Payment recorded successfully.');
    }

    public function show(Payment $payment): View
    {
        $payment->load([
            'student',
            'studentFee.feeStructureItem',
            'studentFee.feeStructure.academicYear',
            'studentFee.feeStructure.term',
            'recordedBy',
            'reversedBy',
        ]);

        return view('payments.show', compact('payment'));
    }

    public function reverse(Request $request, Payment $payment): RedirectResponse
    {
        $validated = $request->validate([
            'reversal_reason' => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        DB::transaction(function () use ($payment, $validated): void {
            $lockedPayment = Payment::query()
                ->lockForUpdate()
                ->findOrFail($payment->id);

            if (! $lockedPayment->isCompleted()) {
                throw ValidationException::withMessages([
                    'payment' => 'Only a completed payment can be reversed.',
                ]);
            }

            $studentFee = StudentFee::query()
                ->lockForUpdate()
                ->findOrFail($lockedPayment->student_fee_id);

            $paidAmountInCents = (int) round((float) $studentFee->paid_amount * 100);
            $paymentAmountInCents = (int) round((float) $lockedPayment->amount * 100);

            if ($paidAmountInCents < $paymentAmountInCents) {
                throw ValidationException::withMessages([
                    'payment' => 'The payment cannot be reversed because the student fee balance is inconsistent.',
                ]);
            }

            $lockedPayment->update([
                'status' => 'reversed',
                'reversed_at' => now(),
                'reversed_by' => auth()->id(),
                'reversal_reason' => $validated['reversal_reason'],
            ]);

            $studentFee->paid_amount = number_format(
                ($paidAmountInCents - $paymentAmountInCents) / 100,
                2,
                '.',
                ''
            );
            $studentFee->save();
            $studentFee->recalculateBalance();
        });

        return redirect()
            ->route('payments.show', $payment)
            ->with('success', 'Payment reversed successfully.');
    }
}
