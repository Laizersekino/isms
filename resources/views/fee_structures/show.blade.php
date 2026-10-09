@extends('layouts.app')

@section('title', $feeStructure->name)

@section('content')
<div class="space-y-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div>
            <p class="text-sm font-medium text-primary-700">Fee structure</p>
            <h1 class="mt-1 text-2xl font-bold text-slate-900">{{ $feeStructure->name }}</h1>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('fee-structures.index') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Back</a>
            @if(auth()->user()->hasPermission('fee_structures.update'))
                <a href="{{ route('fee-structures.edit', $feeStructure) }}" class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-700">
                    <x-icon name="pencil-square" size="sm" /> Edit
                </a>
            @endif
            @if(auth()->user()->hasPermission('fee_structures.delete'))
                <form action="{{ route('fee-structures.destroy', $feeStructure) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <x-button type="submit" variant="danger" icon="trash" onclick="return confirm('Archive this fee structure?')">Archive</x-button>
                </form>
            @endif
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <x-card title="Structure details" class="lg:col-span-2">
            <dl class="grid gap-4 sm:grid-cols-2">
                <div><dt class="text-sm text-slate-500">Academic year</dt><dd class="mt-1 font-medium">{{ $feeStructure->academicYear->name }}</dd></div>
                <div><dt class="text-sm text-slate-500">Term</dt><dd class="mt-1 font-medium">{{ $feeStructure->term->name }}</dd></div>
                <div><dt class="text-sm text-slate-500">Class</dt><dd class="mt-1 font-medium">{{ $feeStructure->classRoom->name }}</dd></div>
                <div>
                    <dt class="text-sm text-slate-500">Status</dt>
                    <dd class="mt-1">
                        <x-badge :variant="match($feeStructure->status) {'active' => 'success', 'draft' => 'warning', default => 'default'}">
                            {{ ucfirst($feeStructure->status) }}
                        </x-badge>
                    </dd>
                </div>
                <div>
                    <dt class="text-sm text-slate-500">Payment Plan</dt>
                    <dd class="mt-1 font-medium">
                        {{ match($feeStructure->payment_plan ?? 'termly') {
                            'full' => 'Full Payment',
                            'termly' => 'Termly (3 installments)',
                            'monthly' => 'Monthly',
                            'custom' => 'Custom',
                            default => ucfirst($feeStructure->payment_plan ?? 'N/A'),
                        } }}
                    </dd>
                </div>
                <div>
                    <dt class="text-sm text-slate-500">Installments</dt>
                    <dd class="mt-1 font-medium">
                        {{ $feeStructure->installment_count ?? 3 }} × {{ config('library.currency', 'TZS') }} {{ number_format((float) ($feeStructure->installment_amount ?? 0), 2) }}
                    </dd>
                </div>
                <div class="sm:col-span-2"><dt class="text-sm text-slate-500">Description</dt><dd class="mt-1">{{ $feeStructure->description ?: '—' }}</dd></div>
                <div class="sm:col-span-2"><dt class="text-sm text-slate-500">Created by</dt><dd class="mt-1">{{ $feeStructure->createdBy->name }}</dd></div>
            </dl>
        </x-card>

        <x-card title="Total amount">
            <div class="flex items-center gap-3 text-2xl font-bold text-primary-700">
                <x-icon name="currency-dollar" />
                {{ config('library.currency', 'TZS') }} {{ number_format((float) $feeStructure->total_amount, 2) }}
            </div>
            @if($feeStructure->installment_amount)
                <p class="mt-2 text-sm text-slate-600">
                    {{ $feeStructure->installment_count }} installments of
                    <strong>{{ config('library.currency', 'TZS') }} {{ number_format((float) $feeStructure->installment_amount, 2) }}</strong>
                </p>
            @endif
        </x-card>
    </div>

    <x-card title="Fee items">
        <x-table.index>
            <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-600">
                <tr>
                    <th class="px-4 py-3">#</th>
                    <th class="px-4 py-3">Name</th>
                    <th class="px-4 py-3">Amount</th>
                    <th class="px-4 py-3">Mandatory</th>
                    <th class="px-4 py-3">Due Date</th>
                    <th class="px-4 py-3">Description</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($feeStructure->items->sortBy('order') as $item)
                    <tr>
                        <td class="px-4 py-3 text-slate-500">{{ $item->order ?? $loop->index }}</td>
                        <td class="px-4 py-3 font-medium">{{ $item->name }}</td>
                        <td class="px-4 py-3">{{ config('library.currency', 'TZS') }} {{ number_format((float) $item->amount, 2) }}</td>
                        <td class="px-4 py-3">
                            <x-badge :variant="$item->is_mandatory ? 'success' : 'default'">
                                {{ $item->is_mandatory ? 'Yes' : 'No' }}
                            </x-badge>
                        </td>
                        <td class="px-4 py-3">
                            {{ $item->due_date ? $item->due_date->format('Y-m-d') : '—' }}
                        </td>
                        <td class="px-4 py-3">{{ $item->description ?: '—' }}</td>
                    </tr>
                @empty
                    <x-table.empty message="No fee items have been added." colspan="6" />
                @endforelse
            </tbody>
            <tfoot class="bg-slate-50 font-semibold">
                <tr>
                    <td class="px-4 py-3" colspan="2">Total</td>
                    <td class="px-4 py-3">{{ number_format((float) $feeStructure->total_amount, 2) }} {{ config('library.currency', 'TZS') }}</td>
                    <td colspan="3"></td>
                </tr>
            </tfoot>
        </x-table.index>
    </x-card>
</div>
@endsection