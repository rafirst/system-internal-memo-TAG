<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('approval_flows') || ! Schema::hasTable('approval_flow_steps')) {
            return;
        }

        $staffRoleId = DB::table('roles')->where('slug', 'staff')->value('id');
        $supervisorRoleId = DB::table('roles')->where('slug', 'spv')->value('id');
        $divisionId = DB::table('divisions')->where('code', 'MD')->value('id');

        if (! $staffRoleId || ! $supervisorRoleId || ! $divisionId) {
            return;
        }

        DB::transaction(function () use ($staffRoleId, $supervisorRoleId, $divisionId): void {
            $flowId = DB::table('approval_flows')
                ->where('submitter_role_id', $staffRoleId)
                ->where('division_id', $divisionId)
                ->where('is_active', true)
                ->value('id');

            if (! $flowId) {
                return;
            }

            $hasSupervisorStep = DB::table('approval_flow_steps')
                ->where('approval_flow_id', $flowId)
                ->where('approver_role_id', $supervisorRoleId)
                ->exists();

            if (! $hasSupervisorStep) {
                DB::table('approval_flow_steps')
                    ->where('approval_flow_id', $flowId)
                    ->update(['step_order' => DB::raw('step_order + 10')]);
                DB::table('approval_flow_steps')
                    ->where('approval_flow_id', $flowId)
                    ->update(['step_order' => DB::raw('step_order - 9')]);
                DB::table('approval_flow_steps')->insert([
                    'approval_flow_id' => $flowId,
                    'step_order' => 1,
                    'approver_role_id' => $supervisorRoleId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('approval_flows')
                ->where('id', $flowId)
                ->update([
                    'name' => 'Marcomm: Supervisor > Admin Keuangan > Admin Finance > Direksi',
                    'updated_at' => now(),
                ]);

            if (! Schema::hasTable('memo_approvals') || ! Schema::hasTable('memos')) {
                return;
            }

            $staffIds = DB::table('users')->where('role_id', $staffRoleId)->pluck('id');
            $memos = DB::table('memos')
                ->where('division_id', $divisionId)
                ->where('status', 'pending')
                ->whereIn('submitter_id', $staffIds)
                ->get(['id', 'round']);

            foreach ($memos as $memo) {
                $hasSupervisorApproval = DB::table('memo_approvals')
                    ->where('memo_id', $memo->id)
                    ->where('round', $memo->round)
                    ->where('approver_role_id', $supervisorRoleId)
                    ->exists();

                if ($hasSupervisorApproval) {
                    continue;
                }

                DB::table('memo_approvals')
                    ->where('memo_id', $memo->id)
                    ->where('round', $memo->round)
                    ->update(['step_order' => DB::raw('step_order + 10')]);
                DB::table('memo_approvals')
                    ->where('memo_id', $memo->id)
                    ->where('round', $memo->round)
                    ->update(['step_order' => DB::raw('step_order - 9')]);
                DB::table('memo_approvals')
                    ->where('memo_id', $memo->id)
                    ->where('round', $memo->round)
                    ->where('status', 'pending')
                    ->update(['status' => 'waiting']);
                DB::table('memo_approvals')->insert([
                    'memo_id' => $memo->id,
                    'round' => $memo->round,
                    'step_order' => 1,
                    'approver_role_id' => $supervisorRoleId,
                    'status' => 'pending',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                DB::table('memos')
                    ->where('id', $memo->id)
                    ->update(['current_step' => 1]);
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('approval_flows') || ! Schema::hasTable('approval_flow_steps')) {
            return;
        }

        $staffRoleId = DB::table('roles')->where('slug', 'staff')->value('id');
        $supervisorRoleId = DB::table('roles')->where('slug', 'spv')->value('id');
        $divisionId = DB::table('divisions')->where('code', 'MD')->value('id');

        if (! $staffRoleId || ! $supervisorRoleId || ! $divisionId) {
            return;
        }

        $flowId = DB::table('approval_flows')
            ->where('submitter_role_id', $staffRoleId)
            ->where('division_id', $divisionId)
            ->where('is_active', true)
            ->value('id');

        if (! $flowId) {
            return;
        }

        DB::transaction(function () use ($flowId, $supervisorRoleId): void {
            DB::table('approval_flow_steps')
                ->where('approval_flow_id', $flowId)
                ->where('approver_role_id', $supervisorRoleId)
                ->delete();
            DB::table('approval_flow_steps')
                ->where('approval_flow_id', $flowId)
                ->update(['step_order' => DB::raw('step_order + 10')]);
            DB::table('approval_flow_steps')
                ->where('approval_flow_id', $flowId)
                ->update(['step_order' => DB::raw('step_order - 11')]);
            DB::table('approval_flows')
                ->where('id', $flowId)
                ->update([
                    'name' => 'Marcomm: Admin Keuangan > Admin Finance > Direksi',
                    'updated_at' => now(),
                ]);
        });
    }
};
