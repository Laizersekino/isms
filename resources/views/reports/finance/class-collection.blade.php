@extends(($forPdf ?? false) ? 'reports.finance.pdf-layout' : 'layouts.crud')

@section('title', 'Class-wise Collection')

@section('content')
<h1>Class-wise Collection</h1>

@unless($forPdf ?? false)
    <form method="GET" action="{{ route('reports.finance.class-collection') }}">
        <label for="academic_year_id">Academic year</label>
        <select id="academic_year_id" name="academic_year_id">
            <option value="">All years</option>
            @foreach($academicYears as $academicYear)
                <option value="{{ $academicYear->id }}" @selected((string) ($filters['academic_year_id'] ?? '') === (string) $academicYear->id)>{{ $academicYear->name }}</option>
            @endforeach
        </select>
        <label for="term_id">Term</label>
        <select id="term_id" name="term_id">
            <option value="">All terms</option>
            @foreach($terms as $term)
                <option value="{{ $term->id }}" @selected((string) ($filters['term_id'] ?? '') === (string) $term->id)>{{ $term->academicYear->name }} — {{ $term->name }}</option>
            @endforeach
        </select>
        <button type="submit">Filter</button>
        <a href="{{ route('reports.finance.class-collection') }}">Clear</a>
    </form>
    @include('reports.finance._export-links', ['routeName' => 'reports.finance.class-collection', 'routeParameters' => []])
@endunless

<table>
    <thead><tr><th>Class</th><th>Students with fees</th><th>Expected</th><th>Collected</th><th>Outstanding</th><th>Collection rate</th></tr></thead>
    <tbody>
        @forelse($classes as $row)
            <tr>
                <td>{{ $row->class_name }}</td>
                <td>{{ $row->student_count }}</td>
                <td>{{ config('library.currency', 'TZS') }} {{ number_format((float) $row->total_expected, 2) }}</td>
                <td>{{ config('library.currency', 'TZS') }} {{ number_format((float) $row->total_collected, 2) }}</td>
                <td>{{ config('library.currency', 'TZS') }} {{ number_format((float) $row->outstanding, 2) }}</td>
                <td>{{ number_format((float) $row->collection_rate, 2) }}%</td>
            </tr>
        @empty
            <tr><td colspan="6">No fee records found.</td></tr>
        @endforelse
    </tbody>
</table>

@if(!($forPdf ?? false) && method_exists($classes, 'links'))
    {{ $classes->links() }}
@endif
@endsection
