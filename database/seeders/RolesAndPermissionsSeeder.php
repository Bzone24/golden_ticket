<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = Role::create(['name' => 'admin']);
        $shopkeeper = Role::create(['name' => 'shopkeeper']);
        $user = Role::create(['name' => 'user']);

        // Permissions
        Permission::create(['name' => 'manage shopkeepers']);
        Permission::create(['name' => 'manage users']);
        Permission::create(['name' => 'view own profile']);

        // Assign permissions
        $admin->givePermissionTo(['manage shopkeepers', 'manage users', 'view own profile']);
        $shopkeeper->givePermissionTo(['manage users', 'view own profile']);
        $user->givePermissionTo(['view own profile']);
    }
}
