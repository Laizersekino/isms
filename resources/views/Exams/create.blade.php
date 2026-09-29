@extends('layouts.crud')

@section('content')

<h1>Create Exam</h1>

<form action="{{ route('exams.store') }}" method="POST">

    @csrf

    <div>
        <label for="name">Exam Name:</label><br>

        <input
            type="text"
            name="name"
            id="name"
            value="{{ old('name') }}"
            placeholder="Example: Mid Term Examination"
            required
        >
    </div>

    <br>

    <div>
        <label for="type">Exam Type:</label><br>

        <select name="exam_type" id="exam_type" required>

            <option value="">-- Select Exam Type --</option>

            <option value="Mid Term"
                {{ old('type') == 'Mid Term' ? 'selected' : '' }}>
                Mid Term
            </option>

            <option value="Terminal"
                {{ old('type') == 'Terminal' ? 'selected' : '' }}>
                Terminal
            </option>

            <option value="Annual"
                {{ old('type') == 'Annual' ? 'selected' : '' }}>
                Annual
            </option>

            <option value="Mock"
                {{ old('type') == 'Mock' ? 'selected' : '' }}>
                Mock
            </option>

        </select>
    </div>

    <br>

    <div>
        <label for="academic_year_id">Academic Year:</label><br>

        <select name="academic_year_id" id="academic_year_id" required>

            <option value="">-- Select Academic Year --</option>

            @foreach($academicYears as $academicYear)

                <option
                    value="{{ $academicYear->id }}"
                    {{ old('academic_year_id') == $academicYear->id ? 'selected' : '' }}
                >
                    {{ $academicYear->name }}
                </option>

            @endforeach

        </select>

    </div>

    <br>

    <div>
        <label for="term_id">Term:</label><br>

        <select name="term_id" id="term_id" required>

            <option value="">-- Select Term --</option>

            @foreach($terms as $term)

                <option
                    value="{{ $term->id }}"
                    {{ old('term_id') == $term->id ? 'selected' : '' }}
                >
                    {{ $term->name }}
                </option>

            @endforeach

        </select>

    </div>

    <br>

    <div>
        <label for="start_date">Start Date:</label><br>

        <input
            type="date"
            name="start_date"
            id="start_date"
            value="{{ old('start_date') }}"
            required
        >
    </div>

    <br>

    <div>
        <label for="end_date">End Date:</label><br>

        <input
            type="date"
            name="end_date"
            id="end_date"
            value="{{ old('end_date') }}"
            required
        >
    </div>

    <br>

    <div>
        <label for="status">Status:</label><br>

        <select name="status" id="status" required>

            <option value="Draft"
                {{ old('status', 'Draft') == 'Draft' ? 'selected' : '' }}>
                Draft
            </option>

            <option value="Active"
                {{ old('status') == 'Active' ? 'selected' : '' }}>
                Active
            </option>

            <option value="Completed"
                {{ old('status') == 'Completed' ? 'selected' : '' }}>
                Completed
            </option>

            <option value="Cancelled"
                {{ old('status') == 'Cancelled' ? 'selected' : '' }}>
                Cancelled
            </option>

        </select>

    </div>

    <br>

    <button type="submit" class="primary">
        Save Exam
    </button>

    <a href="{{ route('exams.index') }}">
        Cancel
    </a>

<br>

<div>
    <label for="description">Description:</label><br>

    <textarea
        name="description"
        id="description"
        rows="4"
        placeholder="Optional exam description"
    >{{ old('description') }}</textarea>
</div>
</form>

@endsection