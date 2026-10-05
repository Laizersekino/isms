@extends('layouts.app')

@section('title', 'Edit Fee Structure')
@section('content')
<div class="mx-auto max-w-5xl space-y-6">
    <h1 class="text-2xl font-bold text-slate-900">Edit Fee Structure</h1>
    <x-card>
        <form method="POST" action="{{ route('fee-structures.update', $feeStructure) }}" class="space-y-6">
            @csrf
            @method('PUT')
            @include('fee_structures.form', [
                'feeStructure' => $feeStructure,
                'items' => old('items', $feeStructure->items->map(fn ($item) => [
                    'name' => $item->name,
                    'amount' => $item->amount,
                    'is_mandatory' => $item->is_mandatory,
                    'description' => $item->description,
                ])->all()),
            ])
            <div class="flex flex-wrap gap-3 border-t border-slate-200 pt-5">
                <x-button type="submit" icon="check">Update Fee Structure</x-button>
                <a href="{{ route('fee-structures.show', $feeStructure) }}" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">Cancel</a>
            </div>
        </form>
    </x-card>
</div>
@endsection
