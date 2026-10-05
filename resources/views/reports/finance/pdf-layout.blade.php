@extends('layouts.pdf')

@push('styles')
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #222; }
        h1 { font-size: 20px; margin-bottom: 18px; }
        h2 { font-size: 14px; margin: 14px 0 6px; }
        table { margin-top: 12px; }
        th { background: #eee; }
        .summary { margin: 8px 0; }
    </style>
@endpush

@section('content')
    @yield('content')
@endsection
