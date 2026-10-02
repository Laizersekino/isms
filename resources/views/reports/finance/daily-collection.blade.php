@extends(($forPdf ?? false) ? 'reports.finance.pdf-layout' : 'layouts.crud')

@section('title', 'Daily Collection')

@section('content')
<h1>Daily Collection</h1>

@unless($forPdf ?? false)
    <form method="GET" action="{{ route('reports.finance.daily-collection') }}">
        <label for="date">Date</label>
        <input id="date" name="date" type="date" value="{{ $filters['date'] }}">
        <button type="submit">Filter</button>
        <a href="{{ route('reports.finance.daily-collection') }}">Today</a>
    </form>
    @include('reports.finance._export-links', ['routeName' => 'reports.finance.daily-collection', 'routeParameters' => []])
@endunless

<p>Total collected: {{ config('library.currency', 'TZS') }} {{ number_format((float) $totalCollected, 2) }}</p>
<h2>Breakdown by payment method</h2>
<table>
    <thead><tr><th>Method</th><th>Payments</th><th>Amount</th></tr></thead>
    <tbody>
        @forelse($methods as $method)
            <tr>
                <td>{{ str($method->payment_method)->replace('_', ' ')->title() }}</td>
                <td>{{ $method->payment_count }}</td>
                <td>{{ config('library.currency', 'TZS') }} {{ number_format((float) $method->total_collected, 2) }}</td>
            </tr>
        @empty
            <tr><td colspan="3">No payments found for this date.</td></tr>
        @endforelse
    </tbody>
</table>

<h2>Payments</h2>
<table>
    <thead><tr><th>Date</th><th>Receipt</th><th>Student</th><th>Fee item</th><th>Method</th><th>Amount</th></tr></thead>
    <tbody>
        @forelse($payments as $payment)
            <tr>
                <td>{{ $payment->paid_date->format('Y-m-d') }}</td>
                <td>{{ $payment->receipt_number }}</td>
                <td>{{ $payment->student->first_name }} {{ $payment->student->last_name }} ({{ $payment->student->admission_number }})</td>
                <td>{{ $payment->studentFee->feeStructureItem->name }}</td>
                <td>{{ str($payment->payment_method)->replace('_', ' ')->title() }}</td>
                <td>{{ config('library.currency', 'TZS') }} {{ number_format((float) $payment->amount, 2) }}</td>
            </tr>
        @empty
            <tr><td colspan="6">No payments found for this date.</td></tr>
        @endforelse
    </tbody>
</table>

@if(!($forPdf ?? false) && method_exists($payments, 'links'))
    {{ $payments->links() }}
@endif
@endsection
