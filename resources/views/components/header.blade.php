<header class="sticky top-0 z-30 flex h-16 items-center justify-between border-b border-slate-200 bg-white/95 px-4 shadow-sm backdrop-blur sm:px-6 lg:px-8">
    <div class="flex items-center gap-3">
        <button type="button" class="rounded-lg p-2 text-slate-600 hover:bg-slate-100 lg:hidden" @click="sidebarOpen = true" aria-controls="app-sidebar" aria-label="Open navigation">
            <x-icon name="bars-3" />
        </button>
        <p class="hidden text-sm font-medium text-slate-500 sm:block">@yield('page-description', 'School management')</p>
    </div>

    <div class="flex items-center gap-3">
        @if(auth()->check())
            @if(auth()->user()->hasPermission('announcements.view') && \Illuminate\Support\Facades\Route::has('announcements.index'))
                <a href="{{ route('announcements.index') }}" class="relative rounded-lg p-2 text-slate-600 hover:bg-slate-100" aria-label="Announcements">
                    <x-icon name="bell" />
                </a>
            @endif

            <div class="hidden text-right sm:block">
                <x-icon name="user-circle" size="sm" class="mb-0.5 inline align-middle text-slate-500" />
                <p class="text-sm font-semibold text-slate-800">{{ auth()->user()->name }}</p>
                <p class="text-xs text-slate-500">{{ auth()->user()->roles->pluck('name')->join(', ') ?: 'No role assigned' }}</p>
            </div>

            <a href="{{ route('profile.edit') }}" class="rounded-full focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-primary-100" aria-label="Edit profile">
                <x-avatar :name="auth()->user()->name" size="sm" />
            </a>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                    <x-icon name="arrow-right-on-rectangle" size="sm" />
                    Log out
                </button>
            </form>
        @endif
    </div>
</header>