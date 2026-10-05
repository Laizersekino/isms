@props([
    'title' => 'Nothing here yet',
    'message' => null,
    'icon' => 'inbox',
])

<div {{ $attributes->class('flex flex-col items-center justify-center rounded-xl border border-dashed border-slate-300 bg-white px-6 py-12 text-center') }}>
    <span class="mb-4 inline-flex rounded-full bg-primary-50 p-3 text-primary-600">
        <x-icon :name="$icon" size="lg" />
    </span>
    <h2 class="text-base font-semibold text-slate-900">{{ $title }}</h2>
    @if($message)
        <p class="mt-2 max-w-md text-sm text-slate-600">{{ $message }}</p>
    @endif
    @if(trim((string) $slot) !== '')
        <div class="mt-5">{{ $slot }}</div>
    @endif
</div>
