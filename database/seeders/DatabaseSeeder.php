<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            OrganizationSeeder::class,
            MarcommFlowSeeder::class,
            DemoUserSeeder::class, // hapus/ganti sebelum production
        ]);
    }
}
