@extends('layouts.app')

@section('title', 'Disciplinary Case Details')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-slate-900">Disciplinary Case Details</h1>
        <div class="flex items-center gap-2">
            <x-button variant="secondary" :href="route('discipline.index')" icon="arrow-left">
                Back
            </x-button>
            <x-button variant="primary" :href="route('discipline.edit', $disciplinaryCase)" icon="pencil-square">
                Edit
            </x-button>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main Content --}}
        <div class="lg:col-span-2 space-y-6">
            <x-card>
                <h2 class="text-lg font-semibold text-slate-900 mb-4">Case Information</h2>
                <div class="space-y-3">
                    <p class="text-sm"><strong class="text-slate-700">Student:</strong> <span class="text-slate-900">{{ $disciplinaryCase->student?->first_name }} {{ $disciplinaryCase->student?->last_name }}</span></p>
                    <p class="text-sm"><strong class="text-slate-700">Offence Type:</strong> <span class="text-slate-900">{{ $disciplinaryCase->offence_type }}</span></p>
                    <p class="text-sm"><strong class="text-slate-700">Incident Date:</strong> <span class="text-slate-900">{{ $disciplinaryCase->incident_date?->format('F j, Y') }}</span></p>
                    <p class="text-sm"><strong class="text-slate-700">Status:</strong> 
                        <x-badge :variant="$disciplinaryCase->status === 'open' ? 'warning' : ($disciplinaryCase->status === 'resolved' ? 'success' : 'gray')">
                            {{ ucfirst($disciplinaryCase->status) }}
                        </x-badge>
                    </p>
                </div>
            </x-card>

            <x-card>
                <h2 class="text-lg font-semibold text-slate-900 mb-4">Description</h2>
                <p class="text-sm text-slate-700 whitespace-pre-wrap">{{ $disciplinaryCase->description }}</p>
            </x-card>

            @if($disciplinaryCase->action_taken)
                <x-card>
                    <h2 class="text-lg font-semibold text-slate-900 mb-4">Action Taken</h2>
                    <p class="text-sm text-slate-700 whitespace-pre-wrap">{{ $disciplinaryCase->action_taken }}</p>
                </x-card>
            @endif

            @if($disciplinaryCase->follow_up)
                <x-card>
                    <h2 class="text-lg font-semibold text-slate-900 mb-4">Follow Up</h2>
                    <p class="text-sm text-slate-700 whitespace-pre-wrap">{{ $disciplinaryCase->follow_up }}</p>
                </x-card>
            @endif
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            <x-card>
                <h2 class="text-lg font-semibold text-slate-900 mb-4">Details</h2>
                <div class="space-y-3">
                    <p class="text-sm"><strong class="text-slate-700">Reported By:</strong> <span class="text-slate-900">{{ $disciplinaryCase->reportedBy?->name ?? 'N/A' }}</span></p>
                    <p class="text-sm"><strong class="text-slate-700">Created By:</strong> <span class="text-slate-900">{{ $disciplinaryCase->createdBy?->name ?? 'N/A' }}</span></p>
                    <p class="text-sm"><strong class="text-slate-700">Created At:</strong> <span class="text-slate-900">{{ $disciplinaryCase->created_at?->format('F j, Y') }}</span></p>
                    @if($disciplinaryCase->resolution_date)
                        <p class="text-sm"><strong class="text-slate-700">Resolution Date:</strong> <span class="text-slate-900">{{ $disciplinaryCase->resolution_date?->format('F j, Y') }}</span></p>
                    @endif
                </div>
            </x-card>

            <x-card>
                <h2 class="text-lg font-semibold text-slate-900 mb-4">Actions</h2>
                <form method="POST" action="{{ route('discipline.destroy', $disciplinaryCase) }}" onsubmit="return confirm('Are you sure you want to delete this case?');">
                    @csrf
                    @method('DELETE')
                    <x-button type="submit" variant="danger" icon="trash" class="w-full">
                        Delete Case
                    </x-button>
                </form>
            </x-card>
        </div>
    </div>
@endsection