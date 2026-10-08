@extends('layouts.app')

@section('title', 'Users')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-slate-900">Users</h1>
        <x-button variant="primary" :href="route('users.create')">
            <x-icon name="plus" class="w-5 h-5" />
            Create User
        </x-button>
    </div>

    <x-card>
        <x-table.index>
            <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-600">
                <tr>
                    <th scope="col" class="px-4 py-3">Name</th>
                    <th scope="col" class="px-4 py-3">Email</th>
                    <th scope="col" class="px-4 py-3">Roles</th>
                    <th scope="col" class="px-4 py-3">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                @forelse($users as $user)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3 font-medium">{{ $user->name }}</td>
                        <td class="px-4 py-3">{{ $user->email }}</td>
                        <td class="px-4 py-3">
                            @foreach($user->roles as $role)
                                <x-badge variant="info">{{ $role->name }}</x-badge>
                            @endforeach
                        </td>
                        <td class="px-4 py-3">
                            <a href="{{ route('users.permissions', $user) }}" 
                               class="text-primary-600 hover:text-primary-700 text-sm font-medium">
                                Manage Permissions →
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-8">
                            <x-table.empty icon="users" title="No users found." />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </x-table.index>

        <x-table.pagination :paginator="$users" />
    </x-card>
@endsection