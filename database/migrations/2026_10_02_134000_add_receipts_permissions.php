<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $descriptions = [
            'receipts.view' => 'View payment receipts.',
            'receipts.print' => 'Print or download payment receipts.',
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
            'receipts.view' => [
                'Super Administrator',
                'School Administrator',
                'Finance Officer',
                'Principal',
                'Receptionist',
            ],
            'receipts.print' => [
                'Super Administrator',
                'School Administrator',
                'Finance Officer',
                'Receptionist',
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
            ->whereIn('name', ['receipts.view', 'receipts.print'])
            ->pluck('id');

        DB::table('role_permissions')
            ->whereIn('permission_id', $permissionIds)
            ->delete();

        DB::table('permissions')
            ->whereIn('id', $permissionIds)
            ->delete();
    }
};
