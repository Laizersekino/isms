@extends('layouts.crud')

@section('content')

<h1>Create Subject</h1>

<form action="{{ route('subjects.store') }}" method="POST">
    @csrf

    <div>
        <label for="code">Subject Code</label><br>
        <input type="text"
               id="code"
               name="code"
               value="{{ old('code') }}"
               required>
    </div>

    <br>

    <div>
        <label for="name">Subject Name</label><br>
        <input type="text"
               id="name"
               name="name"
               value="{{ old('name') }}"
               required>
    </div>

    <br>

    <div>
        <label for="description">Description</label><br>
        <textarea id="description"
                  name="description"
                  rows="4">{{ old('description') }}</textarea>
    </div>

    <br>

    <div>
        <label for="status">Status</label><br>
        <select name="status" id="status" required>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
        </select>
    </div>

    <br>

    <button type="submit" class="primary">
        Save Subject
    </button>

    <a href="{{ route('subjects.index') }}">
        Cancel
    </a>

</form>

@endsection