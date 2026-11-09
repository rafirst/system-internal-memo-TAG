<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            'administrator' => 'Administrator',
            'staff' => 'Staff',
            'spv' => 'Supervisor',
            'admin_keuangan' => 'Admin Keuangan',
            'admin_finance' => 'Admin Finance',
            'adh' => 'ADH',
            'admin_kontrak' => 'Admin Kontrak',
            'hrd' => 'HRD',
            'audit' => 'Audit',
            'kepala_cabang' => 'Kepala Cabang',
            'direksi' => 'Direksi',
        ];

        foreach ($roles as $slug => $label) {
            Role::updateOrCreate(['slug' => $slug], [
                'name' => $label,
                'label' => $label,
            ]);
        }
    }
}
