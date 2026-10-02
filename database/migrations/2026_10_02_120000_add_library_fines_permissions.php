<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $descriptions = [
            'fines.view' => 'View library fines and payment history.',
            'fines.pay' => 'Record full payment of library fines.',
            'fines.waive' => 'Waive unpaid library fines.',
        ];

        foreach ($descriptions as $name => $description) {
            DB::table('permissions')->insertOrIgnore([
                'name' => $name,
                'description' => $description,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $permissionIds = DB::table('permissions')
            ->whereIn('name', array_keys($descriptions))
            ->pluck('id', 'name');

        $rolePermissions = [
            'fines.view' => [
                'Super Administrator',
                'School Administrator',
                'Librarian',
                'Accountant',
                'Finance Officer',
            ],
            'fines.pay' => [
                'Super Administrator',
                'School Administrator',
                'Accountant',
                'Finance Officer',
            ],
            'fines.waive' => [
                'Super Administrator',
                'School Administrator',
            ],
        ];

        foreach ($rolePermissions as $permissionName => $roleNames) {
            $roleIds = DB::table('roles')
                ->whereIn('name', $roleNames)
                ->pluck('id');

            foreach ($roleIds as $roleId) {
                DB::table('role_permissions')->insertOrIgnore([
                    'role_id' => $roleId,
                    'permission_id' => $permissionIds[$permissionName],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        $permissionIds = DB::table('permissions')
            ->whereIn('name', ['fines.view', 'fines.pay', 'fines.waive'])
            ->pluck('id');

        DB::table('role_permissions')
            ->whereIn('permission_id', $permissionIds)
            ->delete();

        DB::table('permissions')
            ->whereIn('id', $permissionIds)
            ->delete();
    }
};
