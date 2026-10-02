@extends('layouts.auth')

@section('title', 'Set your password')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-bold tracking-tight text-slate-950">Set your password</h1>
        <p class="mt-2 text-sm text-slate-600">Choose a secure password for your account.</p>
    </div>

    <form method="POST" action="{{ route('password.update') }}" class="space-y-5">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <x-form.input name="email" label="Email address" type="email" :value="$email" autocomplete="email" required autofocus />
        <x-form.input name="password" label="New password" type="password" autocomplete="new-password" required />
        <x-form.input name="password_confirmation" label="Confirm new password" type="password" autocomplete="new-password" required />
        <x-button type="submit" class="w-full">Reset password</x-button>
    </form>
@endsection
