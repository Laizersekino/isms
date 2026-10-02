@extends('layouts.crud')

@section('content')

<h1>Terms</h1>

@if ($errors->has('delete'))
    <div class="error">{{ $errors->first('delete') }}</div>
@endif

<a href="{{ route('dashboard') }}">Dashboard</a>
<a href="{{ route('terms.create') }}" class="primary">Add Term</a>

<table>

<tr>
    <th>ID</th>
    <th>Academic Year</th>
    <th>Name</th>
    <th>Start</th>
    <th>End</th>
    <th>Status</th>
    <th>Actions</th>
</tr>

@forelse($terms as $term)

<tr>

    <td>{{ $term->id }}</td>

    <td>{{ $term->academicYear->name ?? '-' }}</td>

    <td>{{ $term->name }}</td>

    <td>{{ $term->start_date?->format('Y-m-d') }}</td>

    <td>{{ $term->end_date?->format('Y-m-d') }}</td>

    <td>{{ $term->status }}</td>

    <td>

        @if(auth()->user()->hasPermission('academic_structure.update'))
            <a
                href="{{ route('terms.edit', $term) }}"
                class="warning"
            >
                Edit
            </a>
        @endif

        <form
            action="{{ route('terms.destroy', $term) }}"
            method="POST"
            style="display:inline"
        >

            @csrf
            @method('DELETE')

            <button
                class="danger"
                onclick="return confirm('Delete this term?')"
            >
                Delete
            </button>

        </form>

    </td>

</tr>

@empty

<tr>
    <td colspan="7">No terms found.</td>
</tr>

@endforelse

</table>

@endsection