@extends('layouts.crud')

@section('content')
<h1>Student Fee Details</h1>

<p><strong>Student:</strong> {{ $studentFee->student->first_name }} {{ $studentFee->student->last_name }}</p>
<p><strong>Admission number:</strong> {{ $studentFee->student->admission_number }}</p>
<p><strong>Fee item:</strong> {{ $studentFee->feeStructureItem->name }}</p>
<p><strong>Fee structure:</strong> {{ $studentFee->feeStructure->name }}</p>
<p><strong>Academic year:</strong> {{ $studentFee->feeStructure->academicYear->name }}</p>
<p><strong>Term:</strong> {{ $studentFee->feeStructure->term->name }}</p>
<p><strong>Class:</strong> {{ $studentFee->feeStructure->classRoom->name }}</p>
<p><strong>Amount:</strong> {{ config('library.currency', 'TZS') }} {{ number_format((float) $studentFee->amount, 2) }}</p>
<p><strong>Paid:</strong> {{ config('library.currency', 'TZS') }} {{ number_format((float) $studentFee->paid_amount, 2) }}</p>
<p><strong>Balance:</strong> {{ config('library.currency', 'TZS') }} {{ number_format((float) $studentFee->balance, 2) }}</p>
<p><strong>Due date:</strong> {{ $studentFee->due_date?->format('Y-m-d') ?? '—' }}</p>
<p><strong>Status:</strong> {{ str($studentFee->status)->replace('_', ' ')->title() }}</p>
<p><strong>Created by:</strong> {{ $studentFee->createdBy->name }}</p>

<h2>Payments</h2>
@if($studentFee->payments->isEmpty())
    <p>No payments have been recorded.</p>
@else
    <table>
        <thead>
            <tr>
                <th>Receipt</th>
                <th>Paid date</th>
                <th>Method</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Recorded by</th>
            </tr>
        </thead>
        <tbody>
            @foreach($studentFee->payments as $payment)
                <tr>
                    <td><a href="{{ route('payments.show', $payment) }}">{{ $payment->receipt_number }}</a></td>
                    <td>{{ $payment->paid_date->format('Y-m-d') }}</td>
                    <td>{{ str($payment->payment_method)->replace('_', ' ')->title() }}</td>
                    <td>{{ config('library.currency', 'TZS') }} {{ number_format((float) $payment->amount, 2) }}</td>
                    <td>{{ ucfirst($payment->status) }}</td>
                    <td>{{ $payment->recordedBy->name }}</td>
                </tr>
                @if($payment->isReversed())
                    <tr>
                        <td colspan="6">
                            Reversed {{ $payment->reversed_at?->format('Y-m-d H:i') ?? '' }}
                            by {{ $payment->reversedBy?->name ?? '—' }}:
                            {{ $payment->reversal_reason }}
                        </td>
                    </tr>
                @endif
            @endforeach
        </tbody>
    </table>
@endif

<p>
    <a href="{{ route('student-fees.index') }}">Back to student fees</a>
    @if(
        auth()->user()->hasPermission('payments.create')
        && in_array($studentFee->status, ['unpaid', 'partially_paid'], true)
        && (float) $studentFee->balance > 0
    )
        <a href="{{ route('payments.create', ['student_fee_id' => $studentFee->id]) }}" class="primary">Record payment</a>
    @endif
    @if(auth()->user()->hasPermission('student_fees.delete'))
        <form action="{{ route('student-fees.destroy', $studentFee) }}" method="POST" style="display:inline">
            @csrf
            @method('DELETE')
            <button type="submit" class="danger" onclick="return confirm('Delete this student fee?')">Delete</button>
        </form>
    @endif
</p>
@endsection
