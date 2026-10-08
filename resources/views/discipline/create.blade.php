@extends('layouts.app')

@section('title', 'Create Disciplinary Case')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-slate-900">Create Disciplinary Case</h1>
        <x-button variant="secondary" :href="route('discipline.index')" icon="arrow-left">
            Back to Cases
        </x-button>
    </div>

    <x-card>
        <form method="POST" action="{{ route('discipline.store') }}">
            @csrf

            @include('discipline.form')

            <div class="flex items-center gap-2 mt-6">
                <x-button type="submit" variant="primary" icon="check">
                    Create Case
                </x-button>
                <x-button variant="secondary" :href="route('discipline.index')">
                    Cancel
                </x-button>
            </div>
        </form>
    </x-card>
@endsection