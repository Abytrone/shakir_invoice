<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Create roles
        $superAdmin = Role::create(['name' => 'super_admin']);
        $admin = Role::create(['name' => 'admin']);
        $manager = Role::create(['name' => 'manager']);
        $accountant = Role::create(['name' => 'accountant']);
        $user = Role::create(['name' => 'user']);

        // Resource permissions based on Filament Shield's structure
        $resources = ['invoice', 'quote', 'client', 'product', 'role', 'user'];
        $resourcePermissions = [
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
        ];

        // Pages and custom action permissions for specific resources
        $customPermissions = [
            'invoice' => ['send_invoice', 'download_invoice'],
            'quote' => ['send_quote', 'download_quote', 'convert_quote'],
        ];

        $allPermissions = [];

        // Create resource permissions
        foreach ($resources as $resource) {
            foreach ($resourcePermissions as $action) {
                $permissionName = "{$action}_{$resource}";
                $permission = Permission::create(['name' => $permissionName]);
                $allPermissions[] = $permission;
            }
        }

        // Create custom permissions
        foreach ($customPermissions as $resource => $actions) {
            foreach ($actions as $permissionName) {
                $permission = Permission::create(['name' => $permissionName]);
                $allPermissions[] = $permission;
            }
        }

        // Assign permissions to roles
        $superAdmin->givePermissionTo($allPermissions);

        $admin->givePermissionTo(array_filter($allPermissions, function ($permission) {
            return ! str_contains($permission->name, '_role') && ! str_contains($permission->name, '_user');
        }));

        $manager->givePermissionTo(array_filter($allPermissions, function ($permission) {
            return ! str_contains($permission->name, '_role') &&
                   ! str_contains($permission->name, '_user') &&
                   ! str_contains($permission->name, 'delete_any_') &&
                   ! str_contains($permission->name, 'force_delete_');
        }));

        $accountant->givePermissionTo([
            'view_any_invoice', 'view_invoice', 'create_invoice', 'update_invoice', 'send_invoice', 'download_invoice',
            'view_any_quote', 'view_quote', 'create_quote', 'update_quote', 'send_quote', 'download_quote',
            'view_any_client', 'view_client', 'create_client', 'update_client',
            'view_any_product', 'view_product',
        ]);

        $user->givePermissionTo([
            'view_any_invoice', 'view_invoice', 'download_invoice',
            'view_any_quote', 'view_quote', 'download_quote',
            'view_any_client', 'view_client',
            'view_any_product', 'view_product',
        ]);
    }
}
