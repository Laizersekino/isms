@extends('layouts.app')

@section('title', 'Fee Structures')
@section('content')
<div class="space-y-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Fee Structures</h1>
            <p class="mt-1 text-sm text-slate-600">Manage fees by academic period and class.</p>
        </div>
        @if(auth()->user()->hasPermission('fee_structures.create'))
            <a href="{{ route('fee-structures.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-primary-700">
                <x-icon name="plus" size="sm" /> Add Fee Structure
            </a>
        @endif
    </div>

    <x-card>
        <form method="GET" action="{{ route('fee-structures.index') }}" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
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
            <x-form.select name="class_id" label="Class">
                <option value="">All classes</option>
                @foreach($classes as $class)
                    <option value="{{ $class->id }}" @selected((string) ($filters['class_id'] ?? '') === (string) $class->id)>{{ $class->name }}</option>
                @endforeach
            </x-form.select>
            <x-form.select name="status" label="Status">
                <option value="">All statuses</option>
                @foreach(['draft', 'active', 'archived'] as $status)
                    <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </x-form.select>
            <div class="flex items-end gap-2">
                <x-button type="submit" icon="chart-bar">Filter</x-button>
                @if(count(array_filter($filters, fn ($value) => $value !== null && $value !== '' && $value !== false)) > 0)
                    <a href="{{ route('fee-structures.index') }}" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">Clear</a>
                @endif
            </div>
        </form>
    </x-card>

    <x-card>
        <x-table.index>
            <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-600">
                <tr><th class="px-4 py-3">Name</th><th class="px-4 py-3">Academic year</th><th class="px-4 py-3">Term</th><th class="px-4 py-3">Class</th><th class="px-4 py-3">Total</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Actions</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
            @forelse($feeStructures as $feeStructure)
                <tr class="hover:bg-slate-50">
                    <td class="px-4 py-3 font-medium text-slate-900"><a class="text-primary-700 hover:underline" href="{{ route('fee-structures.show', $feeStructure) }}">{{ $feeStructure->name }}</a></td>
                    <td class="px-4 py-3">{{ $feeStructure->academicYear->name }}</td>
                    <td class="px-4 py-3">{{ $feeStructure->term->name }}</td>
                    <td class="px-4 py-3">{{ $feeStructure->classRoom->name }}</td>
                    <td class="whitespace-nowrap px-4 py-3">{{ config('library.currency', 'TZS') }} {{ number_format((float) $feeStructure->total_amount, 2) }}</td>
                    <td class="px-4 py-3"><x-badge :variant="match($feeStructure->status) {'active' => 'success', 'draft' => 'warning', default => 'default'}">{{ ucfirst($feeStructure->status) }}</x-badge></td>
                    <td class="px-4 py-3">
                        <div class="flex flex-wrap items-center gap-2">
                            <a href="{{ route('fee-structures.show', $feeStructure) }}" class="inline-flex items-center gap-1 text-sm text-primary-700 hover:underline"><x-icon name="arrow-right" size="sm" /> View</a>
                            @if(auth()->user()->hasPermission('fee_structures.update'))
                                <a href="{{ route('fee-structures.edit', $feeStructure) }}" class="inline-flex items-center gap-1 text-sm text-warning-700 hover:underline"><x-icon name="pencil-square" size="sm" /> Edit</a>
                            @endif
                            @if(auth()->user()->hasPermission('fee_structures.delete'))
                                <form action="{{ route('fee-structures.destroy', $feeStructure) }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="inline-flex items-center gap-1 text-sm text-danger-700 hover:underline" onclick="return confirm('Archive this fee structure?')"><x-icon name="trash" size="sm" /> Archive</button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <x-table.empty message="No fee structures found." colspan="7" />
            @endforelse
            </tbody>
        </x-table.index>
        <x-table.pagination :paginator="$feeStructures" />
    </x-card>
</div>
@endsection
