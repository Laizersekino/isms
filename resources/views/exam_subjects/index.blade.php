@extends('layouts.crud')

@section('content')

<h1>Exam Subjects</h1>

@if ($errors->has('delete'))
    <div class="error">{{ $errors->first('delete') }}</div>
@endif

@if(auth()->user()->hasPermission('marks.create'))
    <p>
        <a href="{{ route('exam-subjects.create') }}" class="primary">
            + Add Subject to Exam
        </a>
    </p>
@endif

@if($examSubjects->count() > 0)

<table>

    <thead>
        <tr>
            <th>#</th>
            <th>Exam</th>
            <th>Subject</th>
            <th>Class</th>
            <th>Maximum Marks</th>
            <th>Actions</th>
        </tr>
    </thead>

    <tbody>

        @foreach($examSubjects as $examSubject)

            <tr>

                <td>
                    {{ $examSubject->id }}
                </td>

                <td>
                    {{ $examSubject->exam->name }}
                </td>

                <td>
                    {{ $examSubject->subject->name }}
                </td>

                <td>
                    {{ $examSubject->classRoom->name }}
                </td>

                <td>
                    {{ $examSubject->max_marks }}
                </td>

                <td>

                    @if(auth()->user()->hasPermission('marks.delete'))

                        <form
                            action="{{ route('exam-subjects.destroy', $examSubject) }}"
                            method="POST"
                            style="display:inline;"
                            onsubmit="return confirm('Are you sure you want to remove this subject from the exam?');"
                        >

                            @csrf

                            @method('DELETE')

                            <button type="submit" class="danger">
                                Delete
                            </button>

                        </form>

                    @endif

                </td>

            </tr>

        @endforeach

    </tbody>

</table>

@else

    <p>
        No subjects have been added to any exam yet.
    </p>

@endif

@endsection