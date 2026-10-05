@extends(($forPdf ?? false) ? 'reports.finance.pdf-layout' : 'layouts.app')

@section('title', 'Payment Methods')
@section('content')
<div @class(['space-y-6' => !($forPdf ?? false)])>
    <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
        <div><h1 class="text-2xl font-bold text-slate-900">Payment Methods</h1><p class="mt-1 text-sm text-slate-600">Compare payment counts and collection totals by method.</p></div>
        @unless($forPdf ?? false)
            @include('reports.finance._export-links', ['routeName' => 'reports.finance.payment-methods', 'routeParameters' => []])
        @endunless
    </div>
    @unless($forPdf ?? false)
        <x-card>
            <form method="GET" action="{{ route('reports.finance.payment-methods') }}" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <x-form.input name="date_from" label="From" type="date" :value="$filters['date_from']" />
                <x-form.input name="date_to" label="To" type="date" :value="$filters['date_to']" />
                <div class="flex items-end gap-2"><x-button type="submit" icon="chart-bar">Filter</x-button><a href="{{ route('reports.finance.payment-methods') }}" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">Current month</a></div>
            </form>
        </x-card>
    @endunless
    <x-card title="Collection by payment method">
        <x-table.index>
            <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-600"><tr><th class="px-4 py-3">Payment method</th><th class="px-4 py-3">Payments</th><th class="px-4 py-3">Total collected</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($methods as $method)
                    <tr><td class="px-4 py-3 font-medium">{{ str($method->payment_method)->replace('_', ' ')->title() }}</td><td class="px-4 py-3">{{ $method->payment_count }}</td><td class="px-4 py-3 font-semibold text-success-700">{{ config('library.currency', 'TZS') }} {{ number_format((float) $method->total_collected, 2) }}</td></tr>
                @empty
                    <x-table.empty message="No payments found in this date range." colspan="3" />
                @endforelse
            </tbody>
        </x-table.index>
    </x-card>
</div>
@endsection
