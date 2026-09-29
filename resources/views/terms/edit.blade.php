@extends('layouts.crud')

@section('content')

<h1>Edit Term</h1>

<a href="{{ route('terms.index') }}">Back</a>

<form
    action="{{ route('terms.update', $term) }}"
    method="POST"
>

    @csrf
    @method('PUT')

    <p>
        <label>Academic Year</label><br>

        <select name="academic_year_id" required>

            @foreach($academicYears as $year)

                <option
                    value="{{ $year->id }}"
                    {{ $term->academic_year_id == $year->id ? 'selected' : '' }}
                >
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
            value="{{ $term->name }}"
            required
        >
    </p>

    <p>
        <label>Start Date</label><br>

        <input
            type="date"
            name="start_date"
            value="{{ $term->start_date?->format('Y-m-d') }}"
            required
        >
    </p>

    <p>
        <label>End Date</label><br>

        <input
            type="date"
            name="end_date"
            value="{{ $term->end_date?->format('Y-m-d') }}"
            required
        >
    </p>

    <p>
        <label>Status</label><br>

        <select name="status">

            <option
                value="active"
                {{ $term->status === 'active' ? 'selected' : '' }}
            >
                Active
            </option>

            <option
                value="inactive"
                {{ $term->status === 'inactive' ? 'selected' : '' }}
            >
                Inactive
            </option>

        </select>
    </p>

    <button type="submit">Update Term</button>

</form>

@endsection