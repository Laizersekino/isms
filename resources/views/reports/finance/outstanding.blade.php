@extends(($forPdf ?? false) ? 'reports.finance.pdf-layout' : 'layouts.crud')

@section('title', 'Outstanding Fees')

@section('content')
<h1>Outstanding Fees</h1>

@unless($forPdf ?? false)
    <form method="GET" action="{{ route('reports.finance.outstanding') }}">
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
        <label for="class_id">Class</label>
        <select id="class_id" name="class_id">
            <option value="">All classes</option>
            @foreach($classes as $class)
                <option value="{{ $class->id }}" @selected((string) ($filters['class_id'] ?? '') === (string) $class->id)>{{ $class->name }}</option>
            @endforeach
        </select>
        <label for="minimum_balance">Minimum balance</label>
        <input id="minimum_balance" name="minimum_balance" type="number" min="0" step="0.01" value="{{ $filters['minimum_balance'] ?? '' }}">
        <button type="submit">Filter</button>
        <a href="{{ route('reports.finance.outstanding') }}">Clear</a>
    </form>
    @include('reports.finance._export-links', ['routeName' => 'reports.finance.outstanding', 'routeParameters' => []])
@endunless

<table>
    <thead>
        <tr><th>Student</th><th>Admission number</th><th>Class</th><th>Total fees</th><th>Total paid</th><th>Balance</th></tr>
    </thead>
    <tbody>
        @forelse($students as $row)
            <tr>
                <td>{{ trim($row->first_name.' '.$row->last_name) }}</td>
                <td>{{ $row->admission_number }}</td>
                <td>{{ $row->class_name }}</td>
                <td>{{ config('library.currency', 'TZS') }} {{ number_format((float) $row->total_expected, 2) }}</td>
                <td>{{ config('library.currency', 'TZS') }} {{ number_format((float) $row->total_collected, 2) }}</td>
                <td>{{ config('library.currency', 'TZS') }} {{ number_format((float) $row->balance, 2) }}</td>
            </tr>
        @empty
            <tr><td colspan="6">No outstanding fees found.</td></tr>
        @endforelse
    </tbody>
</table>

@if(!($forPdf ?? false) && method_exists($students, 'links'))
    {{ $students->links() }}
@endif
@endsection
