<header class="sticky top-0 z-30 flex h-16 items-center justify-between border-b border-slate-200 bg-white/95 px-4 shadow-sm backdrop-blur sm:px-6 lg:px-8">
    <div class="flex items-center gap-3">
        <button type="button" class="rounded-lg p-2 text-slate-600 hover:bg-slate-100 lg:hidden" @click="sidebarOpen = true" aria-controls="app-sidebar" aria-label="Open navigation">
            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>
        <p class="hidden text-sm font-medium text-slate-500 sm:block">@yield('page-description', 'School management')</p>
    </div>

    <div class="flex items-center gap-3">
        @if(auth()->user()->hasPermission('announcements.view') && \Illuminate\Support\Facades\Route::has('announcements.index'))
            <a href="{{ route('announcements.index') }}" class="relative rounded-lg p-2 text-slate-600 hover:bg-slate-100" aria-label="Announcements">
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 11v2a2 2 0 0 0 2 2h2l3 4h2v-6.2a7 7 0 0 0 7-6.8V5a2 2 0 0 0-2-2h-1L7 8H5a2 2 0 0 0-2 3Zm16-1a4 4 0 0 1 0 4"/></svg>
            </a>
        @endif

        <div class="hidden text-right sm:block">
            <p class="text-sm font-semibold text-slate-800">{{ auth()->user()->name }}</p>
            <p class="text-xs text-slate-500">{{ auth()->user()->roles->pluck('name')->join(', ') ?: 'No role assigned' }}</p>
        </div>

        <a href="{{ route('profile.edit') }}" class="rounded-full bg-primary-50 px-3 py-2 text-sm font-semibold text-primary-700 hover:bg-primary-100" aria-label="Edit profile">
            {{ str($userName = auth()->user()->name)->substr(0, 1)->upper() }}
        </a>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Log out</button>
        </form>
    </div>
</header>
