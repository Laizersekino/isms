@extends(($forPdf ?? false) ? 'reports.finance.pdf-layout' : 'layouts.app')

@section('title', 'Outstanding Fees')
@section('content')
<div @class(['space-y-6' => !($forPdf ?? false)])>
    <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
        <div><h1 class="text-2xl font-bold text-slate-900">Outstanding Fees</h1><p class="mt-1 text-sm text-slate-600">Review fee balances that are still due.</p></div>
        @unless($forPdf ?? false)
            @include('reports.finance._export-links', ['routeName' => 'reports.finance.outstanding', 'routeParameters' => []])
        @endunless
    </div>
    @unless($forPdf ?? false)
        <x-card>
            <form method="GET" action="{{ route('reports.finance.outstanding') }}" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
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
                <x-form.input name="minimum_balance" label="Minimum balance" type="number" min="0" step="0.01" :value="$filters['minimum_balance'] ?? ''" />
                <div class="flex items-end gap-2"><x-button type="submit" icon="chart-bar">Filter</x-button><a href="{{ route('reports.finance.outstanding') }}" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">Clear</a></div>
            </form>
        </x-card>
    @endunless
    <x-card title="Students with outstanding fees">
        <x-table.index>
            <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-600"><tr><th class="px-4 py-3">Student</th><th class="px-4 py-3">Admission number</th><th class="px-4 py-3">Class</th><th class="px-4 py-3">Total fees</th><th class="px-4 py-3">Total paid</th><th class="px-4 py-3">Balance</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($students as $row)
                    <tr><td class="px-4 py-3 font-medium">{{ trim($row->first_name.' '.$row->last_name) }}</td><td class="px-4 py-3">{{ $row->admission_number }}</td><td class="px-4 py-3">{{ $row->class_name }}</td><td class="px-4 py-3">{{ config('library.currency', 'TZS') }} {{ number_format((float) $row->total_expected, 2) }}</td><td class="px-4 py-3 text-success-700">{{ config('library.currency', 'TZS') }} {{ number_format((float) $row->total_collected, 2) }}</td><td class="px-4 py-3 font-semibold text-warning-700">{{ config('library.currency', 'TZS') }} {{ number_format((float) $row->balance, 2) }}</td></tr>
                @empty
                    <x-table.empty message="No outstanding fees found." colspan="6" />
                @endforelse
            </tbody>
        </x-table.index>
        @if(!($forPdf ?? false) && method_exists($students, 'links'))
            <x-table.pagination :paginator="$students" />
        @endif
    </x-card>
</div>
@endsection
