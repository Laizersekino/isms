@extends('layouts.app')

@section('title', 'Profile')
@section('page-description', 'Manage your account details')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-bold tracking-tight text-slate-950">Your profile</h1>
        <p class="mt-2 text-sm text-slate-600">Update your account name and email address.</p>
    </div>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,2fr)_minmax(16rem,1fr)]">
        <x-card title="Account details">
            <form method="POST" action="{{ route('profile.update') }}" class="space-y-5">
                @csrf
                @method('PUT')
                <x-form.input name="name" label="Full name" :value="$user->name" autocomplete="name" required />
                <x-form.input name="email" label="Email address" type="email" :value="$user->email" autocomplete="email" required />
                <x-button type="submit">Save changes</x-button>
            </form>
        </x-card>

        <x-card title="Access">
            <p class="text-sm text-slate-600">Your roles are managed by a school administrator.</p>
            <ul class="mt-4 flex flex-wrap gap-2">
                @forelse($user->roles as $role)
                    <li><x-badge variant="info">{{ $role->name }}</x-badge></li>
                @empty
                    <li><x-badge>No role assigned</x-badge></li>
                @endforelse
            </ul>
        </x-card>
    </div>
@endsection
