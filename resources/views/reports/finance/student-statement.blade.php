@extends(($forPdf ?? false) ? 'reports.finance.pdf-layout' : 'layouts.crud')

@section('title', 'Student Fee Statement')

@section('content')
<h1>Student Fee Statement</h1>

@unless($forPdf ?? false)
    @include('reports.finance._export-links', [
        'routeName' => 'reports.finance.student-statement',
        'routeParameters' => ['student' => $student],
    ])
@endunless

<p>Student: {{ $student->first_name }} {{ $student->middle_name }} {{ $student->last_name }}</p>
<p>Admission number: {{ $student->admission_number }}</p>
<p>Total fees: {{ config('library.currency', 'TZS') }} {{ number_format((float) $totalExpected, 2) }}</p>
<p>Total collected: {{ config('library.currency', 'TZS') }} {{ number_format((float) $totalCollected, 2) }}</p>
<p>Outstanding balance: {{ config('library.currency', 'TZS') }} {{ number_format((float) $totalOutstanding, 2) }}</p>

<h2>Fees</h2>
<table>
    <thead><tr><th>Fee item</th><th>Academic period</th><th>Class</th><th>Amount</th><th>Paid</th><th>Balance</th></tr></thead>
    <tbody>
        @forelse($studentFees as $statementFee)
            <tr>
                <td>{{ $statementFee['studentFee']->feeStructureItem->name }}</td>
                <td>{{ $statementFee['studentFee']->feeStructure->academicYear->name }} — {{ $statementFee['studentFee']->feeStructure->term->name }}</td>
                <td>{{ $statementFee['studentFee']->feeStructure->classRoom->name }}</td>
                <td>{{ config('library.currency', 'TZS') }} {{ number_format((float) $statementFee['studentFee']->amount, 2) }}</td>
                <td>{{ config('library.currency', 'TZS') }} {{ number_format((float) $statementFee['totalCollected'], 2) }}</td>
                <td>{{ config('library.currency', 'TZS') }} {{ number_format((float) $statementFee['balance'], 2) }}</td>
            </tr>
        @empty
            <tr><td colspan="6">No student fees found.</td></tr>
        @endforelse
    </tbody>
</table>

<h2>Payments and running balance</h2>
<table>
    <thead><tr><th>Date</th><th>Receipt</th><th>Fee item</th><th>Amount</th><th>Status</th><th>Running balance</th></tr></thead>
    <tbody>
        @forelse($transactions as $transaction)
            <tr>
                <td>{{ $transaction['payment']->paid_date->format('Y-m-d') }}</td>
                <td>{{ $transaction['payment']->receipt_number }}</td>
                <td>{{ $transaction['feeItem'] }}</td>
                <td>{{ config('library.currency', 'TZS') }} {{ number_format((float) $transaction['payment']->amount, 2) }}</td>
                <td>{{ ucfirst($transaction['payment']->status) }}</td>
                <td>{{ config('library.currency', 'TZS') }} {{ number_format((float) $transaction['runningBalance'], 2) }}</td>
            </tr>
        @empty
            <tr><td colspan="6">No payments found.</td></tr>
        @endforelse
    </tbody>
</table>
@endsection
