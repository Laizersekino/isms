@extends('layouts.app')

@section('title', 'Payments')
@section('content')
<div class="space-y-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Payments</h1>
            <p class="mt-1 text-sm text-slate-600">Search recorded payments and review their status.</p>
        </div>
        @if(auth()->user()->hasPermission('payments.create'))
            <a href="{{ route('payments.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-primary-700"><x-icon name="plus" size="sm" /> Record payment</a>
        @endif
    </div>
    <x-card>
        <form method="GET" action="{{ route('payments.index') }}" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-form.input name="search" label="Receipt or reference" type="search" :value="$search" />
            <x-form.input name="student_id" label="Student ID" type="number" min="1" :value="$filters['student_id'] ?? ''" />
            <x-form.input name="student_fee_id" label="Student fee ID" type="number" min="1" :value="$filters['student_fee_id'] ?? ''" />
            <x-form.select name="status" label="Status">
                <option value="">All statuses</option>
                @foreach(['completed', 'reversed'] as $status)
                    <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </x-form.select>
            <x-form.select name="payment_method" label="Method">
                <option value="">All methods</option>
                @foreach(['cash', 'bank_transfer', 'mobile_money', 'cheque', 'other'] as $method)
                    <option value="{{ $method }}" @selected(($filters['payment_method'] ?? '') === $method)>{{ str($method)->replace('_', ' ')->title() }}</option>
                @endforeach
            </x-form.select>
            <x-form.input name="date_from" label="Paid from" type="date" :value="$filters['date_from'] ?? ''" />
            <x-form.input name="date_to" label="Paid to" type="date" :value="$filters['date_to'] ?? ''" />
            <div class="flex items-end gap-2">
                <x-button type="submit" icon="chart-bar">Filter</x-button>
                @if(count(array_filter($filters, fn ($value) => $value !== null && $value !== '')) > 0 || $search !== '')
                    <a href="{{ route('payments.index') }}" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">Clear</a>
                @endif
            </div>
        </form>
    </x-card>
    <x-card>
        <x-table.index>
            <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-600">
                <tr><th class="px-4 py-3">Receipt</th><th class="px-4 py-3">Student</th><th class="px-4 py-3">Fee</th><th class="px-4 py-3">Paid date</th><th class="px-4 py-3">Method</th><th class="px-4 py-3">Amount</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Details</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($payments as $payment)
                    <tr class="hover:bg-slate-50">
                        <td class="whitespace-nowrap px-4 py-3 font-medium text-primary-700">{{ $payment->receipt_number }}</td>
                        <td class="px-4 py-3">{{ $payment->student->first_name }} {{ $payment->student->last_name }}<div class="text-xs text-slate-500">{{ $payment->student->admission_number }}</div></td>
                        <td class="px-4 py-3">{{ $payment->studentFee->feeStructureItem->name }}</td>
                        <td class="whitespace-nowrap px-4 py-3">{{ $payment->paid_date->format('Y-m-d') }}</td>
                        <td class="px-4 py-3">{{ str($payment->payment_method)->replace('_', ' ')->title() }}</td>
                        <td class="whitespace-nowrap px-4 py-3">{{ config('library.currency', 'TZS') }} {{ number_format((float) $payment->amount, 2) }}</td>
                        <td class="px-4 py-3"><x-badge :variant="$payment->status === 'completed' ? 'success' : 'danger'">{{ ucfirst($payment->status) }}</x-badge></td>
                        <td class="px-4 py-3"><a href="{{ route('payments.show', $payment) }}" class="inline-flex items-center gap-1 text-sm font-medium text-primary-700 hover:underline"><x-icon name="arrow-right" size="sm" /> Details</a></td>
                    </tr>
                @empty
                    <x-table.empty message="No payments found." colspan="8" />
                @endforelse
            </tbody>
        </x-table.index>
        <x-table.pagination :paginator="$payments" />
    </x-card>
</div>
@endsection
