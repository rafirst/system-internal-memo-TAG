<?php

namespace Tests\Feature;

use App\Enums\ApprovalStatus;
use App\Models\Branch;
use App\Models\Division;
use App\Models\Memo;
use App\Models\Role;
use App\Models\User;
use App\Services\ApprovalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarcommSupervisorApprovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_yogi_must_approve_before_finance_and_pdf_marks_him_approved(): void
    {
        $this->seed();

        $staff = User::where('username', 'staff.marcomm')->firstOrFail();
        $yogi = User::where('username', 'yogi')->firstOrFail();
        $division = Division::where('code', 'MD')->firstOrFail();
        $branch = Branch::where('name', 'Palembang')->firstOrFail();

        $memo = Memo::create([
            'submitter_id' => $staff->id,
            'on_behalf_of_id' => $yogi->id,
            'division_id' => $division->id,
            'branch_id' => $branch->id,
            'subject' => 'Pengujian approval supervisor',
            'opening_paragraph' => 'Memo pengujian.',
            'total_amount' => 100000,
            'memo_date' => now()->toDateString(),
            'status' => 'draft',
            'approver_ids' => [
                $yogi->id,
                User::where('username', 'devi')->value('id'),
            ],
        ]);
        $memo->items()->create([
            'name' => 'Perlengkapan',
            'qty' => 1,
            'unit_price' => 100000,
            'subtotal' => 100000,
        ]);

        app(ApprovalService::class)->submit($memo, $staff);
        $memo->refresh();

        $yogiApproval = $memo->currentApprovals()->firstOrFail();
        $this->assertSame('spv', $yogiApproval->approverRole->slug);
        $this->assertSame(ApprovalStatus::Pending, $yogiApproval->status);
        $this->assertSame(1, $memo->current_step);

        $pendingMemo = $memo->fresh()->load([
            'approvals.actor',
            'approvals.approverRole',
            'items',
            'division',
            'branch',
            'submitter',
            'onBehalfOf',
        ]);
        $pendingHtml = view('pdf.memo', [
            'memo' => $pendingMemo,
            'direksi' => null,
        ])->render();
        $this->assertStringContainsString('class="stamp waiting"', $pendingHtml);
        $this->assertStringContainsString('Menunggu approval', $pendingHtml);

        $otherSupervisor = User::factory()->create([
            'name' => 'Supervisor Lain',
            'username' => 'other.supervisor',
            'email' => 'other.supervisor@example.test',
            'role_id' => Role::where('slug', 'spv')->value('id'),
            'division_id' => $division->id,
            'branch_id' => $branch->id,
            'jabatan' => 'Supervisor',
            'is_active' => true,
        ]);

        $this->actingAs($otherSupervisor)
            ->post(route('memos.approve', $memo))
            ->assertForbidden();

        $this->actingAs($yogi)
            ->post(route('memos.approve', $memo))
            ->assertRedirect(route('memos.show', $memo));

        $yogiApproval->refresh();
        $this->assertSame(ApprovalStatus::Approved, $yogiApproval->status);
        $this->assertSame($yogi->id, $yogiApproval->acted_by);
        $this->assertSame(2, $memo->fresh()->current_step);
        $this->assertTrue(
            $staff->notifications()->where('data->title', 'Persetujuan memo diperbarui')->exists()
        );
        $this->assertTrue(
            User::where('username', 'devi')->firstOrFail()->notifications()
                ->where('data->title', 'Memo menunggu persetujuan Anda')
                ->exists()
        );

        $memo = $memo->fresh()->load([
            'approvals.actor',
            'approvals.approverRole',
            'items',
            'division',
            'branch',
            'submitter',
            'onBehalfOf',
        ]);
        $html = view('pdf.memo', [
            'memo' => $memo,
            'direksi' => User::whereHas('role', fn ($query) => $query->where('slug', 'direksi'))->first(),
        ])->render();

        $this->assertStringContainsString('Disetujui', $html);
        $this->assertStringContainsString('Yogi Pratama', $html);
        $this->assertStringContainsString('class="stamp approved"', $html);
    }
}
