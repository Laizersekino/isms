@extends('layouts.app')

@section('title', 'Create Fee Structure')
@section('content')
<div class="mx-auto max-w-5xl space-y-6">
    <h1 class="text-2xl font-bold text-slate-900">Create Fee Structure</h1>
    <x-card>
        <form method="POST" action="{{ route('fee-structures.store') }}" class="space-y-6">
            @csrf
            @include('fee_structures.form', [
                'feeStructure' => null,
                'items' => old('items', [['name' => '', 'amount' => '', 'is_mandatory' => 1, 'description' => '']]),
            ])
            <div class="flex flex-wrap gap-3 border-t border-slate-200 pt-5">
                <x-button type="submit" icon="check">Save Fee Structure</x-button>
                <a href="{{ route('fee-structures.index') }}" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">Cancel</a>
            </div>
        </form>
    </x-card>
</div>
@endsection
