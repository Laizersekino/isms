@props(['title' => 'Dialog', 'closeLabel' => 'Close'])

<div x-data="{ open: false }">
    <button type="button" @click="open = true" class="inline-flex">
        {{ $trigger ?? 'Open' }}
    </button>

    <div
        x-cloak
        x-show="open"
        x-transition.opacity
        class="fixed inset-0 z-[60] flex items-center justify-center bg-slate-950/50 p-4"
        role="dialog"
        aria-modal="true"
        aria-label="{{ $title }}"
        @keydown.escape.window="open = false"
        @click.self="open = false"
    >
        <section x-show="open" x-transition class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl">
            <div class="mb-4 flex items-center justify-between gap-4">
                <h2 class="text-lg font-semibold text-slate-900">{{ $title }}</h2>
                <button type="button" @click="open = false" class="rounded-lg px-3 py-1.5 text-sm text-slate-600 hover:bg-slate-100">{{ $closeLabel }}</button>
            </div>
            <div>{{ $slot }}</div>
        </section>
    </div>
</div>
