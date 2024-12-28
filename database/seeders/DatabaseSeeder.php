<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // Seed permissions first
        $this->call(PermissionsSeeder::class);

        // Seed roles after permissions
        $this->call(RolesTableSeeder::class);
    }
}
