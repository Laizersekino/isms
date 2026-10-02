<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $descriptions = [
            'fee_structures.view' => 'View class fee structures.',
            'fee_structures.create' => 'Create class fee structures.',
            'fee_structures.update' => 'Update class fee structures.',
            'fee_structures.delete' => 'Archive class fee structures.',
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
            'fee_structures.view' => [
                'Super Administrator',
                'School Administrator',
                'Finance Officer',
                'Principal',
            ],
            'fee_structures.create' => [
                'Super Administrator',
                'School Administrator',
                'Finance Officer',
            ],
            'fee_structures.update' => [
                'Super Administrator',
                'School Administrator',
                'Finance Officer',
            ],
            'fee_structures.delete' => [
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
                'fee_structures.view',
                'fee_structures.create',
                'fee_structures.update',
                'fee_structures.delete',
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
