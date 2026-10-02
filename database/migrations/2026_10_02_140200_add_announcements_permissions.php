<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $descriptions = [
            'announcements.view' => 'View announcements visible to the user.',
            'announcements.create' => 'Create announcements.',
            'announcements.update' => 'Update owned announcements.',
            'announcements.delete' => 'Delete announcements.',
            'announcements.publish' => 'Publish and archive announcements.',
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

        $allRoleIds = DB::table('roles')->pluck('id');
        foreach ($allRoleIds as $roleId) {
            DB::table('role_permissions')->insertOrIgnore([
                'role_id' => $roleId,
                'permission_id' => $permissionIds['announcements.view'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $rolePermissions = [
            'announcements.create' => [
                'Super Administrator',
                'School Administrator',
                'Principal',
                'Academic Officer',
            ],
            'announcements.update' => [
                'Super Administrator',
                'School Administrator',
                'Principal',
            ],
            'announcements.delete' => [
                'Super Administrator',
                'School Administrator',
            ],
            'announcements.publish' => [
                'Super Administrator',
                'School Administrator',
                'Principal',
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
                'announcements.view',
                'announcements.create',
                'announcements.update',
                'announcements.delete',
                'announcements.publish',
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
