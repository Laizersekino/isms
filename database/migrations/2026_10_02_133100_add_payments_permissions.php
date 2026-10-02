<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $descriptions = [
            'payments.view' => 'View student payments.',
            'payments.create' => 'Record student payments.',
            'payments.reverse' => 'Reverse completed student payments.',
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
            'payments.view' => [
                'Super Administrator',
                'School Administrator',
                'Finance Officer',
                'Principal',
                'Receptionist',
            ],
            'payments.create' => [
                'Super Administrator',
                'School Administrator',
                'Finance Officer',
            ],
            'payments.reverse' => [
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
            ->whereIn('name', [
                'payments.view',
                'payments.create',
                'payments.reverse',
            ])
            ->pluck('id');

        DB::table('role_permissions')
            ->whereIn('permission_id', $permissionIds)
            ->delete();

        DB::table('permissions')
            ->whereIn('id', $permissionIds)
            ->delete();
    }
};
