@extends('layouts.crud')

@section('content')

<h1>Create Teacher Assignment</h1>

<form action="{{ route('teacher-assignments.store') }}" method="POST">
    @csrf

```
<div>
    <label for="teacher_id">Teacher:</label><br>
    <select name="teacher_id" id="teacher_id" required>
        <option value="">-- Select Teacher --</option>

        @foreach($teachers as $teacher)
            <option value="{{ $teacher->id }}"
                {{ old('teacher_id') == $teacher->id ? 'selected' : '' }}>
                {{ $teacher->first_name }}
                {{ $teacher->middle_name }}
                {{ $teacher->last_name }}
            </option>
        @endforeach
    </select>
</div>

<br>

<div>
    <label for="class_id">Class:</label><br>
    <select name="class_id" id="class_id" required>
        <option value="">-- Select Class --</option>

        @foreach($classes as $class)
            <option value="{{ $class->id }}"
                {{ old('class_id') == $class->id ? 'selected' : '' }}>
                {{ $class->name }}
            </option>
        @endforeach
    </select>
</div>

<br>

<div>
    <label for="stream_id">Stream:</label><br>
    <select name="stream_id" id="stream_id" required>
        <option value="">-- Select Stream --</option>

        @foreach($streams as $stream)
            <option value="{{ $stream->id }}"
                {{ old('stream_id') == $stream->id ? 'selected' : '' }}>
                {{ $stream->classRoom->name }} - {{ $stream->name }}
            </option>
        @endforeach
    </select>
</div>

<br>

<div>
    <label for="subject_id">Subject:</label><br>
    <select name="subject_id" id="subject_id" required>
        <option value="">-- Select Subject --</option>

        @foreach($subjects as $subject)
            <option value="{{ $subject->id }}"
                {{ old('subject_id') == $subject->id ? 'selected' : '' }}>
                {{ $subject->code }} - {{ $subject->name }}
            </option>
        @endforeach
    </select>
</div>

<br>

<div>
    <label for="academic_year_id">Academic Year:</label><br>
    <select name="academic_year_id" id="academic_year_id" required>
        <option value="">-- Select Academic Year --</option>

        @foreach($academicYears as $academicYear)
            <option value="{{ $academicYear->id }}"
                {{ old('academic_year_id') == $academicYear->id ? 'selected' : '' }}>
                {{ $academicYear->name }}
            </option>
        @endforeach
    </select>
</div>

<br>

<div>
    <label for="status">Status:</label><br>
    <select name="status" id="status" required>
        <option value="active"
            {{ old('status', 'active') == 'active' ? 'selected' : '' }}>
            Active
        </option>

        <option value="inactive"
            {{ old('status') == 'inactive' ? 'selected' : '' }}>
            Inactive
        </option>
    </select>
</div>

<br>

<button type="submit" class="primary">
    Save Assignment
</button>

<a href="{{ route('teacher-assignments.index') }}">
    Cancel
</a>
```

</form>

@endsection
