@extends(($forPdf ?? false) ? 'reports.finance.pdf-layout' : 'layouts.app')

@section('title', 'Daily Collection')
@section('content')
<div @class(['space-y-6' => !($forPdf ?? false)])>
    <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
        <div><h1 class="text-2xl font-bold text-slate-900">Daily Collection</h1><p class="mt-1 text-sm text-slate-600">Collection activity for {{ $filters['date'] }}.</p></div>
        @unless($forPdf ?? false)
            @include('reports.finance._export-links', ['routeName' => 'reports.finance.daily-collection', 'routeParameters' => []])
        @endunless
    </div>
    @unless($forPdf ?? false)
        <x-card>
            <form method="GET" action="{{ route('reports.finance.daily-collection') }}" class="flex flex-col gap-4 sm:flex-row sm:items-end">
                <x-form.input name="date" label="Date" type="date" :value="$filters['date']" />
                <div class="flex items-center gap-2"><x-button type="submit" icon="chart-bar">Filter</x-button><a href="{{ route('reports.finance.daily-collection') }}" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">Today</a></div>
            </form>
        </x-card>
    @endunless
    <x-card>
        <p class="text-sm text-slate-500">Total collected</p>
        <p class="mt-2 flex items-center gap-2 text-2xl font-bold text-success-700"><x-icon name="currency-dollar" />{{ config('library.currency', 'TZS') }} {{ number_format((float) $totalCollected, 2) }}</p>
    </x-card>
    <x-card title="Breakdown by payment method">
        <x-table.index>
            <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-600"><tr><th class="px-4 py-3">Method</th><th class="px-4 py-3">Payments</th><th class="px-4 py-3">Amount</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($methods as $method)
                    <tr><td class="px-4 py-3">{{ str($method->payment_method)->replace('_', ' ')->title() }}</td><td class="px-4 py-3">{{ $method->payment_count }}</td><td class="px-4 py-3 font-medium text-success-700">{{ config('library.currency', 'TZS') }} {{ number_format((float) $method->total_collected, 2) }}</td></tr>
                @empty
                    <x-table.empty message="No payments found for this date." colspan="3" />
                @endforelse
            </tbody>
        </x-table.index>
    </x-card>
    <x-card title="Payments">
        <x-table.index>
            <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-600"><tr><th class="px-4 py-3">Date</th><th class="px-4 py-3">Receipt</th><th class="px-4 py-3">Student</th><th class="px-4 py-3">Fee item</th><th class="px-4 py-3">Method</th><th class="px-4 py-3">Amount</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($payments as $payment)
                    <tr><td class="px-4 py-3">{{ $payment->paid_date->format('Y-m-d') }}</td><td class="px-4 py-3">{{ $payment->receipt_number }}</td><td class="px-4 py-3">{{ $payment->student->first_name }} {{ $payment->student->last_name }} <span class="text-slate-500">({{ $payment->student->admission_number }})</span></td><td class="px-4 py-3">{{ $payment->studentFee->feeStructureItem->name }}</td><td class="px-4 py-3">{{ str($payment->payment_method)->replace('_', ' ')->title() }}</td><td class="px-4 py-3 font-medium text-success-700">{{ config('library.currency', 'TZS') }} {{ number_format((float) $payment->amount, 2) }}</td></tr>
                @empty
                    <x-table.empty message="No payments found for this date." colspan="6" />
                @endforelse
            </tbody>
        </x-table.index>
        @if(!($forPdf ?? false) && method_exists($payments, 'links'))
            <x-table.pagination :paginator="$payments" />
        @endif
    </x-card>
</div>
@endsection
