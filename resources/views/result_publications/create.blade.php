@extends('layouts.crud')

@section('content')

<h1>Publish Results</h1>

<p>
    Select an exam and class whose approved results you want to publish.
</p>

<form
    action="{{ route('result-publications.store') }}"
    method="POST"
>

    @csrf

    <div>

        <label for="exam_id">
            Exam:
        </label>

        <br>

        <select
            name="exam_id"
            id="exam_id"
            required
        >

            <option value="">
                -- Select Exam --
            </option>

            @foreach($exams as $exam)

                <option
                    value="{{ $exam->id }}"
                    {{ old('exam_id') == $exam->id ? 'selected' : '' }}
                >
                    {{ $exam->name }}
                    -
                    {{ $exam->exam_type }}
                </option>

            @endforeach

        </select>

    </div>

    <br>

    <div>

        <label for="class_id">
            Class:
        </label>

        <br>

        <select
            name="class_id"
            id="class_id"
            required
        >

            <option value="">
                -- Select Class --
            </option>

            @foreach($classes as $class)

                <option
                    value="{{ $class->id }}"
                    {{ old('class_id') == $class->id ? 'selected' : '' }}
                >
                    {{ $class->name }}
                </option>

            @endforeach

        </select>

    </div>

    <br>

    <div>

        <label for="remarks">
            Remarks:
        </label>

        <br>

        <textarea
            name="remarks"
            id="remarks"
            rows="4"
            placeholder="Optional"
        >{{ old('remarks') }}</textarea>

    </div>

    <br>

    <button
        type="submit"
        class="primary"
        onclick="return confirm('Are you sure you want to publish these results?');"
    >
        Publish Results
    </button>

    <a
        href="{{ route('result-publications.index') }}"
    >
        Cancel
    </a>

</form>

@endsection