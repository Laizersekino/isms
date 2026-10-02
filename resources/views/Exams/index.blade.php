@extends('layouts.crud')

@section('content')

<h1>Exams</h1>

@if ($errors->has('delete'))
    <div class="error">{{ $errors->first('delete') }}</div>
@endif

@if(auth()->user()->hasPermission('marks.create'))
    <p>
        <a href="{{ route('exams.create') }}" class="primary">
            + Create Exam
        </a>
    </p>
@endif

@if($exams->count() > 0)

<table>

    <thead>
        <tr>
            <th>#</th>
            <th>Exam Name</th>
            <th>Type</th>
            <th>Academic Year</th>
            <th>Term</th>
            <th>Start Date</th>
            <th>End Date</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
    </thead>

    <tbody>

        @foreach($exams as $exam)

            <tr>

                <td>
                    {{ $exam->id }}
                </td>

                <td>
                    {{ $exam->name }}
                </td>

                <td>
                    {{ $exam->exam_type }}
                </td>

                <td>
                    {{ $exam->academicYear->name }}
                </td>

                <td>
                    {{ $exam->term->name }}
                </td>

                <td>
                    {{ $exam->start_date->format('Y-m-d') }}
                </td>

                <td>
                    {{ $exam->end_date->format('Y-m-d') }}
                </td>

                <td>
                    {{ $exam->status }}
                </td>

                <td>

                    {{-- Edit --}}
                    @if(auth()->user()->hasPermission('marks.update'))

                        <a href="{{ route('exams.edit', $exam) }}">
                            Edit
                        </a>

                    @endif


                    {{-- Delete --}}
                    @if(auth()->user()->hasPermission('marks.delete'))

                        <form
                            action="{{ route('exams.destroy', $exam) }}"
                            method="POST"
                            style="display:inline;"
                            onsubmit="return confirm('Are you sure you want to delete this exam?');"
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
        No exams have been created yet.
    </p>

@endif

@endsection