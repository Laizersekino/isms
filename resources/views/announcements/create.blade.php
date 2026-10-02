@extends('layouts.crud')

@section('content')
<h1>Create announcement</h1>
<p><a href="{{ route('announcements.index') }}">Back to announcements</a></p>

@include('announcements._form', [
    'action' => route('announcements.store'),
    'method' => 'POST',
    'submitLabel' => 'Save draft',
])
@endsection
