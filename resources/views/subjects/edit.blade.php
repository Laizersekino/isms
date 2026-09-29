@extends('layouts.crud')

@section('content')

<h1>Edit Subject</h1>

<form action="{{ route('subjects.update', $subject) }}" method="POST">
    @csrf
    @method('PUT')

    <div>
        <label for="code">Subject Code</label><br>
        <input type="text"
               id="code"
               name="code"
               value="{{ old('code', $subject->code) }}"
               required>
    </div>

    <br>

    <div>
        <label for="name">Subject Name</label><br>
        <input type="text"
               id="name"
               name="name"
               value="{{ old('name', $subject->name) }}"
               required>
    </div>

    <br>

    <div>
        <label for="description">Description</label><br>
        <textarea id="description"
                  name="description"
                  rows="4">{{ old('description', $subject->description) }}</textarea>
    </div>

    <br>

    <div>
        <label for="status">Status</label><br>
        <select name="status" id="status" required>

            <option value="active"
                {{ old('status', $subject->status) == 'active' ? 'selected' : '' }}>
                Active
            </option>

            <option value="inactive"
                {{ old('status', $subject->status) == 'inactive' ? 'selected' : '' }}>
                Inactive
            </option>

        </select>
    </div>

    <br>

    <button type="submit" class="primary">
        Update Subject
    </button>

    <a href="{{ route('subjects.index') }}">
        Cancel
    </a>

</form>

@endsection