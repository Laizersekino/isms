@extends('layouts.app')

@section('title', 'Student Fees')
@section('content')
<div class="space-y-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Student Fees</h1>
            <p class="mt-1 text-sm text-slate-600">Review balances, payment status, and fee assignments.</p>
        </div>
        @if(auth()->user()->hasPermission('student_fees.generate'))
            <a href="{{ route('student-fees.generate-form') }}" class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-primary-700">
                <x-icon name="plus" size="sm" /> Generate Student Fees
            </a>
        @endif
    </div>

    <x-card>
        <form method="GET" action="{{ route('student-fees.index') }}" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-6">
            <div class="sm:col-span-2">
                <x-form.input name="search" label="Search student or fee" type="search" :value="$search" placeholder="Name, admission number, fee" />
            </div>
            <x-form.select name="academic_year_id" label="Academic year">
                <option value="">All years</option>
                @foreach($academicYears as $academicYear)
                    <option value="{{ $academicYear->id }}" @selected((string) ($filters['academic_year_id'] ?? '') === (string) $academicYear->id)>{{ $academicYear->name }}</option>
                @endforeach
            </x-form.select>
            <x-form.select name="term_id" label="Term">
                <option value="">All terms</option>
                @foreach($terms as $term)
                    <option value="{{ $term->id }}" @selected((string) ($filters['term_id'] ?? '') === (string) $term->id)>{{ $term->academicYear->name }} — {{ $term->name }}</option>
                @endforeach
            </x-form.select>
            <x-form.select name="fee_structure_id" label="Fee structure">
                <option value="">All structures</option>
                @foreach($feeStructures as $feeStructure)
                    <option value="{{ $feeStructure->id }}" @selected((string) ($filters['fee_structure_id'] ?? '') === (string) $feeStructure->id)>{{ $feeStructure->name }}</option>
                @endforeach
            </x-form.select>
            <x-form.select name="status" label="Status">
                <option value="">All statuses</option>
                @foreach(['unpaid', 'partially_paid', 'paid', 'waived'] as $status)
                    <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ str($status)->replace('_', ' ')->title() }}</option>
                @endforeach
            </x-form.select>
            <div class="flex items-end gap-2 sm:col-span-2 xl:col-span-6">
                <x-button type="submit" icon="chart-bar">Filter</x-button>
                @if(count(array_filter($filters, fn ($value) => $value !== null && $value !== '')) > 0 || $search !== '')
                    <a href="{{ route('student-fees.index') }}" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">Clear</a>
                @endif
            </div>
        </form>
    </x-card>

    <x-card>
        <x-table.index>
            <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-600">
                <tr><th class="px-4 py-3">Student</th><th class="px-4 py-3">Fee</th><th class="px-4 py-3">Academic period</th><th class="px-4 py-3">Amount</th><th class="px-4 py-3">Paid</th><th class="px-4 py-3">Balance</th><th class="px-4 py-3">Due date</th><th class="px-4 py-3">Status</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($studentFees as $studentFee)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3">
                            <a class="font-medium text-primary-700 hover:underline" href="{{ route('student-fees.show', $studentFee) }}">{{ $studentFee->student->first_name }} {{ $studentFee->student->last_name }}</a>
                            <div class="text-xs text-slate-500">{{ $studentFee->student->admission_number }}</div>
                        </td>
                        <td class="px-4 py-3">{{ $studentFee->feeStructureItem->name }}</td>
                        <td class="px-4 py-3">{{ $studentFee->feeStructure->academicYear->name }} — {{ $studentFee->feeStructure->term->name }}</td>
                        <td class="whitespace-nowrap px-4 py-3">{{ config('library.currency', 'TZS') }} {{ number_format((float) $studentFee->amount, 2) }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-success-700">{{ config('library.currency', 'TZS') }} {{ number_format((float) $studentFee->paid_amount, 2) }}</td>
                        <td class="whitespace-nowrap px-4 py-3 font-medium">{{ config('library.currency', 'TZS') }} {{ number_format((float) $studentFee->balance, 2) }}</td>
                        <td class="whitespace-nowrap px-4 py-3">{{ $studentFee->due_date?->format('Y-m-d') ?? '—' }}</td>
                        <td class="px-4 py-3"><x-badge :variant="match($studentFee->status) {'unpaid' => 'warning', 'partially_paid' => 'info', 'paid' => 'success', default => 'default'}">{{ str($studentFee->status)->replace('_', ' ')->title() }}</x-badge></td>
                    </tr>
                @empty
                    <x-table.empty message="No student fees found." colspan="8" />
                @endforelse
            </tbody>
        </x-table.index>
        <x-table.pagination :paginator="$studentFees" />
    </x-card>
</div>
@endsection
