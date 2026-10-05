@props(['name', 'title' => null, 'size' => 'md'])

<svg
    {{ $attributes->class([
        'shrink-0',
        'size-4' => $size === 'sm',
        'size-5' => $size === 'md',
        'size-6' => $size === 'lg',
    ]) }}
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    stroke-width="1.8"
    stroke-linecap="round"
    stroke-linejoin="round"
    @if($title) role="img" aria-label="{{ $title }}" @else aria-hidden="true" @endif
>
    @switch($name)
        @case('academic-cap')
            <path d="m3 9 9-5 9 5-9 5-9-5Z" />
            <path d="M7 11.2V16c3 2.7 7 2.7 10 0v-4.8M21 9v6" />
            @break
        @case('arrow-down-tray')
            <path d="M12 3v12m0 0 4-4m-4 4-4-4" />
            <path d="M4 17v3h16v-3" />
            @break
        @case('arrow-right')
            <path d="M5 12h14m-6-6 6 6-6 6" />
            @break
        @case('arrow-right-on-rectangle')
            <path d="M10 17l5-5-5-5m5 5H3" />
            <path d="M12 3h7a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-7" />
            @break
        @case('bell')
            <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9m-8 12h4" />
            @break
        @case('bars-3')
            <path d="M4 6h16M4 12h16M4 18h16" />
            @break
        @case('book-open')
            <path d="M12 7v14m0-14C9 4 5 4 3 5v14c3-1 6-1 9 2m0-14c3-3 7-3 9-2v14c-3-1-6-1-9 2" />
            @break
        @case('books')
            <path d="M4 4h5v16H4zM11 3h5v17h-5zM18 5h3v15h-3z" />
            <path d="M5.5 7h2m5-1h2m5 2h1" />
            @break
        @case('calendar')
        @case('calendar-days')
            <rect x="3" y="5" width="18" height="16" rx="2" />
            <path d="M16 3v4M8 3v4M3 10h18M8 14h.01M12 14h.01M16 14h.01M8 17h.01M12 17h.01" />
            @break
        @case('chart-bar')
            <path d="M4 20V10m5 10V4m5 16v-7m5 7V7M2 20h20" />
            @break
        @case('check')
            <path d="m5 12 4 4L19 6" />
            @break
        @case('clipboard-document-check')
            <path d="M9 4h6l1 2h3v15H5V6h3l1-2Z" />
            <path d="m9 14 2 2 4-4M9 9h6" />
            @break
        @case('currency-dollar')
            <path d="M12 2v20m5-16H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6" />
            @break
        @case('eye')
            <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z" />
            <circle cx="12" cy="12" r="3" />
            @break
        @case('home')
            <path d="m3 10 9-7 9 7v10a1 1 0 0 1-1 1h-6v-7h-4v7H4a1 1 0 0 1-1-1V10Z" />
            @break
        @case('inbox')
            <path d="M4 4h16l2 11v5H2v-5L4 4Z" />
            <path d="M2 15h6l2 3h4l2-3h6M9 9h6" />
            @break
        @case('megaphone')
            <path d="m3 11 18-6v14L3 13v-2Zm0 2 2 7h5l-2-6m13-6a4 4 0 0 1 0 8" />
            @break
        @case('pencil-square')
            <path d="M13 5H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8" />
            <path d="m16 3 5 5-9 9-5 1 1-5 8-10Z" />
            @break
        @case('printer')
            <path d="M6 8V3h12v5M6 17H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2" />
            <path d="M6 14h12v7H6zM18 11h.01" />
            @break
        @case('plus')
            <path d="M12 5v14m-7-7h14" />
            @break
        @case('trash')
            <path d="M3 6h18m-2 0-1 14H6L5 6m4 0V4h6v2m-5 4v6m4-6v6" />
            @break
        @case('user-circle')
            <circle cx="12" cy="12" r="9" />
            <circle cx="12" cy="9" r="3" />
            <path d="M6.5 19a6 6 0 0 1 11 0" />
            @break
        @case('user-group')
            <path d="M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2m13-6a4 4 0 0 1 3 4v2m-10-9a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm7-8a4 4 0 0 1 0 8" />
            @break
        @case('users')
            <path d="M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2m13-6a4 4 0 0 1 3 4v2m-10-9a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm7-8a4 4 0 0 1 0 8" />
            @break
        @default
            <circle cx="12" cy="12" r="9" />
            <path d="M12 11v5m0-8h.01" />
    @endswitch
</svg>
