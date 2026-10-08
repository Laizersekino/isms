<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        $users = User::with('roles')->paginate(20);
        return view('users.index', compact('users'));
    }

    public function create(): View
    {
        $roles = Role::with('permissions')->get();

        $allPermissions = Permission::orderBy('name')->get()->groupBy(function ($permission) {
            return explode('.', $permission->name)[0];
        });

        return view('users.create', compact('roles', 'allPermissions'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role_id' => ['required', 'exists:roles,id'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['exists:permissions,id'],
            'denied_permissions' => ['nullable', 'array'],
            'denied_permissions.*' => ['exists:permissions,id'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        // Assign role
        $user->roles()->attach($validated['role_id']);

        // Sync direct permissions
        $user->permissions()->sync($validated['permissions'] ?? []);

        // Sync denied permissions
        $user->deniedPermissions()->sync($validated['denied_permissions'] ?? []);

        return redirect()
            ->route('users.index')
            ->with('success', 'User created successfully with assigned role and permissions.');
    }

    public function permissions(User $user): View
    {
        $user->load(['roles.permissions', 'permissions', 'deniedPermissions']);

        $allPermissions = Permission::orderBy('name')->get()->groupBy(function ($permission) {
            return explode('.', $permission->name)[0];
        });

        return view('users.permissions', compact('user', 'allPermissions'));
    }

    public function updatePermissions(User $user, Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['exists:permissions,id'],
            'denied_permissions' => ['nullable', 'array'],
            'denied_permissions.*' => ['exists:permissions,id'],
        ]);

        $user->permissions()->sync($validated['permissions'] ?? []);
        $user->deniedPermissions()->sync($validated['denied_permissions'] ?? []);

        return redirect()
            ->route('users.permissions', $user)
            ->with('success', 'Permissions updated successfully.');
    }
}