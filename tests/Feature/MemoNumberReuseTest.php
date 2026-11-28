<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Division;
use App\Models\Memo;
use App\Models\MemoSequence;
use App\Models\User;
use App\Services\ApprovalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class MemoNumberReuseTest extends TestCase
{
    use RefreshDatabase;

    public function test_deleting_the_latest_memo_releases_its_number_for_the_next_submission(): void
    {
        $this->seed();
        Notification::fake();

        $submitter = User::where('username', 'staff.marcomm')->firstOrFail();
        $approver = User::where('username', 'yogi')->firstOrFail();
        $service = app(ApprovalService::class);
        $memos = collect();

        for ($number = 1; $number <= 8; $number++) {
            $memo = $this->createMemo($submitter, $approver, "Memo {$number}");
            $memos->push($service->submit($memo, $submitter));
        }

        $deletedMemo = $memos->last();
        $deletedMemoId = $deletedMemo->id;

        $this->actingAs($submitter)
            ->delete(route('memos.destroy', $deletedMemo))
            ->assertRedirect(route('memos.index'));

        $this->assertDatabaseMissing('memos', ['id' => $deletedMemoId]);
        $this->assertDatabaseHas('memo_sequences', [
            'division_id' => $deletedMemo->division_id,
            'year' => (int) $deletedMemo->memo_date->format('Y'),
            'last_number' => 7,
        ]);

        $replacement = $this->createMemo($submitter, $approver, 'Memo pengganti');
        $service->submit($replacement, $submitter);

        $replacement = $replacement->fresh();
        $this->assertSame('008/MD-TAG/XI/2026', $replacement->memo_number);
        $this->assertGreaterThan($deletedMemoId, $replacement->id);
    }

    public function test_deleting_a_non_latest_memo_does_not_reuse_its_number(): void
    {
        $this->seed();
        Notification::fake();

        $submitter = User::where('username', 'staff.marcomm')->firstOrFail();
        $approver = User::where('username', 'yogi')->firstOrFail();
        $service = app(ApprovalService::class);
        $firstMemo = $service->submit($this->createMemo($submitter, $approver, 'Memo pertama'), $submitter);
        $service->submit($this->createMemo($submitter, $approver, 'Memo kedua'), $submitter);

        $this->actingAs($submitter)
            ->delete(route('memos.destroy', $firstMemo))
            ->assertRedirect(route('memos.index'));

        $nextMemo = $this->createMemo($submitter, $approver, 'Memo ketiga');
        $service->submit($nextMemo, $submitter);

        $this->assertSame('003/MD-TAG/XI/2026', $nextMemo->fresh()->memo_number);
        $this->assertSame(3, MemoSequence::where('division_id', $nextMemo->division_id)->value('last_number'));
    }

    private function createMemo(User $submitter, User $approver, string $subject): Memo
    {
        $division = Division::where('code', 'MD')->firstOrFail();
        $branch = Branch::where('name', 'Palembang')->firstOrFail();
        $memo = Memo::create([
            'submitter_id' => $submitter->id,
            'on_behalf_of_id' => $approver->id,
            'division_id' => $division->id,
            'branch_id' => $branch->id,
            'subject' => $subject,
            'opening_paragraph' => 'Memo untuk pengujian nomor urut.',
            'total_amount' => 100000,
            'memo_date' => '2026-11-26',
            'status' => 'draft',
            'approver_ids' => [$approver->id],
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
