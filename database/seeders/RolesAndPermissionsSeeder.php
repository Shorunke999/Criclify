<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // PERMISSIONS
        $permissions = [
            'auth.login',
            'auth.signup',
            'auth.logout',
            'user.view',
            'user.update',
            'admin',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'api',
            ]);
        }

        // ROLES
        $user = Role::firstOrCreate([
            'name' => 'user',
            'guard_name' => 'api',
        ]);

        $admin = Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'api',
        ]);

        // ASSIGN PERMISSIONS
        $user->givePermissionTo([
            'auth.login',
            'auth.logout',
        ]);

        $admin->givePermissionTo(Permission::all());
    }
}
