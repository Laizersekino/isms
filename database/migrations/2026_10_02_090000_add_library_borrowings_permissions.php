<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $descriptions = [
            'borrowings.view' => 'View library book borrowings.',
            'borrowings.issue' => 'Issue library books to students and teachers.',
            'borrowings.return' => 'Record library book returns.',
            'borrowings.renew' => 'Renew library book borrowings.',
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
            'borrowings.view' => [
                'Super Administrator',
                'School Administrator',
                'Librarian',
                'Teacher',
            ],
            'borrowings.issue' => [
                'Super Administrator',
                'School Administrator',
                'Librarian',
            ],
            'borrowings.return' => [
                'Super Administrator',
                'School Administrator',
                'Librarian',
            ],
            'borrowings.renew' => [
                'Super Administrator',
                'School Administrator',
                'Librarian',
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

        $legacyPermissions = DB::table('permissions')
            ->whereIn('name', ['books.issue', 'books.return'])
            ->pluck('id', 'name');

        foreach ([
            'books.issue' => 'borrowings.issue',
            'books.return' => 'borrowings.return',
        ] as $legacyName => $newName) {
            if (! isset($legacyPermissions[$legacyName])) {
                continue;
            }

            $legacyRoleIds = DB::table('role_permissions')
                ->where('permission_id', $legacyPermissions[$legacyName])
                ->pluck('role_id');
            $allowedRoleIds = DB::table('roles')
                ->whereIn('name', [
                    'Super Administrator',
                    'School Administrator',
                    'Librarian',
                ])
                ->whereIn('id', $legacyRoleIds)
                ->pluck('id');

            foreach ($allowedRoleIds as $roleId) {
                DB::table('role_permissions')->insertOrIgnore([
                    'role_id' => $roleId,
                    'permission_id' => $permissionIds[$newName],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        $legacyPermissionIds = $legacyPermissions->values();

        DB::table('role_permissions')
            ->whereIn('permission_id', $legacyPermissionIds)
            ->delete();

        DB::table('permissions')
            ->whereIn('id', $legacyPermissionIds)
            ->delete();
    }

    public function down(): void
    {
        $newPermissionIds = DB::table('permissions')
            ->whereIn('name', [
                'borrowings.view',
                'borrowings.issue',
                'borrowings.return',
                'borrowings.renew',
            ])
            ->pluck('id');

        DB::table('role_permissions')
            ->whereIn('permission_id', $newPermissionIds)
            ->delete();

        DB::table('permissions')
            ->whereIn('id', $newPermissionIds)
            ->delete();

        $now = now();
        $legacyDescriptions = [
            'books.issue' => 'Issue library books.',
            'books.return' => 'Return library books.',
        ];

        foreach ($legacyDescriptions as $name => $description) {
            DB::table('permissions')->insertOrIgnore([
                'name' => $name,
                'description' => $description,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $legacyPermissionIds = DB::table('permissions')
            ->whereIn('name', array_keys($legacyDescriptions))
            ->pluck('id', 'name');

        $roles = DB::table('roles')
            ->whereIn('name', ['Super Administrator', 'Librarian'])
            ->get(['id', 'name']);

        foreach ($roles as $role) {
            foreach (['books.issue', 'books.return'] as $permissionName) {
                DB::table('role_permissions')->insertOrIgnore([
                    'role_id' => $role->id,
                    'permission_id' => $legacyPermissionIds[$permissionName],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }
};
