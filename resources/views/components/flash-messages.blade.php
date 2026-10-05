@php
    $messages = [
        'success' => ['Success', 'border-success-100 bg-success-50 text-success-700'],
        'status' => ['Status', 'border-info-100 bg-info-50 text-info-700'],
        'error' => ['Error', 'border-danger-100 bg-danger-50 text-danger-700'],
        'warning' => ['Notice', 'border-warning-100 bg-warning-50 text-warning-700'],
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
        <div role="alert" class="rounded-xl border border-danger-100 bg-danger-50 px-4 py-3 text-sm text-danger-700">
            <p class="font-semibold">Please review the following:</p>
            <ul class="mt-2 list-inside list-disc space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
</div>
