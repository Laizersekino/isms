<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Permission;
use App\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $permissions = [
            'discipline.view',
            'discipline.create',
            'discipline.update',
            'discipline.delete',
        ];

        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name]);
        }

        $rolePermissions = [
            'Super Administrator' => $permissions,
            'School Administrator' => $permissions,
            'Principal' => ['discipline.view', 'discipline.update'],
            'Academic Officer' => ['discipline.view', 'discipline.create', 'discipline.update'],
            'Teacher' => ['discipline.view', 'discipline.create'],
            'Receptionist' => ['discipline.view'],
        ];

        foreach ($rolePermissions as $roleName => $permissionNames) {
            $role = Role::where('name', $roleName)->first();
            if ($role) {
                $permissionIds = Permission::whereIn('name', $permissionNames)->pluck('id');
                $role->permissions()->syncWithoutDetaching($permissionIds);
            }
        }
    }

    public function down(): void
    {
        $permissionNames = [
            'discipline.view',
            'discipline.create',
            'discipline.update',
            'discipline.delete',
        ];

        $permissions = Permission::whereIn('name', $permissionNames)->get();

        foreach ($permissions as $permission) {
            $permission->roles()->detach();
            $permission->delete();
        }
    }
};