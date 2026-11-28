<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemoApproverPreviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_memo_composer_preview_lists_branch_approvers_before_head_office_approvers(): void
    {
        $this->seed();

        $administrator = User::where('username', 'admin')->firstOrFail();
        $branch = Branch::where('name', 'Palembang')->firstOrFail();
        $branchApprover = User::where('username', 'yogi')->firstOrFail();
        $headOfficeApprover = User::where('username', 'devi')->firstOrFail();

        $branchApprover->update([
            'office_type' => 'branch',
            'branch_id' => $branch->id,
        ]);
        $headOfficeApprover->update(['office_type' => 'head_office']);
        $administrator->update(['office_type' => 'head_office']);

        $response = $this->actingAs($administrator)
            ->get(route('memos.create'))
            ->assertOk()
            ->assertSee('selectedApproversByOfficeType', false)
            ->assertSee('officeType', false)
            ->assertSee('branchName', false)
            ->assertSee('requesterOfficeType === \'head_office\'', false)
            ->assertSee('Hormat saya,')
            ->assertSee('Mengetahui,')
            ->assertSee('Menyetujui,')
            ->assertSee('Approver Cabang')
            ->assertSee('Approval Head Office')
            ->assertSee('memo-composer-paper-approval-grid', false)
            ->assertSee('memo-composer-paper-approval-card', false);

        $previewPosition = strpos($response->getContent(), 'class="memo-composer-paper-approvals"');
        $this->assertNotFalse($previewPosition);

        $preview = substr($response->getContent(), $previewPosition);
        $branchGroupPosition = strpos($preview, 'Approver Cabang');
        $headOfficeGroupPosition = strpos($preview, 'Approval Head Office');

        $this->assertNotFalse($branchGroupPosition);
        $this->assertNotFalse($headOfficeGroupPosition);
        $this->assertLessThan($headOfficeGroupPosition, $branchGroupPosition);
    }

    public function test_branch_submitter_signature_is_rendered_in_the_branch_approver_row(): void
    {
        $this->seed();

        $submitter = User::where('username', 'staff.marcomm')->firstOrFail();
        $submitter->update(['office_type' => 'branch']);

        $this->actingAs($submitter)
            ->get(route('memos.create'))
            ->assertOk()
            ->assertSee('requesterOfficeType === \'branch\'', false)
            ->assertSee('Hormat saya,')
            ->assertSee('Approver Cabang');
    }
}
