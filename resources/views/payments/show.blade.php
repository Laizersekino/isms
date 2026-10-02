@extends('layouts.crud')

@section('content')
<style>
    .status-badge {
        border-radius: 4px;
        display: inline-block;
        font-weight: 600;
        padding: 3px 8px;
    }

    .status-completed {
        background: #d1e7dd;
        color: #0f5132;
    }

    .status-reversed {
        background: #f8d7da;
        color: #842029;
    }
</style>

<h1>Payment {{ $payment->receipt_number }}</h1>

<p><strong>Student:</strong> {{ $payment->student->first_name }} {{ $payment->student->last_name }} ({{ $payment->student->admission_number }})</p>
<p><strong>Student fee:</strong> <a href="{{ route('student-fees.show', $payment->studentFee) }}">{{ $payment->studentFee->feeStructureItem->name }}</a></p>
<p><strong>Fee structure:</strong> {{ $payment->studentFee->feeStructure->name }}</p>
<p><strong>Amount:</strong> {{ config('library.currency', 'TZS') }} {{ number_format((float) $payment->amount, 2) }}</p>
<p><strong>Payment method:</strong> {{ str($payment->payment_method)->replace('_', ' ')->title() }}</p>
<p><strong>Reference number:</strong> {{ $payment->reference_number ?? '—' }}</p>
<p><strong>Paid date:</strong> {{ $payment->paid_date->format('Y-m-d') }}</p>
<p><strong>Status:</strong> <span class="status-badge status-{{ $payment->status }}">{{ ucfirst($payment->status) }}</span></p>
<p><strong>Recorded by:</strong> {{ $payment->recordedBy->name }}</p>
@if($payment->remarks)
    <p><strong>Remarks:</strong> {{ $payment->remarks }}</p>
@endif

@if($payment->isReversed())
    <h2>Reversal</h2>
    <p><strong>Reversed at:</strong> {{ $payment->reversed_at?->format('Y-m-d H:i:s') ?? '—' }}</p>
    <p><strong>Reversed by:</strong> {{ $payment->reversedBy?->name ?? '—' }}</p>
    <p><strong>Reason:</strong> {{ $payment->reversal_reason }}</p>
@elseif(auth()->user()->hasPermission('payments.reverse'))
    <h2>Reverse payment</h2>
    <form action="{{ route('payments.reverse', $payment) }}" method="POST">
        @csrf
        <label for="reversal_reason">Reason for reversal</label>
        <textarea id="reversal_reason" name="reversal_reason" minlength="10" maxlength="1000" required>{{ old('reversal_reason') }}</textarea>
        <button type="submit" class="warning" onclick="return confirm('Reverse this payment?')">Reverse payment</button>
    </form>
@endif

<p>
    <a href="{{ route('payments.index') }}">Back to payments</a>
    @if(auth()->user()->hasPermission('receipts.view'))
        <a href="{{ route('receipts.show', $payment) }}">View receipt</a>
    @endif
    @if(auth()->user()->hasPermission('receipts.print'))
        <a href="{{ route('receipts.print', $payment) }}">Print receipt</a>
    @endif
    @if(auth()->user()->hasPermission('payments.create'))
        <a href="{{ route('payments.create', ['student_fee_id' => $payment->student_fee_id]) }}">Record another payment for this fee</a>
    @endif
</p>
@endsection
