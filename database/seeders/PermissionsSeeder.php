<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $permissionGroups = [
            'Users' => [
                'User Menu', 'User View', 'User Add', 'User Edit', 'User Delete'
            ],
            'Roles' => [
                'Role Menu', 'Role View', 'Role Add', 'Role Edit', 'Role Delete',
            ],
            'Pages' => [
                'Page Menu', 'Page View', 'Page Add', 'Page Edit', 'Page Delete',
            ],
            'WebContents' => [
                'WebContent Menu', 'WebContent View', 'WebContent Add', 'WebContent Edit'
            ]
        ];    
        
        // Create the permissions using firstOrCreate
        foreach ($permissionGroups as $group => $permissions) {
            foreach ($permissions as $permission) {
                Permission::Create([
                    'name' => $permission,
                    'guard_name' => 'sanctum',
                    'group' => $group, // Save the group
                ]);
            }
        }        
    }
}
