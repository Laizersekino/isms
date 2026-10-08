@extends('layouts.app')

@section('title', 'Edit Disciplinary Case')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-slate-900">Edit Disciplinary Case</h1>
        <x-button variant="secondary" :href="route('discipline.show', $disciplinaryCase)" icon="arrow-left">
            Back to Case
        </x-button>
    </div>

    <x-card>
        <form method="POST" action="{{ route('discipline.update', $disciplinaryCase) }}">
            @csrf
            @method('PUT')

            @include('discipline._form')

            <div class="flex items-center gap-2 mt-6">
                <x-button type="submit" variant="primary" icon="check">
                    Update Case
                </x-button>
                <x-button variant="secondary" :href="route('discipline.show', $disciplinaryCase)">
                    Cancel
                </x-button>
            </div>
        </form>
    </x-card>
@endsection