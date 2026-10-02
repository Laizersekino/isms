@props(['name', 'label'])

<label for="{{ $attributes->get('id', $name) }}" class="inline-flex items-start gap-2.5 text-sm text-slate-700">
    <input
        id="{{ $attributes->get('id', $name) }}"
        name="{{ $name }}"
        type="checkbox"
        value="{{ $attributes->get('value', 1) }}"
        @checked(old($name, $attributes->get('checked', false)))
        {{ $attributes->except(['id', 'value', 'checked'])->class('mt-0.5 size-4 rounded border-slate-300 text-primary-600 focus:ring-primary-500') }}
    >
    <span>{{ $label }}</span>
</label>
<x-form.error :name="$name" />
