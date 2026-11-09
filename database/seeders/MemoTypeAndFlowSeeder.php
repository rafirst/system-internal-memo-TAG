<?php

namespace Database\Seeders;

use App\Models\ApprovalFlow;
use App\Models\ApprovalStep;
use App\Models\MemoType;
use App\Models\Role;
use Illuminate\Database\Seeder;

class MemoTypeAndFlowSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Pengadaan ATK -> alur pendek: Supervisor -> Kepala Divisi -> Admin Finance
        $atk = MemoType::updateOrCreate(
            ['code' => 'ATK'],
            ['name' => 'Pengadaan ATK', 'description' => 'Permintaan alat tulis kantor']
        );
        $this->makeFlow($atk, 'Flow ATK Standar', [
            Role::SUPERVISOR,
            Role::KEPALA_DIVISI,
            Role::ADMIN_FINANCE,
        ]);

        // 2. Pembelian Aset (laptop, meja, kursi, dll) -> alur panjang sampai Direksi
        $aset = MemoType::updateOrCreate(
            ['code' => 'ASET'],
            ['name' => 'Pembelian Aset', 'description' => 'Pengadaan aset/tools seperti laptop, meja, kursi']
        );
        $this->makeFlow($aset, 'Flow Aset Standar', [
            Role::SUPERVISOR,
            Role::KEPALA_DIVISI,
            Role::KEUANGAN,
            Role::DIREKSI,
        ]);

        // 3. Event (mall/minimarket) -> alur ke Direksi tanpa lewat Supervisor
        $event = MemoType::updateOrCreate(
            ['code' => 'EVENT'],
            ['name' => 'Event Mall/Minimarket', 'description' => 'Kebutuhan event di mall atau minimarket']
        );
        $this->makeFlow($event, 'Flow Event Standar', [
            Role::KEPALA_DIVISI,
            Role::KEUANGAN,
            Role::DIREKSI,
        ]);
    }

    private function makeFlow(MemoType $memoType, string $flowName, array $roleSlugsInOrder): void
    {
        $flow = ApprovalFlow::updateOrCreate(
            ['memo_type_id' => $memoType->id, 'name' => $flowName],
            ['is_active' => true]
        );

        foreach ($roleSlugsInOrder as $index => $slug) {
            $role = Role::where('slug', $slug)->firstOrFail();

            ApprovalStep::updateOrCreate(
                ['approval_flow_id' => $flow->id, 'step_order' => $index + 1],
                ['role_id' => $role->id, 'label' => 'Persetujuan '.$role->name]
            );
        }
    }
}
