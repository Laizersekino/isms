@extends('layouts.crud')

@section('content')

<h1>Assign Subject to Class</h1>

<a href="{{ route('class-subjects.index') }}">Back</a>

<form action="{{ route('class-subjects.store') }}" method="POST">

    @csrf

    <p>
        <label>Class</label><br>

        <select name="class_id" required>

            <option value="">Select Class</option>

            @foreach($classes as $class)

                <option value="{{ $class->id }}">
                    {{ $class->name }}
                </option>

            @endforeach

        </select>
    </p>

    <p>
        <label>Subject</label><br>

        <select name="subject_id" required>

            <option value="">Select Subject</option>

            @foreach($subjects as $subject)

                <option value="{{ $subject->id }}">
                    {{ $subject->code }} - {{ $subject->name }}
                </option>

            @endforeach

        </select>
    </p>

    <button type="submit">Assign Subject</button>

</form>

@endsection