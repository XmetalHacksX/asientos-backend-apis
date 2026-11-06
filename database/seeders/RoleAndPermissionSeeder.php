<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Create Permissions
        $permissions = [
            'manage-campuses',
            'manage-spaces',
            'manage-events',
            'manage-users',
            'sell-tickets',
            'validate-tickets',
            'view-dashboard',
        ];

        foreach ($permissions as $permissionName) {
            Permission::findOrCreate($permissionName, 'web');
        }

        // Create Roles and assign permissions
        $adminRole = Role::findOrCreate('admin', 'web');
        $adminRole->syncPermissions(Permission::all());

        $taquillaRole = Role::findOrCreate('taquilla', 'web');
        $taquillaRole->syncPermissions(['sell-tickets', 'view-dashboard']);

        $validadorRole = Role::findOrCreate('validador', 'web');
        $validadorRole->syncPermissions(['validate-tickets']);
    }
}
