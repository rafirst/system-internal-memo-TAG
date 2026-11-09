<?php

namespace Tests\Feature;

use App\Enums\ApprovalStatus;
use App\Enums\MemoStatus;
use App\Models\Branch;
use App\Models\Division;
use App\Models\Memo;
use App\Models\MemoApproval;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class DashboardDatabaseCompatibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_loads_with_current_schema_approvals(): void
    {
        $this->seed();

        $administrator = User::where('username', 'admin')->firstOrFail();
        $approver = User::where('username', 'yogi')->firstOrFail();
        $memo = Memo::create([
            'memo_number' => 'TEST/2026/0001',
            'submitter_id' => $administrator->id,
            'division_id' => Division::where('code', 'MD')->value('id'),
            'branch_id' => Branch::where('name', 'Palembang')->value('id'),
            'subject' => 'Uji kompatibilitas database',
            'opening_paragraph' => 'Memo untuk pengujian dashboard.',
            'memo_date' => now()->toDateString(),
            'status' => MemoStatus::Pending,
            'current_step' => 1,
            'round' => 1,
            'submitted_at' => now(),
        ]);

        MemoApproval::create([
            'memo_id' => $memo->id,
            'round' => 1,
            'step_order' => 1,
            'approver_role_id' => $administrator->role_id,
            'status' => ApprovalStatus::Pending,
        ]);

        $this->actingAs($administrator)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Administrator')
            ->assertSee('Memo Terbaru')
            ->assertSee('Logo-TAG-white.png')
            ->assertSee('showroom-tag.png');

        $this->get(route('memos.index'))
            ->assertOk()
            ->assertSee('Daftar Memo Internal')
            ->assertSee('Semua Divisi')
            ->assertSee('Tanggal mulai')
            ->assertSee('Buat Memo')
            ->assertSee('Uji kompatibilitas database')
            ->assertSee('Pending');
    }

    public function test_memo_index_filters_by_date_range_and_searches_submitter_name(): void
    {
        $this->seed();

        $administrator = User::where('username', 'admin')->firstOrFail();
        $divisionId = Division::where('code', 'MD')->value('id');
        $branchId = Branch::where('name', 'Palembang')->value('id');

        foreach ([
            ['memo_number' => 'TEST/2026/0001', 'subject' => 'Memo sebelum rentang', 'memo_date' => '2026-10-09'],
            ['memo_number' => 'TEST/2026/0002', 'subject' => 'Memo dalam rentang', 'memo_date' => '2026-10-15'],
        ] as $memoData) {
            Memo::create([
                ...$memoData,
                'submitter_id' => $administrator->id,
                'division_id' => $divisionId,
                'branch_id' => $branchId,
                'opening_paragraph' => 'Memo untuk pengujian filter daftar.',
                'status' => MemoStatus::Pending,
                'current_step' => 1,
                'round' => 1,
                'submitted_at' => now(),
            ]);
        }

        $this->actingAs($administrator)
            ->get(route('memos.index', [
                'q' => 'Administrator',
                'date_from' => '2026-10-10',
                'date_to' => '2026-10-20',
            ]))
            ->assertOk()
            ->assertSee('Memo dalam rentang')
            ->assertDontSee('Memo sebelum rentang');

        $this->get(route('memos.index', ['date_to' => '2026-10-10']))
            ->assertOk()
            ->assertSee('Memo sebelum rentang')
            ->assertDontSee('Memo dalam rentang');
    }

    public function test_status_badge_renders_memo_and_approval_statuses(): void
    {
        $memoBadge = Blade::render('<x-status-badge :status="$status" />', [
            'status' => MemoStatus::Approved,
        ]);
        $approvalBadge = Blade::render('<x-status-badge :status="$status" />', [
            'status' => ApprovalStatus::Pending,
        ]);

        $this->assertStringContainsString('Approved', $memoBadge);
        $this->assertStringContainsString('Menunggu approval', $approvalBadge);
    }

    public function test_administrator_can_open_memo_form_and_submit_with_a_selected_approver(): void
    {
        $this->seed();

        $administrator = User::where('username', 'admin')->firstOrFail();
        $approver = User::where('username', 'yogi')->firstOrFail();
        $division = Division::where('code', 'MD')->firstOrFail();
        $branch = Branch::where('name', 'Palembang')->firstOrFail();

        $this->actingAs($administrator)
            ->get(route('memos.index'))
            ->assertOk()
            ->assertSee('Buat Memo');

        $this->get(route('memos.create'))
            ->assertOk()
            ->assertSee('name="division_id"', false)
            ->assertSee('name="branch_id"', false)
            ->assertSeeText('Pemberi Persetujuan')
            ->assertSee('Ketik nama, jabatan, atau role approver...')
            ->assertSeeText('approver');

        $response = $this->post(route('memos.store'), [
            'division_id' => $division->id,
            'branch_id' => $branch->id,
            'subject' => 'Memo dari Administrator',
            'opening_paragraph' => 'Pengujian alur persetujuan Administrator.',
            'memo_date' => now()->toDateString(),
            'items' => [
                ['name' => 'Perlengkapan', 'qty' => 1, 'unit_price' => 100000],
            ],
            'approver_ids' => [$approver->id],
            'action' => 'submit',
        ]);

        $memo = Memo::where('subject', 'Memo dari Administrator')->firstOrFail();

        $response->assertRedirect(route('memos.show', $memo));
        $this->assertSame(MemoStatus::Pending, $memo->fresh()->status);
        $this->assertGreaterThan(0, $memo->approvals()->count());

        $this->get(route('memos.show', $memo))
            ->assertOk()
            ->assertSee('No. '.$memo->memo_number)
            ->assertSee($memo->subject)
            ->assertSee('Dengan Hormat,')
            ->assertSee('Rincian Pengajuan')
            ->assertSee('Riwayat Persetujuan')
            ->assertSee('Supervisor')
            ->assertSee('Unduh PDF');
    }

    public function test_submission_rejects_the_submitter_staff_and_inactive_approvers(): void
    {
        $this->seed();

        $submitter = User::where('username', 'staff.marcomm')->firstOrFail();
        $staffRoleId = $submitter->role_id;
        $otherStaff = User::create([
            'name' => 'Staff Lain',
            'username' => 'staff.lain',
            'email' => 'staff.lain@example.test',
            'password' => 'password',
            'role_id' => $staffRoleId,
            'is_active' => true,
        ]);
        $inactiveApprover = User::create([
            'name' => 'Approver Nonaktif',
            'username' => 'approver.nonaktif',
            'email' => 'approver.nonaktif@example.test',
            'password' => 'password',
            'role_id' => User::where('username', 'devi')->value('role_id'),
            'is_active' => false,
        ]);
        $payload = [
            'subject' => 'Memo dengan approver tidak valid',
            'opening_paragraph' => 'Pengujian validasi pemberi persetujuan.',
            'memo_date' => now()->toDateString(),
            'items' => [
                ['name' => 'Perlengkapan', 'qty' => 1, 'unit_price' => 100000],
            ],
            'action' => 'submit',
        ];

        foreach ([$submitter->id, $otherStaff->id, $inactiveApprover->id] as $approverId) {
            $this->actingAs($submitter)
                ->post(route('memos.store'), [...$payload, 'approver_ids' => [$approverId]])
                ->assertSessionHasErrors('approver_ids.0');
        }

        $this->assertDatabaseMissing('memos', ['subject' => 'Memo dengan approver tidak valid']);
    }
}
