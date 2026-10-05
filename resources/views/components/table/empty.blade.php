@props(['message' => 'No records found.', 'colspan' => 1, 'icon' => null])

<tr>
    <td colspan="{{ $colspan }}" class="px-4 py-10 text-center text-sm text-slate-500">
        @if($icon)
            <div class="mb-3 flex justify-center text-slate-400">
                <x-icon :name="$icon" size="lg" />
            </div>
        @endif
        {{ $message }}
    </td>
</tr>
