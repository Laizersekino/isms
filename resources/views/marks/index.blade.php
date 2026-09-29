@extends('layouts.crud')

@section('content')

<h1>Marks</h1>

@if(auth()->user()->hasPermission('marks.create'))

    <p>
        <a
            href="{{ route('marks.create') }}"
            class="primary"
        >
            + Enter Marks
        </a>
    </p>

@endif

@if($marks->count() > 0)

<table>

    <thead>

        <tr>
            <th>#</th>
            <th>Student</th>
            <th>Exam</th>
            <th>Subject</th>
            <th>Class</th>
            <th>Marks</th>
            <th>Grade</th>
            <th>Status</th>
            <th>Entered By</th>
            <th>Actions</th>
        </tr>

    </thead>

    <tbody>

        @foreach($marks as $mark)

            <tr>

                <td>
                    {{ $mark->id }}
                </td>

                <td>
                    {{ $mark->student->first_name }}
                    {{ $mark->student->middle_name }}
                    {{ $mark->student->last_name }}
                </td>

                <td>
                    {{ $mark->examSubject->exam->name }}
                </td>

                <td>
                    {{ $mark->examSubject->subject->name }}
                </td>

                <td>
                    {{ $mark->examSubject->classRoom->name }}
                </td>

                <td>
                    {{ $mark->marks_obtained }}
                    /
                    {{ $mark->examSubject->max_marks }}
                </td>

                <td>
                    {{ $mark->grade }}
                </td>

                <td>
                    {{ $mark->status }}
                </td>

                <td>
                    {{ $mark->enteredBy->name ?? 'Unknown' }}
                </td>

                <td>

                    @if(
                        auth()->user()->hasPermission('marks.approve')
                        && $mark->status !== 'Approved'
                    )

                        <form
                            action="{{ route('marks.approve', $mark) }}"
                            method="POST"
                            style="display:inline;"
                            onsubmit="return confirm('Are you sure you want to approve this mark?');"
                        >

                            @csrf

                            <button
                                type="submit"
                                class="primary"
                            >
                                Approve
                            </button>

                        </form>

                    @elseif($mark->status === 'Approved')

                        <strong>Approved</strong>

                    @endif

                </td>

            </tr>

        @endforeach

    </tbody>

</table>

@else

    <p>
        No marks have been entered yet.
    </p>

@endif

@endsection