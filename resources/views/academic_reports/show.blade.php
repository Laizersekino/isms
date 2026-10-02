@extends('layouts.crud')

@section('content')
    <h1>Academic report card</h1>

    <form method="GET" action="{{ route('academic-reports.show', $student) }}" class="period-filter">
        <label for="academic_year_id">Academic year</label>
        <select id="academic_year_id" name="academic_year_id" onchange="this.form.term_id.value = ''; this.form.submit()">
            @foreach($academicYears as $year)
                <option value="{{ $year->id }}" @selected((int) $year->id === (int) $academicYear->id)>{{ $year->name }}</option>
            @endforeach
        </select>
        <label for="term_id">Term</label>
        <select id="term_id" name="term_id">
            @foreach($terms as $availableTerm)
                <option value="{{ $availableTerm->id }}" @selected((int) $availableTerm->id === (int) $term->id)>{{ $availableTerm->name }}</option>
            @endforeach
        </select>
        <button type="submit" class="primary">View report</button>
    </form>

    <p class="report-actions">
        <a href="{{ route('academic-reports.print', ['student' => $student, 'academic_year_id' => $academicYear->id, 'term_id' => $term->id]) }}" target="_blank">Print</a>
        <a href="{{ route('academic-reports.pdf', ['student' => $student, 'academic_year_id' => $academicYear->id, 'term_id' => $term->id]) }}">Download PDF</a>
    </p>

    @include('academic_reports._report_card')
@endsection
