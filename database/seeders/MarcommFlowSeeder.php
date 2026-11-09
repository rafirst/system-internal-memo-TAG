<?php

namespace Database\Seeders;

use App\Models\ApprovalFlow;
use App\Models\Division;
use App\Models\Role;
use Illuminate\Database\Seeder;

class MarcommFlowSeeder extends Seeder
{
    public function run(): void
    {
        $division = Division::where('code', 'MD')->firstOrFail();
        $staff = Role::where('slug', 'staff')->firstOrFail();

        $flow = ApprovalFlow::updateOrCreate(
            ['submitter_role_id' => $staff->id, 'division_id' => $division->id],
            ['name' => 'Marcomm: Supervisor > Admin Keuangan > Admin Finance > Direksi', 'is_active' => true]
        );

        $order = ['spv', 'admin_keuangan', 'admin_finance', 'direksi'];

        $flow->steps()->delete();
        foreach ($order as $i => $slug) {
            $flow->steps()->create([
                'step_order' => $i + 1,
                'approver_role_id' => Role::where('slug', $slug)->value('id'),
            ]);
        }
    }
}
