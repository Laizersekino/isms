@props(['variant' => 'default'])

@php
    $variants = [
        'default' => 'bg-slate-100 text-slate-700 ring-slate-600/10',
        'gray' => 'bg-slate-100 text-slate-700 ring-slate-600/10',
        'primary' => 'bg-primary-50 text-primary-700 ring-primary-600/20',
        'success' => 'bg-success-50 text-success-700 ring-success-600/20',
        'warning' => 'bg-warning-50 text-warning-700 ring-warning-600/20',
        'danger' => 'bg-danger-50 text-danger-700 ring-danger-600/20',
        'info' => 'bg-info-50 text-info-700 ring-info-600/20',
    ];
@endphp

<span {{ $attributes->class('inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset '.($variants[$variant] ?? $variants['default'])) }}>
    {{ $slot }}
</span>
