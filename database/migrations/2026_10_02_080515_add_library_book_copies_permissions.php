<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $permissionDescriptions = [
            'book_copies.view' => 'View library book copies.',
            'book_copies.create' => 'Create library book copies.',
            'book_copies.update' => 'Update library book copies.',
            'book_copies.delete' => 'Delete library book copies without borrowing history.',
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
                if ($role->name === 'Librarian' && $permissionName === 'book_copies.delete') {
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
                'book_copies.view',
                'book_copies.create',
                'book_copies.update',
                'book_copies.delete',
            ])
            ->pluck('id');

        DB::table('role_permissions')
            ->whereIn('permission_id', $permissionIds)
            ->delete();

        DB::table('permissions')
            ->whereIn('name', [
                'book_copies.view',
                'book_copies.create',
                'book_copies.update',
                'book_copies.delete',
            ])
            ->delete();
    }
};
