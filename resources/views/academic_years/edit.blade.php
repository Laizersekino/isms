@extends('layouts.crud')

@section('content')

<h1>Edit Academic Year</h1>

<a href="{{ route('academic-years.index') }}">Back</a>

<form
    action="{{ route('academic-years.update', $academicYear) }}"
    method="POST"
>

    @csrf
    @method('PUT')

    <p>
        <label>Name</label><br>

        <input
            type="text"
            name="name"
            value="{{ $academicYear->name }}"
            required
        >
    </p>

    <p>
        <label>Start Date</label><br>

        <input
            type="date"
            name="start_date"
            value="{{ $academicYear->start_date?->format('Y-m-d') }}"
            required
        >
    </p>

    <p>
        <label>End Date</label><br>

        <input
            type="date"
            name="end_date"
            value="{{ $academicYear->end_date?->format('Y-m-d') }}"
            required
        >
    </p>

    <p>
        <label>Status</label><br>

        <select name="status">

            <option
                value="active"
                {{ $academicYear->status === 'active' ? 'selected' : '' }}
            >
                Active
            </option>

            <option
                value="inactive"
                {{ $academicYear->status === 'inactive' ? 'selected' : '' }}
            >
                Inactive
            </option>

        </select>
    </p>

    <button type="submit">Update Academic Year</button>

</form>

@endsection