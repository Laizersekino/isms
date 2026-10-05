@props(['type' => 'button', 'variant' => 'primary', 'icon' => null])

@php
    $variants = [
        'primary' => 'bg-primary-600 text-white hover:bg-primary-700 focus-visible:ring-primary-500',
        'secondary' => 'border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 focus-visible:ring-slate-400',
        'danger' => 'bg-danger-600 text-white hover:bg-danger-700 focus-visible:ring-danger-500',
    ];
@endphp

<button type="{{ $type }}" {{ $attributes->class('inline-flex items-center justify-center gap-2 rounded-lg px-4 py-2.5 text-sm font-semibold shadow-sm outline-none transition focus-visible:ring-4 disabled:cursor-not-allowed disabled:opacity-50 '.$variants[$variant]) }}>
    @if($icon)
        <x-icon :name="$icon" size="sm" />
    @endif
    {{ $slot }}
</button>
