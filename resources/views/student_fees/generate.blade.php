@extends('layouts.crud')

@section('content')
<h1>Generate Student Fees</h1>

<p>Fees are generated for active enrollments in the selected academic year and class.</p>

<form method="POST" action="{{ route('student-fees.generate') }}">
    @csrf

    <label for="academic_year_id">Academic year</label>
    <select id="academic_year_id" name="academic_year_id" required>
        <option value="">Select academic year</option>
        @foreach($academicYears as $academicYear)
            <option value="{{ $academicYear->id }}" @selected((string) old('academic_year_id') === (string) $academicYear->id)>
                {{ $academicYear->name }}
            </option>
        @endforeach
    </select>

    <label for="term_id">Term</label>
    <select id="term_id" name="term_id" required>
        <option value="">Select term</option>
        @foreach($terms as $term)
            <option value="{{ $term->id }}" @selected((string) old('term_id') === (string) $term->id)>
                {{ $term->academicYear->name }} — {{ $term->name }}
            </option>
        @endforeach
    </select>

    <label for="class_id">Class</label>
    <select id="class_id" name="class_id" required>
        <option value="">Select class</option>
        @foreach($classes as $class)
            <option value="{{ $class->id }}" @selected((string) old('class_id') === (string) $class->id)>
                {{ $class->name }}
            </option>
        @endforeach
    </select>

    <label for="due_date">Due date</label>
    <input id="due_date" type="date" name="due_date" value="{{ old('due_date') }}" min="{{ today()->toDateString() }}" required>

    <button type="submit" class="primary">Generate</button>
    <a href="{{ route('student-fees.index') }}">Cancel</a>
</form>
@endsection
