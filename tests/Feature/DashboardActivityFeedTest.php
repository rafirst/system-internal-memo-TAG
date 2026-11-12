<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Division;
use App\Models\Memo;
use App\Models\User;
use App\Services\ApprovalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardActivityFeedTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_sees_activity_from_memos_they_are_not_involved_in(): void
    {
        $this->seed();

        $staff = User::where('username', 'staff.marcomm')->firstOrFail();
        $yogi = User::where('username', 'yogi')->firstOrFail();
        $this->submitMemo($staff, 'Pengajuan divisi Marcomm', [$yogi->id], $yogi);

        $this->actingAs(User::where('username', 'admin')->firstOrFail())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Aktivitas Seluruh Sistem')
            // Pelakunya disebut namanya, bukan sekadar "memo Anda".
            ->assertSee('Staff Marcomm mengajukan memo')
            ->assertSee('Pengajuan divisi Marcomm');
    }

    public function test_administrator_sees_drafts_while_staff_does_not(): void
    {
        $this->seed();

        $staff = User::where('username', 'staff.marcomm')->firstOrFail();
        $this->draftMemo($staff, 'Draft belum diajukan');

        $this->actingAs(User::where('username', 'admin')->firstOrFail())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Memo dibuat')
            ->assertSee('Draft belum diajukan');

        // Draft belum menghasilkan kejadian apa pun untuk role staff.
        $this->actingAs($staff)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Aktivitas Memo')
            ->assertDontSee('Memo dibuat');
    }

    public function test_staff_does_not_see_another_submitters_activity_but_administrator_does(): void
    {
        $this->seed();

        $staff = User::where('username', 'staff.marcomm')->firstOrFail();
        $yogi = User::where('username', 'yogi')->firstOrFail();
        $otherStaff = User::create([
            'name' => 'Staff Lain',
            'username' => 'staff.lain',
            'email' => 'staff.lain@example.test',
            'password' => 'password',
            'role_id' => $staff->role_id,
            'division_id' => Division::where('code', 'MD')->value('id'),
            'branch_id' => Branch::where('name', 'Palembang')->value('id'),
            'jabatan' => 'Staff Marcomm',
            'is_active' => true,
        ]);

        $this->submitMemo($otherStaff, 'Pengajuan staff lain', [$yogi->id], $yogi);

        $this->actingAs($staff)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Pengajuan staff lain');

        $this->actingAs(User::where('username', 'admin')->firstOrFail())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Pengajuan staff lain');
    }

    public function test_activity_feed_heading_follows_the_users_role(): void
    {
        $this->seed();

        foreach ([
            'admin' => 'Aktivitas Seluruh Sistem',
            'yogi' => 'Aktivitas Divisi',
            'devi' => 'Aktivitas Keuangan',
            'richie' => 'Aktivitas Persetujuan',
            'staff.marcomm' => 'Aktivitas Memo',
        ] as $username => $heading) {
            $this->actingAs(User::where('username', $username)->firstOrFail())
                ->get(route('dashboard'))
                ->assertOk()
                ->assertSee($heading);
        }
    }

    public function test_feed_reports_a_rejection_with_its_reason(): void
    {
        $this->seed();

        $staff = User::where('username', 'staff.marcomm')->firstOrFail();
        $yogi = User::where('username', 'yogi')->firstOrFail();
        $memo = $this->submitMemo($staff, 'Pengajuan untuk ditolak', [$yogi->id], $yogi);

        $this->actingAs($yogi)
            ->post(route('memos.deny', $memo), ['comment' => 'Anggaran belum tersedia'])
            ->assertRedirect(route('memos.show', $memo));

        $this->actingAs(User::where('username', 'admin')->firstOrFail())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Memo ditolak')
            ->assertSee('Yogi Pratama menolak memo')
            ->assertSee('Anggaran belum tersedia');
    }

    /** Memo kosong berstatus draft milik pengaju. */
    private function draftMemo(User $submitter, string $subject): Memo
    {
        $memo = Memo::create([
            'submitter_id' => $submitter->id,
            'division_id' => $submitter->division_id ?? Division::where('code', 'MD')->value('id'),
            'branch_id' => $submitter->branch_id ?? Branch::where('name', 'Palembang')->value('id'),
            'subject' => $subject,
            'opening_paragraph' => 'Memo pengujian umpan aktivitas.',
            'memo_date' => now()->toDateString(),
            'status' => 'draft',
            'total_amount' => 100000,
            'approver_ids' => [],
        ]);

        $memo->items()->create([
            'name' => 'Perlengkapan',
            'qty' => 1,
            'unit_price' => 100000,
            'subtotal' => 100000,
        ]);

        return $memo;
    }

    /**
     * Ajukan memo dengan daftar approver tertentu.
     *
     * @param  array<int, int>  $approverIds
     */
    private function submitMemo(User $submitter, string $subject, array $approverIds, ?User $onBehalfOf = null): Memo
    {
        $memo = $this->draftMemo($submitter, $subject);
        $memo->update([
            'approver_ids' => $approverIds,
            'on_behalf_of_id' => $onBehalfOf?->id,
        ]);

        return app(ApprovalService::class)->submit($memo, $submitter);
    }
}
