@extends('layouts.app')

@section('title', 'Payment '.$payment->receipt_number)
@section('content')
<div class="space-y-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div><p class="text-sm font-medium text-primary-700">Payment</p><h1 class="mt-1 text-2xl font-bold text-slate-900">{{ $payment->receipt_number }}</h1></div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('payments.index') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Back to payments</a>
            @if(auth()->user()->hasPermission('receipts.view'))
                <a href="{{ route('receipts.show', $payment) }}" class="inline-flex items-center gap-2 rounded-lg border border-primary-200 px-4 py-2 text-sm font-semibold text-primary-700 hover:bg-primary-50"><x-icon name="clipboard-document-check" size="sm" /> View receipt</a>
            @endif
            @if(auth()->user()->hasPermission('receipts.print'))
                <a href="{{ route('receipts.print', $payment) }}" class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-700"><x-icon name="arrow-down-tray" size="sm" /> Print receipt</a>
            @endif
            @if(auth()->user()->hasPermission('payments.create'))
                <a href="{{ route('payments.create', ['student_fee_id' => $payment->student_fee_id]) }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"><x-icon name="plus" size="sm" /> Another payment</a>
            @endif
        </div>
    </div>
    <div class="grid gap-6 xl:grid-cols-3">
        <x-card title="Payment details" class="xl:col-span-2">
            <dl class="grid gap-4 sm:grid-cols-2">
                <div><dt class="text-sm text-slate-500">Student</dt><dd class="mt-1 font-medium">{{ $payment->student->first_name }} {{ $payment->student->last_name }} ({{ $payment->student->admission_number }})</dd></div>
                <div><dt class="text-sm text-slate-500">Student fee</dt><dd class="mt-1"><a class="text-primary-700 hover:underline" href="{{ route('student-fees.show', $payment->studentFee) }}">{{ $payment->studentFee->feeStructureItem->name }}</a></dd></div>
                <div><dt class="text-sm text-slate-500">Fee structure</dt><dd class="mt-1">{{ $payment->studentFee->feeStructure->name }}</dd></div>
                <div><dt class="text-sm text-slate-500">Payment method</dt><dd class="mt-1">{{ str($payment->payment_method)->replace('_', ' ')->title() }}</dd></div>
                <div><dt class="text-sm text-slate-500">Reference number</dt><dd class="mt-1">{{ $payment->reference_number ?? '—' }}</dd></div>
                <div><dt class="text-sm text-slate-500">Paid date</dt><dd class="mt-1">{{ $payment->paid_date->format('Y-m-d') }}</dd></div>
                <div><dt class="text-sm text-slate-500">Recorded by</dt><dd class="mt-1">{{ $payment->recordedBy->name }}</dd></div>
                <div><dt class="text-sm text-slate-500">Status</dt><dd class="mt-1"><x-badge :variant="$payment->status === 'completed' ? 'success' : 'danger'">{{ ucfirst($payment->status) }}</x-badge></dd></div>
                @if($payment->remarks)
                    <div class="sm:col-span-2"><dt class="text-sm text-slate-500">Remarks</dt><dd class="mt-1">{{ $payment->remarks }}</dd></div>
                @endif
            </dl>
        </x-card>
        <x-card title="Amount">
            <div class="flex items-center gap-3 text-2xl font-bold text-success-700"><x-icon name="currency-dollar" />{{ config('library.currency', 'TZS') }} {{ number_format((float) $payment->amount, 2) }}</div>
        </x-card>
    </div>
    @if($payment->isReversed())
        <x-card title="Reversal">
            <dl class="grid gap-4 sm:grid-cols-3">
                <div><dt class="text-sm text-slate-500">Reversed at</dt><dd class="mt-1">{{ $payment->reversed_at?->format('Y-m-d H:i:s') ?? '—' }}</dd></div>
                <div><dt class="text-sm text-slate-500">Reversed by</dt><dd class="mt-1">{{ $payment->reversedBy?->name ?? '—' }}</dd></div>
                <div><dt class="text-sm text-slate-500">Reason</dt><dd class="mt-1">{{ $payment->reversal_reason }}</dd></div>
            </dl>
        </x-card>
    @elseif(auth()->user()->hasPermission('payments.reverse'))
        <x-card title="Reverse payment">
            <form action="{{ route('payments.reverse', $payment) }}" method="POST" class="space-y-4">
                @csrf
                <x-form.textarea name="reversal_reason" label="Reason for reversal" minlength="10" maxlength="1000" :value="old('reversal_reason')" required />
                <x-button type="submit" variant="danger" icon="arrow-right-on-rectangle" onclick="return confirm('Reverse this payment?')">Reverse payment</x-button>
            </form>
        </x-card>
    @endif
</div>
@endsection
