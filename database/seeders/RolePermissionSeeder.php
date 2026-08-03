<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Ensure roles exist
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $superAdminRole = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $leadRole = Role::firstOrCreate(['name' => 'lead', 'guard_name' => 'web']);
        $teamMemberRole = Role::firstOrCreate(['name' => 'team_member', 'guard_name' => 'web']);
        
        $entities = [
            'section',
            'module',
            'sub::module',
            'daily::report',
            'weekly::report',
            'user',
            'shield::role',
            'role',
        ];

        $actions = [
            'view_any',
            'view',
            'create',
            'update',
            'delete',
            'delete_any',
            'force_delete',
            'force_delete_any',
            'restore',
            'restore_any',
            'replicate',
            'reorder',
        ];

        $allPermissions = [];
        foreach ($entities as $entity) {
            foreach ($actions as $action) {
                $allPermissions[] = "{$action}_{$entity}";
            }
        }

        foreach ($allPermissions as $permissionName) {
            Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']);
        }

        // Super Admin gets everything via Gate::before in AppServiceProvider, but we can assign all here anyway
        $superAdminRole->syncPermissions($allPermissions);
        $adminRole->syncPermissions($allPermissions);

        // Section Lead
        $leadPermissions = [
            'view_any_daily::report',
            'view_daily::report',
            'view_any_user',
            'view_user',
            'view_any_weekly::report',
            'view_weekly::report',
            'update_weekly::report',
        ];
        $leadRole->syncPermissions($leadPermissions);

        // Team Member
        $teamMemberPermissions = [
            'view_any_daily::report',
            'view_daily::report',
            'create_daily::report',
            'update_daily::report',
            'delete_daily::report',
            'view_any_weekly::report',
            'view_weekly::report',
            'update_weekly::report',
        ];
        $teamMemberRole->syncPermissions($teamMemberPermissions);
    }
}
