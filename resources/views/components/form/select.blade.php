@props(['name', 'label' => null])

<div class="space-y-1.5">
    @if($label)
        <label for="{{ $attributes->get('id', $name) }}" class="block text-sm font-medium text-slate-700">{{ $label }}</label>
    @endif
    <select
        id="{{ $attributes->get('id', $name) }}"
        name="{{ $name }}"
        aria-invalid="{{ $errors->has($name) ? 'true' : 'false' }}"
        {{ $attributes->except(['id'])->class([
            'block w-full rounded-lg border bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none focus:border-primary-500 focus:ring-4 focus:ring-primary-100',
            'border-red-300' => $errors->has($name),
            'border-slate-300' => !$errors->has($name),
        ]) }}
    >{{ $slot }}</select>
    <x-form.error :name="$name" />
</div>
