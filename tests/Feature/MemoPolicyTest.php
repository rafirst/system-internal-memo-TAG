<?php

namespace Tests\Feature;

use App\Enums\ApprovalStatus;
use App\Enums\MemoStatus;
use App\Models\Branch;
use App\Models\Division;
use App\Models\Memo;
use App\Models\MemoApproval;
use App\Models\Role;
use App\Models\User;
use App\Policies\MemoPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemoPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_submitter_can_edit_pending_memo_until_first_approval(): void
    {
        $role = Role::create(['name' => 'Staff', 'slug' => 'staff']);
        $division = Division::create(['name' => 'Marketing', 'code' => 'MKT']);
        $branch = Branch::create(['name' => 'Palembang', 'city' => 'Palembang']);
        $submitter = User::create([
            'name' => 'Staff Marcomm',
            'username' => 'staff.marcomm',
            'email' => 'staff.marcomm@example.test',
            'password' => 'password',
            'role_id' => $role->id,
            'division_id' => $division->id,
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);
        $memo = Memo::create([
            'submitter_id' => $submitter->id,
            'division_id' => $division->id,
            'branch_id' => $branch->id,
            'subject' => 'Peralatan kerja',
            'opening_paragraph' => 'Dengan Hormat,',
            'memo_date' => '2026-10-23',
            'status' => MemoStatus::Pending,
            'current_step' => 1,
            'round' => 1,
        ]);
        $policy = new MemoPolicy;

        $this->assertTrue($policy->update($submitter, $memo));
        $this->actingAs($submitter)
            ->get(route('memos.edit', $memo))
            ->assertOk()
            ->assertSeeText('Simpan Perubahan')
            ->assertDontSeeText('Simpan & Ajukan');

        $administratorRole = Role::create(['name' => 'Administrator', 'slug' => 'administrator']);
        $administrator = User::create([
            'name' => 'Administrator',
            'username' => 'administrator',
            'email' => 'administrator@example.test',
            'password' => 'password',
            'role_id' => $administratorRole->id,
            'is_active' => true,
        ]);
        $this->assertTrue($policy->update($administrator, $memo));
        $this->actingAs($administrator)
            ->get(route('memos.show', $memo))
            ->assertOk()
            ->assertSeeText('Edit Memo')
            ->assertDontSeeText('Ajukan Memo');
        $this->assertFalse($policy->submit($administrator, $memo));

        MemoApproval::create([
            'memo_id' => $memo->id,
            'round' => 1,
            'step_order' => 1,
            'approver_role_id' => $role->id,
            'status' => ApprovalStatus::Pending,
        ]);

        $this->assertTrue($policy->update($submitter, $memo));

        $memo->currentApprovals()->update(['status' => ApprovalStatus::Approved]);

        $this->assertFalse($policy->update($submitter, $memo->fresh()));
        $this->assertFalse($policy->update($administrator, $memo->fresh()));
        $this->get(route('memos.edit', $memo))->assertForbidden();

        $memo->update(['status' => MemoStatus::Denied]);

        $this->assertTrue($policy->update($submitter, $memo->fresh()));
        $this->assertTrue($policy->submit($submitter, $memo->fresh()));
    }
}
