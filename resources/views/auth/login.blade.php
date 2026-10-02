@extends('layouts.auth')

@section('title', 'Sign in')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-bold tracking-tight text-slate-950">Sign in to your account</h1>
        <p class="mt-2 text-sm text-slate-600">Enter your school account credentials to continue.</p>
    </div>

    <form method="POST" action="{{ route('login.submit') }}" class="space-y-5">
        @csrf
        <x-form.input name="email" label="Email address" type="email" autocomplete="email" required autofocus placeholder="name@school.edu" />
        <x-form.input name="password" label="Password" type="password" autocomplete="current-password" required />
        <x-button type="submit" class="w-full">Sign in</x-button>
    </form>
@endsection
