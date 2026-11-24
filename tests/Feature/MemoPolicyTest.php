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
use App\Notifications\MemoNotification;
use App\Policies\MemoPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class MemoPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_supervisor_can_create_and_view_their_own_draft_memo(): void
    {
        $supervisorRole = Role::create(['name' => 'Supervisor', 'slug' => 'spv']);
        $division = Division::create(['name' => 'Digital Marketing', 'code' => 'MD']);
        $branch = Branch::create(['name' => 'Head Office', 'city' => 'Palembang']);
        $supervisor = User::create([
            'name' => 'Yogi Pratama',
            'username' => 'yogi',
            'email' => 'yogi@example.test',
            'password' => 'password',
            'role_id' => $supervisorRole->id,
            'division_id' => $division->id,
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);
        $otherSupervisor = User::create([
            'name' => 'Supervisor Lain',
            'username' => 'supervisor.lain',
            'email' => 'supervisor.lain@example.test',
            'password' => 'password',
            'role_id' => $supervisorRole->id,
            'division_id' => $division->id,
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);

        $this->actingAs($supervisor)
            ->get(route('memos.create'))
            ->assertOk();

        $this->post(route('memos.store'), [
            'memo_kind' => 'pengajuan',
            'subject' => 'Perlengkapan divisi',
            'opening_paragraph' => 'Dengan Hormat,',
            'memo_date' => '2026-11-16',
            'items' => [
                ['name' => 'Perlengkapan', 'qty' => 1, 'unit_price' => 100000],
            ],
            'action' => 'draft',
        ])->assertRedirect();

        $memo = Memo::where('subject', 'Perlengkapan divisi')->firstOrFail();
        $this->assertSame($supervisor->id, $memo->submitter_id);
        $this->assertSame($division->id, $memo->division_id);
        $this->assertSame($branch->id, $memo->branch_id);
        $this->assertNull($memo->on_behalf_of_id);

        $this->get(route('memos.show', $memo))->assertOk();
        $this->get(route('memos.index'))->assertSeeText('Perlengkapan divisi');
        $this->actingAs($otherSupervisor)
            ->get(route('memos.show', $memo))
            ->assertForbidden();

        $supervisorWithoutBranch = User::create([
            'name' => 'Supervisor Tanpa Cabang',
            'username' => 'supervisor.tanpa.cabang',
            'email' => 'supervisor.cabang@example.test',
            'password' => 'password',
            'role_id' => $supervisorRole->id,
            'division_id' => $division->id,
            'is_active' => true,
        ]);

        $this->assertFalse((new MemoPolicy)->create($supervisorWithoutBranch));
    }

    public function test_submitter_can_delete_pending_memo_before_first_approval(): void
    {
        Storage::fake('local');

        $staffRole = Role::create(['name' => 'Staff', 'slug' => 'staff']);
        $supervisorRole = Role::create(['name' => 'Supervisor', 'slug' => 'spv']);
        $division = Division::create(['name' => 'Marketing', 'code' => 'MKT']);
        $branch = Branch::create(['name' => 'Palembang', 'city' => 'Palembang']);
        $submitter = User::create([
            'name' => 'Staff Marcomm',
            'username' => 'staff.marcomm',
            'email' => 'staff.marcomm@example.test',
            'password' => 'password',
            'role_id' => $staffRole->id,
            'division_id' => $division->id,
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);
        $approver = User::create([
            'name' => 'Supervisor Marcomm',
            'username' => 'supervisor.marcomm',
            'email' => 'supervisor.marcomm@example.test',
            'password' => 'password',
            'role_id' => $supervisorRole->id,
            'division_id' => $division->id,
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);
        $memo = Memo::create([
            'submitter_id' => $submitter->id,
            'division_id' => $division->id,
            'branch_id' => $branch->id,
            'subject' => 'Memo salah dibuat',
            'opening_paragraph' => 'Dengan Hormat,',
            'memo_date' => '2026-11-19',
            'status' => MemoStatus::Pending,
            'current_step' => 1,
            'round' => 1,
        ]);
        MemoApproval::create([
            'memo_id' => $memo->id,
            'round' => 1,
            'step_order' => 1,
            'approver_role_id' => $supervisorRole->id,
            'assigned_approver_id' => $approver->id,
            'status' => ApprovalStatus::Pending,
        ]);
        $notification = $approver->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => MemoNotification::class,
            'data' => ['memo_id' => $memo->id, 'title' => 'Memo menunggu persetujuan'],
        ]);
        $attachmentPath = "memos/{$memo->id}/supporting/receipt.pdf";
        Storage::disk('local')->put($attachmentPath, 'memo attachment');

        $this->actingAs($submitter)
            ->get(route('memos.show', $memo))
            ->assertOk()
            ->assertSeeText('Hapus Memo');

        $this->delete(route('memos.destroy', $memo))
            ->assertRedirect(route('memos.index'))
            ->assertSessionHas('success', 'Memo berhasil dihapus.');

        $this->assertDatabaseMissing('memos', ['id' => $memo->id]);
        $this->assertDatabaseMissing('memo_approvals', ['memo_id' => $memo->id]);
        $this->assertDatabaseMissing('notifications', ['id' => $notification->id]);
        Storage::disk('local')->assertMissing($attachmentPath);
    }

    public function test_assigned_supervisor_can_view_and_approve_memo_from_another_division(): void
    {
        Notification::fake();

        $staffRole = Role::create(['name' => 'Staff', 'slug' => 'staff']);
        $supervisorRole = Role::create(['name' => 'Supervisor', 'slug' => 'spv']);
        $submitterDivision = Division::create(['name' => 'Finance', 'code' => 'FIN']);
        $supervisorDivision = Division::create(['name' => 'Digital Marketing', 'code' => 'MD']);
        $branch = Branch::create(['name' => 'Head Office', 'city' => 'Palembang']);
        $submitter = User::create([
            'name' => 'Staff Finance',
            'username' => 'staff.finance',
            'email' => 'staff.finance@example.test',
            'password' => 'password',
            'role_id' => $staffRole->id,
            'division_id' => $submitterDivision->id,
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);
        $supervisor = User::create([
            'name' => 'Yogi Pratama',
            'username' => 'yogi',
            'email' => 'yogi@example.test',
            'password' => 'password',
            'role_id' => $supervisorRole->id,
            'division_id' => $supervisorDivision->id,
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);
        $unassignedSupervisor = User::create([
            'name' => 'Supervisor Lain',
            'username' => 'supervisor.lain',
            'email' => 'supervisor.lain@example.test',
            'password' => 'password',
            'role_id' => $supervisorRole->id,
            'division_id' => $supervisorDivision->id,
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);
        $memo = Memo::create([
            'submitter_id' => $submitter->id,
            'division_id' => $submitterDivision->id,
            'branch_id' => $branch->id,
            'subject' => 'Pembelian meja',
            'opening_paragraph' => 'Dengan Hormat,',
            'memo_date' => '2026-11-14',
            'status' => MemoStatus::Pending,
            'current_step' => 1,
            'round' => 1,
        ]);
        MemoApproval::create([
            'memo_id' => $memo->id,
            'round' => 1,
            'step_order' => 1,
            'approver_role_id' => $supervisorRole->id,
            'assigned_approver_id' => $supervisor->id,
            'status' => ApprovalStatus::Pending,
        ]);

        $this->actingAs($unassignedSupervisor)
            ->get(route('memos.show', $memo))
            ->assertForbidden();
        $this->post(route('memos.approve', $memo))->assertForbidden();

        $this->actingAs($supervisor)
            ->get(route('memos.show', $memo))
            ->assertOk();
        $this->post(route('memos.approve', $memo))
            ->assertRedirect(route('memos.show', $memo));

        $this->assertSame(
            ApprovalStatus::Approved,
            $memo->currentApprovals()->firstOrFail()->status,
        );
    }

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
        $this->assertTrue($policy->delete($submitter, $memo));
        $this->actingAs($submitter)
            ->get(route('memos.show', $memo))
            ->assertOk()
            ->assertSeeText('Hapus Memo');
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
        $this->assertFalse($policy->delete($submitter, $memo->fresh()));
        $this->get(route('memos.edit', $memo))->assertForbidden();

        $memo->update(['status' => MemoStatus::Denied]);

        $this->assertTrue($policy->update($submitter, $memo->fresh()));
        $this->assertTrue($policy->submit($submitter, $memo->fresh()));
    }
}
