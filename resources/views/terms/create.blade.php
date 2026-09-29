@extends('layouts.crud')

@section('content')

<h1>Add Term</h1>

<a href="{{ route('terms.index') }}">Back</a>

<form action="{{ route('terms.store') }}" method="POST">

    @csrf

    <p>
        <label>Academic Year</label><br>

        <select name="academic_year_id" required>

            <option value="">Select Academic Year</option>

            @foreach($academicYears as $year)

                <option value="{{ $year->id }}">
                    {{ $year->name }}
                </option>

            @endforeach

        </select>
    </p>

    <p>
        <label>Term Name</label><br>

        <input
            type="text"
            name="name"
            placeholder="Term 1"
            required
        >
    </p>

    <p>
        <label>Start Date</label><br>
        <input type="date" name="start_date" required>
    </p>

    <p>
        <label>End Date</label><br>
        <input type="date" name="end_date" required>
    </p>

    <p>
        <label>Status</label><br>

        <select name="status">
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
        </select>
    </p>

    <button type="submit">Save Term</button>

</form>

@endsection