@extends(($forPdf ?? false) ? 'reports.finance.pdf-layout' : 'layouts.crud')

@section('title', 'Payment Methods')

@section('content')
<h1>Payment Methods</h1>

@unless($forPdf ?? false)
    <form method="GET" action="{{ route('reports.finance.payment-methods') }}">
        <label for="date_from">From</label>
        <input id="date_from" name="date_from" type="date" value="{{ $filters['date_from'] }}">
        <label for="date_to">To</label>
        <input id="date_to" name="date_to" type="date" value="{{ $filters['date_to'] }}">
        <button type="submit">Filter</button>
        <a href="{{ route('reports.finance.payment-methods') }}">Current month</a>
    </form>
    @include('reports.finance._export-links', ['routeName' => 'reports.finance.payment-methods', 'routeParameters' => []])
@endunless

<table>
    <thead><tr><th>Payment method</th><th>Payments</th><th>Total collected</th></tr></thead>
    <tbody>
        @forelse($methods as $method)
            <tr>
                <td>{{ str($method->payment_method)->replace('_', ' ')->title() }}</td>
                <td>{{ $method->payment_count }}</td>
                <td>{{ config('library.currency', 'TZS') }} {{ number_format((float) $method->total_collected, 2) }}</td>
            </tr>
        @empty
            <tr><td colspan="3">No payments found in this date range.</td></tr>
        @endforelse
    </tbody>
</table>
@endsection
