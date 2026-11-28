<?php

namespace Tests\Feature;

use App\Enums\ApprovalStatus;
use App\Enums\MemoStatus;
use App\Models\Branch;
use App\Models\Division;
use App\Models\Memo;
use App\Models\MemoApproval;
use App\Models\User;
use App\Services\ApprovalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class MemoApprovalRoundTest extends TestCase
{
    use RefreshDatabase;

    public function test_resubmitting_a_denied_memo_starts_a_pending_round_and_preserves_history(): void
    {
        $this->seed();
        Notification::fake();

        $submitter = User::where('username', 'staff.marcomm')->firstOrFail();
        $supervisor = User::where('username', 'yogi')->firstOrFail();
        $financeApprover = User::where('username', 'devi')->firstOrFail();
        $memo = $this->createMemo($submitter, $supervisor, $financeApprover);
        $service = app(ApprovalService::class);

        $service->submit($memo, $submitter);
        $service->approve($memo->fresh(), $supervisor);
        $service->deny($memo->fresh(), $financeApprover, 'Mohon lengkapi rincian.');

        $service->submit($memo->fresh(), $submitter);

        $memo = $memo->fresh();
        $this->assertSame(MemoStatus::Pending, $memo->status);
        $this->assertSame(2, $memo->round);
        $this->assertSame(1, $memo->current_step);

        $roundTwoStatuses = $memo->currentApprovals()
            ->orderBy('step_order')
            ->get()
            ->map(fn (MemoApproval $approval) => $approval->status)
            ->all();

        $this->assertSame([ApprovalStatus::Pending, ApprovalStatus::Waiting], $roundTwoStatuses);

        $roundOneStatuses = $memo->approvals()
            ->where('round', 1)
            ->orderBy('step_order')
            ->get()
            ->map(fn (MemoApproval $approval) => $approval->status)
            ->all();

        $this->assertSame([ApprovalStatus::Approved, ApprovalStatus::Denied], $roundOneStatuses);

        $this->actingAs($submitter)
            ->get(route('memos.show', $memo))
            ->assertOk()
            ->assertSee('Pengajuan 2 (terkini)')
            ->assertSee('Pending');
    }

    private function createMemo(User $submitter, User $supervisor, User $financeApprover): Memo
    {
        $division = Division::where('code', 'MD')->firstOrFail();
        $branch = Branch::where('name', 'Palembang')->firstOrFail();
        $memo = Memo::create([
            'submitter_id' => $submitter->id,
            'on_behalf_of_id' => $supervisor->id,
            'division_id' => $division->id,
            'branch_id' => $branch->id,
            'subject' => 'Memo pengujian ronde approval',
            'opening_paragraph' => 'Memo untuk pengujian pengajuan ulang.',
            'total_amount' => 100000,
            'memo_date' => now()->toDateString(),
            'status' => MemoStatus::Draft,
            'approver_ids' => [$supervisor->id, $financeApprover->id],
        ]);
        $memo->items()->create([
            'name' => 'Item pengujian',
            'qty' => 1,
            'unit_price' => 100000,
            'subtotal' => 100000,
        ]);

        return $memo;
    }
}
