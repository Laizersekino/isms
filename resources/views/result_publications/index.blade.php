@extends('layouts.crud')

@section('content')

<h1>Result Publications</h1>

@if(auth()->user()->hasPermission('results.publish'))

    <p>
        <a
            href="{{ route('result-publications.create') }}"
            class="primary"
        >
            + Publish Results
        </a>
    </p>

@endif

@if($publications->count() > 0)

<table>

    <thead>

        <tr>
            <th>#</th>
            <th>Exam</th>
            <th>Class</th>
            <th>Status</th>
            <th>Published By</th>
            <th>Published At</th>
            <th>Remarks</th>
        </tr>

    </thead>

    <tbody>

        @foreach($publications as $publication)

            <tr>

                <td>
                    {{ $publication->id }}
                </td>

                <td>
                    {{ $publication->exam->name }}
                </td>

                <td>
                    {{ $publication->classRoom->name }}
                </td>

                <td>
                    {{ $publication->status }}
                </td>

                <td>
                    {{ $publication->publishedBy->name ?? 'Unknown' }}
                </td>

                <td>
                    {{ $publication->published_at?->format('Y-m-d H:i') }}
                </td>

                <td>
                    {{ $publication->remarks ?? '-' }}
                </td>

            </tr>

        @endforeach

    </tbody>

</table>

@else

    <p>
        No results have been published yet.
    </p>

@endif

@endsection