@extends('layouts.crud')

@section('content')

<h1>Edit Exam</h1>

<form action="{{ route('exams.update', $exam) }}" method="POST">

    @csrf
    @method('PUT')

    <div>
        <label for="name">Exam Name:</label><br>

        <input
            type="text"
            name="name"
            id="name"
            value="{{ old('name', $exam->name) }}"
            required
        >
    </div>

    <br>

    <div>
        <label for="exam_type">Exam Type:</label><br>

        <select name="exam_type" id="exam_type" required>

            <option value="">-- Select Exam Type --</option>

            <option value="Mid Term"
                {{ old('exam_type', $exam->exam_type) == 'Mid Term' ? 'selected' : '' }}>
                Mid Term
            </option>

            <option value="Terminal"
                {{ old('exam_type', $exam->exam_type) == 'Terminal' ? 'selected' : '' }}>
                Terminal
            </option>

            <option value="Annual"
                {{ old('exam_type', $exam->exam_type) == 'Annual' ? 'selected' : '' }}>
                Annual
            </option>

            <option value="Mock"
                {{ old('exam_type', $exam->exam_type) == 'Mock' ? 'selected' : '' }}>
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
                    {{ old('academic_year_id', $exam->academic_year_id) == $academicYear->id ? 'selected' : '' }}
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
                    {{ old('term_id', $exam->term_id) == $term->id ? 'selected' : '' }}
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
            value="{{ old('start_date', $exam->start_date->format('Y-m-d')) }}"
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
            value="{{ old('end_date', $exam->end_date->format('Y-m-d')) }}"
            required
        >
    </div>

    <br>

    <div>
        <label for="description">Description:</label><br>

        <textarea
            name="description"
            id="description"
            rows="4"
            placeholder="Optional exam description"
        >{{ old('description', $exam->description) }}</textarea>
    </div>

    <br>

    <div>
        <label for="status">Status:</label><br>

        <select name="status" id="status" required>

            <option value="Draft"
                {{ old('status', $exam->status) == 'Draft' ? 'selected' : '' }}>
                Draft
            </option>

            <option value="Active"
                {{ old('status', $exam->status) == 'Active' ? 'selected' : '' }}>
                Active
            </option>

            <option value="Completed"
                {{ old('status', $exam->status) == 'Completed' ? 'selected' : '' }}>
                Completed
            </option>

            <option value="Cancelled"
                {{ old('status', $exam->status) == 'Cancelled' ? 'selected' : '' }}>
                Cancelled
            </option>

        </select>
    </div>

    <br>

    <button type="submit" class="primary">
        Update Exam
    </button>

    <a href="{{ route('exams.index') }}">
        Cancel
    </a>

</form>

@endsection