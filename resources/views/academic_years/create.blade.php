@extends('layouts.crud')

@section('content')

<h1>Add Academic Year</h1>

<a href="{{ route('academic-years.index') }}">Back</a>

<form action="{{ route('academic-years.store') }}" method="POST">

    @csrf

    <p>
        <label>Name</label><br>
        <input type="text" name="name" placeholder="2026/2027" required>
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

    <button type="submit">Save Academic Year</button>

</form>

@endsection