@extends('layouts.app')

@section('title', 'Create User')

@section('content')
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px;">
        <h1 style="font-size: 24px; font-weight: 700; color: #0f172a;">Create User</h1>
        <a href="/users" 
           style="display: inline-block; padding: 10px 20px; background: #e2e8f0; color: #334155; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 14px;">
            ← Back to Users
        </a>
    </div>

    <x-card>
        <form method="POST" action="/users">
            @csrf

            {{-- User Information --}}
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 32px;">
                <x-form.input name="name" label="Name" required />
                <x-form.input name="email" label="Email" type="email" required />
                <x-form.input name="password" label="Password" type="password" required />
                <x-form.input name="password_confirmation" label="Confirm Password" type="password" required />
            </div>

            {{-- Role Selection --}}
            <div style="margin-bottom: 32px;">
                <h2 style="font-size: 18px; font-weight: 600; color: #0f172a; margin-bottom: 8px;">Assign Role</h2>
                <p style="font-size: 14px; color: #475569; margin-bottom: 16px;">Select a role for this user. You can customize its permissions below.</p>
                <x-form.select name="role_id" label="Role" id="role-select" required>
                    <option value="">Select a role...</option>
                    @foreach($roles as $role)
                        <option value="{{ $role->id }}" data-permissions="{{ $role->permissions->pluck('id') }}">
                            {{ $role->name }}
                        </option>
                    @endforeach
                </x-form.select>
            </div>

            {{-- Permissions Section --}}
            <div id="permissions-section" style="margin-bottom: 32px; display: none;">
                <h2 style="font-size: 18px; font-weight: 600; color: #0f172a; margin-bottom: 8px;">Permissions for this Role</h2>
                <p style="font-size: 14px; color: #475569; margin-bottom: 16px;">Check or uncheck permissions as needed for this specific user.</p>

                @foreach($allPermissions as $module => $permissions)
                    <div style="margin-bottom: 16px;">
                        <h3 style="font-size: 14px; font-weight: 600; color: #334155; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 8px;">{{ $module }}</h3>
                        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px;">
                            @foreach($permissions as $permission)
                                <label style="display: flex; align-items: center; gap: 8px; padding: 8px; border-radius: 4px;">
                                    <input type="checkbox"
                                           name="permissions[]"
                                           value="{{ $permission->id }}"
                                           class="permission-checkbox"
                                           style="border-radius: 4px; border: 1px solid #cbd5e1;">
                                    <span style="font-size: 14px; color: #334155;">{{ $permission->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

            <div style="display: flex; align-items: center; gap: 8px;">
                <button type="submit" 
                        style="display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 10px 20px; background: #2563eb; color: white; border: none; border-radius: 8px; font-weight: 600; font-size: 14px; cursor: pointer;">
                    Create User
                </button>
                <a href="/users" 
                   style="display: inline-block; padding: 10px 20px; background: #e2e8f0; color: #334155; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 14px;">
                    Cancel
                </a>
            </div>
        </form>
    </x-card>

    {{-- JavaScript for Role Selection --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const roleSelect = document.getElementById('role-select');
            const permissionsSection = document.getElementById('permissions-section');
            const permissionCheckboxes = document.querySelectorAll('.permission-checkbox');

            if (roleSelect) {
                roleSelect.addEventListener('change', function() {
                    const selectedOption = this.options[this.selectedIndex];
                    const permissions = selectedOption.getAttribute('data-permissions');

                    if (permissions) {
                        permissionsSection.style.display = 'block';
                        try {
                            const permissionIds = JSON.parse(permissions);
                            permissionCheckboxes.forEach(function(checkbox) {
                                checkbox.checked = permissionIds.includes(parseInt(checkbox.value));
                            });
                        } catch (e) {
                            console.error('Error parsing permissions:', e);
                        }
                    } else {
                        permissionsSection.style.display = 'none';
                        permissionCheckboxes.forEach(function(checkbox) {
                            checkbox.checked = false;
                        });
                    }
                });
            }
        });
    </script>
@endsection