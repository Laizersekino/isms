@extends('layouts.crud')

@section('content')

<h1>Academic Years</h1>

@if ($errors->has('delete'))
    <div class="error">{{ $errors->first('delete') }}</div>
@endif

<a href="{{ route('dashboard') }}">Dashboard</a>
<a href="{{ route('academic-years.create') }}" class="primary">
    Add Academic Year
</a>

<table>

    <tr>
        <th>ID</th>
        <th>Name</th>
        <th>Start Date</th>
        <th>End Date</th>
        <th>Status</th>
        <th>Actions</th>
    </tr>

    @forelse($academicYears as $year)

        <tr>
            <td>{{ $year->id }}</td>
            <td>{{ $year->name }}</td>
            <td>{{ $year->start_date?->format('Y-m-d') }}</td>
            <td>{{ $year->end_date?->format('Y-m-d') }}</td>
            <td>{{ $year->status }}</td>

            <td>
                @if(auth()->user()->hasPermission('academic_structure.update'))
                    <a
                        href="{{ route('academic-years.edit', $year) }}"
                        class="warning"
                    >
                        Edit
                    </a>
                @endif

                <form
                    action="{{ route('academic-years.destroy', $year) }}"
                    method="POST"
                    style="display:inline"
                >
                    @csrf
                    @method('DELETE')

                    <button
                        class="danger"
                        onclick="return confirm('Delete this academic year?')"
                    >
                        Delete
                    </button>
                </form>
            </td>
        </tr>

    @empty

        <tr>
            <td colspan="6">No academic years found.</td>
        </tr>

    @endforelse

</table>

@endsection