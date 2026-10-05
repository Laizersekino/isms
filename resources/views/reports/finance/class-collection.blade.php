@extends(($forPdf ?? false) ? 'reports.finance.pdf-layout' : 'layouts.app')

@section('title', 'Class-wise Collection')
@section('content')
<div @class(['space-y-6' => !($forPdf ?? false)])>
    <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
        <div><h1 class="text-2xl font-bold text-slate-900">Class-wise Collection</h1><p class="mt-1 text-sm text-slate-600">Compare collection totals across classes.</p></div>
        @unless($forPdf ?? false)
            @include('reports.finance._export-links', ['routeName' => 'reports.finance.class-collection', 'routeParameters' => []])
        @endunless
    </div>
    @unless($forPdf ?? false)
        <x-card>
            <form method="GET" action="{{ route('reports.finance.class-collection') }}" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
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
                <div class="flex items-end gap-2"><x-button type="submit" icon="chart-bar">Filter</x-button><a href="{{ route('reports.finance.class-collection') }}" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">Clear</a></div>
            </form>
        </x-card>
    @endunless
    <x-card title="Collection by class">
        <x-table.index>
            <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-600"><tr><th class="px-4 py-3">Class</th><th class="px-4 py-3">Students with fees</th><th class="px-4 py-3">Expected</th><th class="px-4 py-3">Collected</th><th class="px-4 py-3">Outstanding</th><th class="px-4 py-3">Collection rate</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($classes as $row)
                    <tr><td class="px-4 py-3 font-medium">{{ $row->class_name }}</td><td class="px-4 py-3">{{ $row->student_count }}</td><td class="px-4 py-3">{{ config('library.currency', 'TZS') }} {{ number_format((float) $row->total_expected, 2) }}</td><td class="px-4 py-3 text-success-700">{{ config('library.currency', 'TZS') }} {{ number_format((float) $row->total_collected, 2) }}</td><td class="px-4 py-3 text-warning-700">{{ config('library.currency', 'TZS') }} {{ number_format((float) $row->outstanding, 2) }}</td><td class="px-4 py-3">{{ number_format((float) $row->collection_rate, 2) }}%</td></tr>
                @empty
                    <x-table.empty message="No fee records found." colspan="6" />
                @endforelse
            </tbody>
        </x-table.index>
        @if(!($forPdf ?? false) && method_exists($classes, 'links'))
            <x-table.pagination :paginator="$classes" />
        @endif
    </x-card>
</div>
@endsection
