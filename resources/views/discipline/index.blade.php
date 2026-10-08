@extends('layouts.app')

@section('title', 'Discipline')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-slate-900">Disciplinary Cases</h1>
        <x-button variant="primary" :href="route('discipline.create')" icon="plus">
            Add Case
        </x-button>
    </div>

    <x-card>
        <form method="GET" action="{{ route('discipline.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <x-form.select name="status" label="Status">
                <option value="">All Statuses</option>
                <option value="open" @selected(request('status') === 'open')>Open</option>
                <option value="resolved" @selected(request('status') === 'resolved')>Resolved</option>
                <option value="closed" @selected(request('status') === 'closed')>Closed</option>
            </x-form.select>

            <x-form.input name="offence_type" label="Offence Type" :value="request('offence_type')" />
            <x-form.input name="student_id" label="Student ID" :value="request('student_id')" />

            <div class="flex items-end gap-2">
                <x-button type="submit" variant="primary" icon="magnifying-glass">
                    Filter
                </x-button>
                <x-button variant="secondary" :href="route('discipline.index')">
                    Reset
                </x-button>
            </div>
        </form>

        <x-table.index>
            <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-600">
                <tr>
                    <th scope="col" class="px-4 py-3">Student</th>
                    <th scope="col" class="px-4 py-3">Offence</th>
                    <th scope="col" class="px-4 py-3">Date</th>
                    <th scope="col" class="px-4 py-3">Status</th>
                    <th scope="col" class="px-4 py-3">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                @forelse($cases as $case)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3">{{ $case->student?->first_name }} {{ $case->student?->last_name }}</td>
                        <td class="px-4 py-3">{{ $case->offence_type }}</td>
                        <td class="px-4 py-3">{{ $case->incident_date?->format('Y-m-d') }}</td>
                        <td class="px-4 py-3">
                            <x-badge :variant="$case->status === 'open' ? 'warning' : ($case->status === 'resolved' ? 'success' : 'gray')">
                                {{ ucfirst($case->status) }}
                            </x-badge>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <a href="{{ route('discipline.show', $case) }}" class="text-primary-600 hover:text-primary-700">
                                    <x-icon name="eye" class="w-5 h-5" />
                                </a>
                                <a href="{{ route('discipline.edit', $case) }}" class="text-warning-600 hover:text-warning-700">
                                    <x-icon name="pencil-square" class="w-5 h-5" />
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8">
                            <x-table.empty icon="clipboard-document-check" title="No disciplinary cases found." />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </x-table.index>

        <x-table.pagination :paginator="$cases" />
    </x-card>
@endsection