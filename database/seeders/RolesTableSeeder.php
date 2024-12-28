<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        // Create the admin roles using firstOrCreate
        $role = Role::firstOrCreate(['name' => 'Super Administrator', 'guard_name' => 'sanctum']);

        // Retrieve all permissions
        $permissions = Permission::all();

        // // Sync all permissions to the admin role
        $role->syncPermissions($permissions);
        
        // Retrieve the admin role
        $adminRole = Role::where('name', 'Super Administrator')->first();

        // Create admin user
        $admin = User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@gmail.com',
            'user_enabled' => 1,
            'password' => Hash::make('admin123@'), // Use a secure password here
        ]);

        // Assign the admin role to the created user
        $admin->assignRole($adminRole);
    }
}
