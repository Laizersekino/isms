@extends('layouts.app')

@section('title', 'Student Fee Details')
@section('content')
<div class="space-y-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Student Fee Details</h1>
            <p class="mt-1 text-sm text-slate-600">{{ $studentFee->student->first_name }} {{ $studentFee->student->last_name }} · {{ $studentFee->student->admission_number }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('student-fees.index') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Back to student fees</a>
            @if(auth()->user()->hasPermission('payments.create') && in_array($studentFee->status, ['unpaid', 'partially_paid'], true) && (float) $studentFee->balance > 0)
                <a href="{{ route('payments.create', ['student_fee_id' => $studentFee->id]) }}" class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-700"><x-icon name="plus" size="sm" /> Record payment</a>
            @endif
            @if(auth()->user()->hasPermission('student_fees.delete'))
                <form action="{{ route('student-fees.destroy', $studentFee) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <x-button type="submit" variant="danger" icon="trash" onclick="return confirm('Delete this student fee?')">Delete</x-button>
                </form>
            @endif
        </div>
    </div>
    <div class="grid gap-6 xl:grid-cols-3">
        <x-card title="Fee details" class="xl:col-span-2">
            <dl class="grid gap-4 sm:grid-cols-2">
                <div><dt class="text-sm text-slate-500">Fee item</dt><dd class="mt-1 font-medium">{{ $studentFee->feeStructureItem->name }}</dd></div>
                <div><dt class="text-sm text-slate-500">Fee structure</dt><dd class="mt-1 font-medium">{{ $studentFee->feeStructure->name }}</dd></div>
                <div><dt class="text-sm text-slate-500">Academic year</dt><dd class="mt-1">{{ $studentFee->feeStructure->academicYear->name }}</dd></div>
                <div><dt class="text-sm text-slate-500">Term / Class</dt><dd class="mt-1">{{ $studentFee->feeStructure->term->name }} / {{ $studentFee->feeStructure->classRoom->name }}</dd></div>
                <div><dt class="text-sm text-slate-500">Due date</dt><dd class="mt-1">{{ $studentFee->due_date?->format('Y-m-d') ?? '—' }}</dd></div>
                <div><dt class="text-sm text-slate-500">Created by</dt><dd class="mt-1">{{ $studentFee->createdBy->name }}</dd></div>
            </dl>
        </x-card>
        <x-card title="Balance summary">
            <div class="space-y-3">
                <div class="flex justify-between gap-3"><span class="text-slate-600">Amount</span><strong>{{ config('library.currency', 'TZS') }} {{ number_format((float) $studentFee->amount, 2) }}</strong></div>
                <div class="flex justify-between gap-3"><span class="text-slate-600">Paid</span><strong class="text-success-700">{{ config('library.currency', 'TZS') }} {{ number_format((float) $studentFee->paid_amount, 2) }}</strong></div>
                <div class="flex justify-between gap-3 border-t border-slate-200 pt-3"><span class="text-slate-600">Balance</span><strong class="text-lg">{{ config('library.currency', 'TZS') }} {{ number_format((float) $studentFee->balance, 2) }}</strong></div>
                <x-badge :variant="match($studentFee->status) {'unpaid' => 'warning', 'partially_paid' => 'info', 'paid' => 'success', default => 'default'}">{{ str($studentFee->status)->replace('_', ' ')->title() }}</x-badge>
            </div>
        </x-card>
    </div>
    <x-card title="Payments">
        <x-table.index>
            <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-600"><tr><th class="px-4 py-3">Receipt</th><th class="px-4 py-3">Paid date</th><th class="px-4 py-3">Method</th><th class="px-4 py-3">Amount</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Recorded by</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($studentFee->payments as $payment)
                    <tr>
                        <td class="px-4 py-3"><a class="font-medium text-primary-700 hover:underline" href="{{ route('payments.show', $payment) }}">{{ $payment->receipt_number }}</a></td>
                        <td class="px-4 py-3">{{ $payment->paid_date->format('Y-m-d') }}</td>
                        <td class="px-4 py-3">{{ str($payment->payment_method)->replace('_', ' ')->title() }}</td>
                        <td class="px-4 py-3">{{ config('library.currency', 'TZS') }} {{ number_format((float) $payment->amount, 2) }}</td>
                        <td class="px-4 py-3"><x-badge :variant="$payment->status === 'completed' ? 'success' : 'danger'">{{ ucfirst($payment->status) }}</x-badge></td>
                        <td class="px-4 py-3">{{ $payment->recordedBy->name }}</td>
                    </tr>
                    @if($payment->isReversed())
                        <tr><td colspan="6" class="bg-danger-50 px-4 py-3 text-sm text-danger-800">Reversed {{ $payment->reversed_at?->format('Y-m-d H:i') ?? '' }} by {{ $payment->reversedBy?->name ?? '—' }}: {{ $payment->reversal_reason }}</td></tr>
                    @endif
                @empty
                    <x-table.empty message="No payments have been recorded." colspan="6" />
                @endforelse
            </tbody>
        </x-table.index>
    </x-card>
</div>
@endsection
