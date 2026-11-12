<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Division;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoUserSeeder extends Seeder
{
    public function run(): void
    {
        $md = Division::where('code', 'MD')->first();
        $branch = Branch::where('name', 'Palembang')->first();
        $role = fn (string $slug) => Role::where('slug', $slug)->value('id');

        $users = [
            // name, username, email, role, division?, jabatan
            ['Administrator', 'admin', 'admin@tag.test', 'administrator', null, 'Administrator'],
            ['Yogi Pratama', 'yogi', 'yogi@tag.test', 'spv', $md, 'Digital Marketing'],
            ['Staff Marcomm', 'staff.marcomm', 'staff.marcomm@tag.test', 'staff', $md, 'Staff Marcomm'],
            ['Devi K', 'devi', 'devi@tag.test', 'admin_keuangan', null, 'Kepala Keuangan'],
            ['Aswani', 'aswani', 'aswani@tag.test', 'admin_finance', null, 'ADH'],
            ['Richie Setiawan', 'richie', 'richie@tag.test', 'direksi', null, 'Direksi'],
        ];

        foreach ($users as [$name, $username, $email, $roleSlug, $division, $jabatan]) {
            User::updateOrCreate(['username' => $username], [
                'name' => $name,
                'email' => $email,
                'password' => Hash::make('password'),
                'role_id' => $role($roleSlug),
                'division_id' => $division?->id,
                'branch_id' => $branch->id,
                'jabatan' => $jabatan,
                'is_active' => true,
            ]);
        }
    }
}
