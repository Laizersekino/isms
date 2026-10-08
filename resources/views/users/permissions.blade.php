@extends('layouts.app')

@section('title', 'Manage Permissions: ' . $user->name)

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Manage Permissions</h1>
            <p class="text-sm text-slate-600">{{ $user->name }} ({{ $user->email }})</p>
        </div>
        <x-button variant="secondary" :href="route('users.index')">
            Back to Users
        </x-button>
    </div>

    <x-card>
        <form method="POST" action="{{ route('users.permissions.update', $user) }}">
            @csrf
            @method('PUT')

            {{-- Role Permissions --}}
            <div class="mb-8">
                <h2 class="text-lg font-semibold text-slate-900 mb-2">Role Permissions (Inherited)</h2>
                <p class="text-sm text-slate-600 mb-4">These come from the user's roles.</p>
                <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-2">
                    @foreach($user->roles as $role)
                        <div class="p-3 rounded-lg bg-slate-50 border border-slate-200">
                            <p class="font-medium text-sm text-slate-900">{{ $role->name }}</p>
                            <p class="text-xs text-slate-500">{{ $role->permissions->count() }} permissions</p>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Direct Permissions --}}
            <div class="mb-8">
                <h2 class="text-lg font-semibold text-slate-900 mb-2">Direct Permissions (Granted)</h2>
                <p class="text-sm text-slate-600 mb-4">Grant additional permissions beyond role permissions.</p>

                @foreach($allPermissions as $module => $permissions)
                    <div class="mb-4">
                        <h3 class="text-sm font-semibold text-slate-700 uppercase tracking-wide mb-2">{{ $module }}</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2">
                            @foreach($permissions as $permission)
                                <label class="flex items-center gap-2 p-2 rounded hover:bg-slate-50">
                                    <input type="checkbox"
                                           name="permissions[]"
                                           value="{{ $permission->id }}"
                                           @checked($user->permissions->contains('id', $permission->id))
                                           class="rounded border-slate-300 text-primary-600 focus:ring-primary-500">
                                    <span class="text-sm text-slate-700">{{ $permission->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Denied Permissions --}}
            <div class="mb-8">
                <h2 class="text-lg font-semibold text-slate-900 mb-2">Denied Permissions (Revoked)</h2>
                <p class="text-sm text-slate-600 mb-4">Revoke permissions even if the user's role grants them.</p>

                @foreach($allPermissions as $module => $permissions)
                    <div class="mb-4">
                        <h3 class="text-sm font-semibold text-slate-700 uppercase tracking-wide mb-2">{{ $module }}</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2">
                            @foreach($permissions as $permission)
                                <label class="flex items-center gap-2 p-2 rounded hover:bg-slate-50">
                                    <input type="checkbox"
                                           name="denied_permissions[]"
                                           value="{{ $permission->id }}"
                                           @checked($user->deniedPermissions->contains('id', $permission->id))
                                           class="rounded border-slate-300 text-danger-600 focus:ring-danger-500">
                                    <span class="text-sm text-slate-700">{{ $permission->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="flex items-center gap-2">
                <x-button type="submit" variant="primary">
                    <x-icon name="check" class="w-5 h-5" />
                    Save Permissions
                </x-button>
                <x-button variant="secondary" :href="route('users.index')">Cancel</x-button>
            </div>
        </form>
    </x-card>
@endsection