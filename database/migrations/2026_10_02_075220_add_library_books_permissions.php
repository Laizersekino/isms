<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $permissionDescriptions = [
            'books.view' => 'View library book records.',
            'books.create' => 'Create library book records.',
            'books.update' => 'Update library book records.',
            'books.delete' => 'Delete library book records without copies.',
        ];

        foreach ($permissionDescriptions as $name => $description) {
            DB::table('permissions')->insertOrIgnore([
                'name' => $name,
                'description' => $description,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $permissionIds = DB::table('permissions')
            ->whereIn('name', array_keys($permissionDescriptions))
            ->pluck('id', 'name');

        $roles = DB::table('roles')
            ->whereIn('name', [
                'Super Administrator',
                'School Administrator',
                'Librarian',
            ])
            ->get(['id', 'name']);

        foreach ($roles as $role) {
            foreach ($permissionIds as $permissionName => $permissionId) {
                if ($role->name === 'Librarian' && $permissionName === 'books.delete') {
                    continue;
                }

                DB::table('role_permissions')->insertOrIgnore([
                    'role_id' => $role->id,
                    'permission_id' => $permissionId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        $permissionIds = DB::table('permissions')
            ->whereIn('name', [
                'books.view',
                'books.create',
                'books.update',
                'books.delete',
            ])
            ->pluck('id');

        DB::table('role_permissions')
            ->whereIn('permission_id', $permissionIds)
            ->delete();

        DB::table('permissions')
            ->whereIn('name', [
                'books.view',
                'books.create',
                'books.update',
                'books.delete',
            ])
            ->delete();
    }
};
