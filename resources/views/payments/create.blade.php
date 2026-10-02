@extends('layouts.crud')

@section('content')
<h1>Record Payment</h1>

@if($studentFees->isEmpty())
    <p>No active student fees have an outstanding balance.</p>
    <p><a href="{{ route('payments.index') }}">Back to payments</a></p>
@else
    <form method="POST" action="{{ route('payments.store') }}">
        @csrf

        <label for="student_fee_id">Student fee</label>
        <select id="student_fee_id" name="student_fee_id" required>
            <option value="">Select student fee</option>
            @foreach($studentFees as $studentFee)
                <option
                    value="{{ $studentFee->id }}"
                    data-balance="{{ number_format((float) $studentFee->balance, 2, '.', '') }}"
                    @selected((string) old('student_fee_id', $selectedStudentFeeId) === (string) $studentFee->id)
                >
                    {{ $studentFee->student->admission_number }} —
                    {{ $studentFee->student->first_name }} {{ $studentFee->student->last_name }} —
                    {{ $studentFee->feeStructureItem->name }} —
                    Balance: {{ config('library.currency', 'TZS') }} {{ number_format((float) $studentFee->balance, 2) }}
                </option>
            @endforeach
        </select>

        <label for="amount">Amount</label>
        <input id="amount" type="number" name="amount" step="0.01" min="0.01" value="{{ old('amount') }}" required>

        <label for="payment_method">Payment method</label>
        <select id="payment_method" name="payment_method" required>
            <option value="">Select method</option>
            @foreach(['cash', 'bank_transfer', 'mobile_money', 'cheque', 'other'] as $method)
                <option value="{{ $method }}" @selected(old('payment_method') === $method)>
                    {{ str($method)->replace('_', ' ')->title() }}
                </option>
            @endforeach
        </select>

        <label for="reference_number">Reference number (optional)</label>
        <input id="reference_number" type="text" name="reference_number" maxlength="100" value="{{ old('reference_number') }}">

        <label for="paid_date">Payment date</label>
        <input id="paid_date" type="date" name="paid_date" max="{{ today()->toDateString() }}" value="{{ old('paid_date', today()->toDateString()) }}" required>

        <label for="remarks">Remarks (optional)</label>
        <textarea id="remarks" name="remarks" maxlength="1000">{{ old('remarks') }}</textarea>

        <button type="submit" class="primary">Record payment</button>
        <a href="{{ route('payments.index') }}">Cancel</a>
    </form>
@endif
@endsection
