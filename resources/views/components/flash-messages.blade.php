@php
    $messages = [
        'success' => ['Success', 'border-emerald-200 bg-emerald-50 text-emerald-800'],
        'status' => ['Status', 'border-blue-200 bg-blue-50 text-blue-800'],
        'error' => ['Error', 'border-red-200 bg-red-50 text-red-800'],
        'warning' => ['Notice', 'border-amber-200 bg-amber-50 text-amber-900'],
    ];
@endphp

<div class="mb-5 space-y-3" aria-live="polite">
    @foreach($messages as $key => [$label, $classes])
        @if(session()->has($key))
            <div role="status" class="rounded-xl border px-4 py-3 text-sm {{ $classes }}">
                <span class="font-semibold">{{ $label }}:</span> {{ session($key) }}
            </div>
        @endif
    @endforeach
    @if($errors->any())
        <div role="alert" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            <p class="font-semibold">Please review the following:</p>
            <ul class="mt-2 list-inside list-disc space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
</div>
