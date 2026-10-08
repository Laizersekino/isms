<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Permission;
use App\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $permission = Permission::firstOrCreate(['name' => 'users.manage_permissions']);

        $roles = Role::whereIn('name', ['Super Administrator', 'School Administrator'])->get();

        foreach ($roles as $role) {
            $role->permissions()->syncWithoutDetaching([$permission->id]);
        }
    }

    public function down(): void
    {
        $permission = Permission::where('name', 'users.manage_permissions')->first();

        if ($permission) {
            $permission->roles()->detach();
            $permission->delete();
        }
    }
};