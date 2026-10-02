@props(['message' => 'No records found.', 'colspan' => 1])

<tr>
    <td colspan="{{ $colspan }}" class="px-4 py-10 text-center text-sm text-slate-500">
        {{ $message }}
    </td>
</tr>
