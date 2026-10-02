@props(['name'])

@error($name)
    <p id="{{ $name }}-error" class="text-sm text-red-700">{{ $message }}</p>
@enderror
