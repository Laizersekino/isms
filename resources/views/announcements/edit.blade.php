@extends('layouts.crud')

@section('content')
<h1>Edit announcement</h1>
<p><a href="{{ route('announcements.show', $announcement) }}">Back to announcement</a></p>

@include('announcements._form', [
    'action' => route('announcements.update', $announcement),
    'method' => 'PUT',
    'submitLabel' => 'Save changes',
])
@endsection
