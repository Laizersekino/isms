@props(['name' => '', 'src' => null, 'size' => 'md', 'alt' => null])

@php
    $sizeClasses = [
        'sm' => 'size-8 text-xs',
        'md' => 'size-10 text-sm',
        'lg' => 'size-14 text-lg',
    ];
    $nameParts = preg_split('/\s+/', trim($name), flags: PREG_SPLIT_NO_EMPTY) ?: [];
    $initials = collect($nameParts)
        ->when(count($nameParts) > 1, fn ($parts) => collect([$parts->first(), $parts->last()]))
        ->map(fn ($part) => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($part, 0, 1)))
        ->join('');
    $initials = $initials ?: '?';
@endphp

@if($src)
    <img
        src="{{ $src }}"
        alt="{{ $alt ?? $name }}"
        {{ $attributes->class('inline-flex shrink-0 rounded-full object-cover '.$sizeClasses[$size]) }}
    >
@else
    <span
        {{ $attributes->class('inline-flex shrink-0 items-center justify-center rounded-full bg-primary-100 font-semibold text-primary-700 '.$sizeClasses[$size]) }}
        role="img"
        aria-label="{{ $alt ?? $name }}"
    >{{ $initials }}</span>
@endif
