<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Division;
use Illuminate\Database\Seeder;

class OrganizationSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Palembang', 'Head Office', 'Polygon', 'Lubuk Linggau', 'Prabumulih', 'Muara Enim'] as $name) {
            // Kota untuk tanggal di PDF. Sesuaikan jika nama cabang != nama kota.
            Branch::updateOrCreate(['name' => $name], ['city' => $name === 'Head Office' ? 'Palembang' : $name]);
        }

        // Saat ini hanya Marcomm. Divisi lain ditambah ketika flow-nya sudah jelas.
        Division::updateOrCreate(['code' => 'MD'], ['name' => 'Marcomm']);
    }
}
