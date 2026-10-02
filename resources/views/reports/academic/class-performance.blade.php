@extends('layouts.crud')

@section('content')
    <h1>Class performance report</h1>

    <form method="GET" action="{{ route('reports.academic.class-performance', ['class' => $classRoom]) }}">
        <label for="academic_year_id">Academic year</label>
        <select id="academic_year_id" name="academic_year_id" required>
            @foreach($academicYears as $year)
                <option value="{{ $year->id }}" @selected((int) $year->id === (int) $academicYear->id)>{{ $year->name }}</option>
            @endforeach
        </select>
        <label for="term_id">Term</label>
        <select id="term_id" name="term_id" required>
            @foreach($terms as $availableTerm)
                <option value="{{ $availableTerm->id }}" @selected((int) $availableTerm->id === (int) $term->id)>{{ $availableTerm->name }}</option>
            @endforeach
        </select>
        <button type="submit" class="primary">View report</button>
    </form>

    <p>
        <a href="{{ route('reports.academic.class-performance.print', ['class' => $classRoom, 'academic_year_id' => $academicYear->id, 'term_id' => $term->id]) }}" target="_blank">Print</a>
        <a href="{{ route('reports.academic.class-performance.pdf', ['class' => $classRoom, 'academic_year_id' => $academicYear->id, 'term_id' => $term->id]) }}">Download PDF</a>
    </p>

    @include('reports.academic.partials.class-performance-content')
@endsection
