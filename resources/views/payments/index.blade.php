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

<h1>Payments</h1>

<p>
    <a href="{{ route('dashboard') }}">Dashboard</a>
    @if(auth()->user()->hasPermission('payments.create'))
        <a href="{{ route('payments.create') }}" class="primary">Record payment</a>
    @endif
</p>

<form method="GET" action="{{ route('payments.index') }}">
    <label for="search">Receipt or reference</label>
    <input id="search" name="search" type="search" value="{{ $search }}">

    <label for="student_id">Student ID</label>
    <input id="student_id" name="student_id" type="number" min="1" value="{{ $filters['student_id'] ?? '' }}">

    <label for="student_fee_id">Student fee ID</label>
    <input id="student_fee_id" name="student_fee_id" type="number" min="1" value="{{ $filters['student_fee_id'] ?? '' }}">

    <label for="status">Status</label>
    <select id="status" name="status">
        <option value="">All statuses</option>
        @foreach(['completed', 'reversed'] as $status)
            <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ ucfirst($status) }}</option>
        @endforeach
    </select>

    <label for="payment_method">Method</label>
    <select id="payment_method" name="payment_method">
        <option value="">All methods</option>
        @foreach(['cash', 'bank_transfer', 'mobile_money', 'cheque', 'other'] as $method)
            <option value="{{ $method }}" @selected(($filters['payment_method'] ?? '') === $method)>
                {{ str($method)->replace('_', ' ')->title() }}
            </option>
        @endforeach
    </select>

    <label for="date_from">Paid from</label>
    <input id="date_from" name="date_from" type="date" value="{{ $filters['date_from'] ?? '' }}">

    <label for="date_to">Paid to</label>
    <input id="date_to" name="date_to" type="date" value="{{ $filters['date_to'] ?? '' }}">

    <button type="submit">Filter</button>
    @if(count(array_filter($filters, fn ($value) => $value !== null && $value !== '')) > 0 || $search !== '')
        <a href="{{ route('payments.index') }}">Clear</a>
    @endif
</form>

@if($payments->isEmpty())
    <p>No payments found.</p>
@else
    <table>
        <thead>
            <tr>
                <th>Receipt</th>
                <th>Student</th>
                <th>Fee</th>
                <th>Paid date</th>
                <th>Method</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Details</th>
            </tr>
        </thead>
        <tbody>
            @foreach($payments as $payment)
                <tr>
                    <td>{{ $payment->receipt_number }}</td>
                    <td>
                        {{ $payment->student->first_name }} {{ $payment->student->last_name }}
                        ({{ $payment->student->admission_number }})
                    </td>
                    <td>{{ $payment->studentFee->feeStructureItem->name }}</td>
                    <td>{{ $payment->paid_date->format('Y-m-d') }}</td>
                    <td>{{ str($payment->payment_method)->replace('_', ' ')->title() }}</td>
                    <td>{{ config('library.currency', 'TZS') }} {{ number_format((float) $payment->amount, 2) }}</td>
                    <td><span class="status-badge status-{{ $payment->status }}">{{ ucfirst($payment->status) }}</span></td>
                    <td><a href="{{ route('payments.show', $payment) }}">Details</a></td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{ $payments->links() }}
@endif
@endsection
