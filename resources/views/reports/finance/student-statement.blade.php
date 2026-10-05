@extends(($forPdf ?? false) ? 'reports.finance.pdf-layout' : 'layouts.app')

@section('title', 'Student Fee Statement')
@section('content')
<div @class(['space-y-6' => !($forPdf ?? false)])>
    <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
        <div><h1 class="text-2xl font-bold text-slate-900">Student Fee Statement</h1><p class="mt-1 text-sm text-slate-600">{{ $student->first_name }} {{ $student->middle_name }} {{ $student->last_name }} · {{ $student->admission_number }}</p></div>
        @unless($forPdf ?? false)
            @include('reports.finance._export-links', ['routeName' => 'reports.finance.student-statement', 'routeParameters' => ['student' => $student]])
        @endunless
    </div>
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        <x-card><p class="text-sm text-slate-500">Total fees</p><p class="mt-2 text-xl font-bold text-slate-900">{{ config('library.currency', 'TZS') }} {{ number_format((float) $totalExpected, 2) }}</p></x-card>
        <x-card><p class="text-sm text-slate-500">Total collected</p><p class="mt-2 text-xl font-bold text-success-700">{{ config('library.currency', 'TZS') }} {{ number_format((float) $totalCollected, 2) }}</p></x-card>
        <x-card><p class="text-sm text-slate-500">Outstanding balance</p><p class="mt-2 text-xl font-bold text-warning-700">{{ config('library.currency', 'TZS') }} {{ number_format((float) $totalOutstanding, 2) }}</p></x-card>
    </div>
    <x-card title="Fees">
        <x-table.index>
            <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-600"><tr><th class="px-4 py-3">Fee item</th><th class="px-4 py-3">Academic period</th><th class="px-4 py-3">Class</th><th class="px-4 py-3">Amount</th><th class="px-4 py-3">Paid</th><th class="px-4 py-3">Balance</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($studentFees as $statementFee)
                    <tr><td class="px-4 py-3 font-medium">{{ $statementFee['studentFee']->feeStructureItem->name }}</td><td class="px-4 py-3">{{ $statementFee['studentFee']->feeStructure->academicYear->name }} — {{ $statementFee['studentFee']->feeStructure->term->name }}</td><td class="px-4 py-3">{{ $statementFee['studentFee']->feeStructure->classRoom->name }}</td><td class="px-4 py-3">{{ config('library.currency', 'TZS') }} {{ number_format((float) $statementFee['studentFee']->amount, 2) }}</td><td class="px-4 py-3 text-success-700">{{ config('library.currency', 'TZS') }} {{ number_format((float) $statementFee['totalCollected'], 2) }}</td><td class="px-4 py-3">{{ config('library.currency', 'TZS') }} {{ number_format((float) $statementFee['balance'], 2) }}</td></tr>
                @empty
                    <x-table.empty message="No student fees found." colspan="6" />
                @endforelse
            </tbody>
        </x-table.index>
    </x-card>
    <x-card title="Payments and running balance">
        <x-table.index>
            <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-600"><tr><th class="px-4 py-3">Date</th><th class="px-4 py-3">Receipt</th><th class="px-4 py-3">Fee item</th><th class="px-4 py-3">Amount</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Running balance</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($transactions as $transaction)
                    <tr><td class="px-4 py-3">{{ $transaction['payment']->paid_date->format('Y-m-d') }}</td><td class="px-4 py-3">{{ $transaction['payment']->receipt_number }}</td><td class="px-4 py-3">{{ $transaction['feeItem'] }}</td><td class="px-4 py-3">{{ config('library.currency', 'TZS') }} {{ number_format((float) $transaction['payment']->amount, 2) }}</td><td class="px-4 py-3"><x-badge :variant="$transaction['payment']->status === 'completed' ? 'success' : 'danger'">{{ ucfirst($transaction['payment']->status) }}</x-badge></td><td class="px-4 py-3">{{ config('library.currency', 'TZS') }} {{ number_format((float) $transaction['runningBalance'], 2) }}</td></tr>
                @empty
                    <x-table.empty message="No payments found." colspan="6" />
                @endforelse
            </tbody>
        </x-table.index>
    </x-card>
</div>
@endsection
