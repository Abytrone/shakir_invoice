<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

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

        // Create permissions for each module
        $modules = [
            'invoice' => ['view', 'create', 'edit', 'delete', 'send', 'download'],
            'quote' => ['view', 'create', 'edit', 'delete', 'send', 'download', 'convert'],
            'client' => ['view', 'create', 'edit', 'delete'],
            'product' => ['view', 'create', 'edit', 'delete'],
            'report' => ['view'],
            'role' => ['view', 'create', 'edit', 'delete'],
            'user' => ['view', 'create', 'edit', 'delete'],
        ];

        $allPermissions = [];
        foreach ($modules as $module => $actions) {
            foreach ($actions as $action) {
                $permissionName = "{$action}_{$module}";
                $permission = Permission::create(['name' => $permissionName]);
                $allPermissions[] = $permission;
            }
        }

        // Assign permissions to roles
        $superAdmin->givePermissionTo($allPermissions);
        
        $admin->givePermissionTo(array_filter($allPermissions, function($permission) {
            return !str_contains($permission->name, 'role_') && !str_contains($permission->name, 'user_');
        }));

        $manager->givePermissionTo(array_filter($allPermissions, function($permission) {
            return !str_contains($permission->name, 'role_') && 
                   !str_contains($permission->name, 'user_') &&
                   !str_contains($permission->name, 'delete_');
        }));

        $accountant->givePermissionTo([
            'view_invoice', 'create_invoice', 'edit_invoice', 'send_invoice', 'download_invoice',
            'view_quote', 'create_quote', 'edit_quote', 'send_quote', 'download_quote',
            'view_client', 'create_client', 'edit_client',
            'view_product',
            'view_report'
        ]);

        $user->givePermissionTo([
            'view_invoice', 'download_invoice',
            'view_quote', 'download_quote',
            'view_client',
            'view_product'
        ]);
    }
}