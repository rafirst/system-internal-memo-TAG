<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Division;
use App\Models\Memo;
use App\Models\Role;
use App\Models\User;
use App\Notifications\MemoNotification;
use App\Services\ApprovalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class ApprovalEmailNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_approval_disbursement_and_completion_notify_the_required_recipients(): void
    {
        $this->seed();
        Notification::fake();

        $staff = User::where('username', 'staff.marcomm')->firstOrFail();
        $supervisor = User::where('username', 'yogi')->firstOrFail();
        $finance = User::where('username', 'devi')->firstOrFail();
        $adh = User::where('username', 'awanti')->firstOrFail();
        $director = User::where('username', 'richie')->firstOrFail();
        $supervisorRole = Role::where('slug', 'spv')->firstOrFail();
        $extraSupervisor = User::factory()->create([
            'name' => 'Supervisor Aktif Lain',
            'username' => 'supervisor.lain',
            'email' => 'supervisor.lain@example.test',
            'role_id' => $supervisorRole->id,
            'is_active' => true,
        ]);
        $inactiveSupervisor = User::factory()->create([
            'name' => 'Supervisor Nonaktif',
            'username' => 'supervisor.nonaktif',
            'email' => 'supervisor.nonaktif@example.test',
            'role_id' => $supervisorRole->id,
            'is_active' => false,
        ]);

        $memo = $this->createMemo($staff, $supervisor);
        $service = app(ApprovalService::class);
        $service->submit($memo, $staff);

        $this->assertNotificationSentTo($supervisor, 'Memo menunggu persetujuan Anda');
        Notification::assertNotSentTo($extraSupervisor, MemoNotification::class);
        Notification::assertNotSentTo($inactiveSupervisor, MemoNotification::class);
        Notification::assertNotSentTo($staff, MemoNotification::class, fn (MemoNotification $notification) => $notification->title === 'Memo menunggu persetujuan Anda');

        $service->approve($memo->fresh(), $supervisor);
        $this->assertNotificationSentTo($finance, 'Memo menunggu persetujuan Anda');

        $service->approve($memo->fresh(), $finance);
        $this->assertNotificationSentTo($adh, 'Memo menunggu persetujuan Anda');

        $service->approve($memo->fresh(), $adh);
        $this->assertNotificationSentTo($director, 'Memo menunggu persetujuan Anda');

        $service->approve($memo->fresh(), $director);
        $this->assertNotificationSentTo($staff, 'Memo disetujui seluruhnya');
        $this->assertNotificationSentTo($supervisor, 'Memo disetujui seluruhnya');
        $this->assertNotificationSentTo($finance, 'Memo siap dicairkan');

        $service->disburse($memo->fresh(), $finance);
        $this->assertNotificationSentTo($staff, 'Dana telah dicairkan');
        $this->assertNotificationSentTo($supervisor, 'Dana telah dicairkan');

        Storage::fake('local');
        $service->complete($memo->fresh(), $staff, [
            UploadedFile::fake()->createWithContent('bukti.pdf', '%PDF-1.4 bukti'),
        ]);
        $this->assertNotificationSentTo($finance, 'Bukti realisasi diunggah');
    }

    public function test_administrator_receives_email_only_when_assigned_to_approve_a_memo(): void
    {
        $this->seed();
        Notification::fake();

        $staff = User::where('username', 'staff.marcomm')->firstOrFail();
        $administrator = User::where('username', 'admin')->firstOrFail();
        $adminApprovalMemo = $this->createMemo($staff, $administrator, [$administrator->id]);

        app(ApprovalService::class)->submit($adminApprovalMemo, $staff);

        Notification::assertSentTo(
            $administrator,
            MemoNotification::class,
            fn (MemoNotification $notification): bool => $notification->title === 'Memo menunggu persetujuan Anda'
                && $notification->via($administrator) === ['database', 'mail'],
        );

        Notification::fake();

        $supervisor = User::where('username', 'yogi')->firstOrFail();
        $adminSubmittedMemo = $this->createMemo($administrator, $supervisor, [$supervisor->id]);
        $service = app(ApprovalService::class);
        $service->submit($adminSubmittedMemo, $administrator);
        $service->approve($adminSubmittedMemo->fresh(), $supervisor);

        Notification::assertSentTo(
            $administrator,
            MemoNotification::class,
            fn (MemoNotification $notification): bool => $notification->title === 'Memo disetujui seluruhnya'
                && $notification->via($administrator) === ['database'],
        );
    }

    public function test_denial_reason_is_kept_in_app_but_never_in_the_email(): void
    {
        $this->seed();
        Notification::fake();

        $staff = User::where('username', 'staff.marcomm')->firstOrFail();
        $supervisor = User::where('username', 'yogi')->firstOrFail();
        $memo = $this->createMemo($staff, $supervisor);
        $service = app(ApprovalService::class);
        $service->submit($memo, $staff);
        $service->deny($memo->fresh(), $supervisor, 'ALASAN-RAHASIA-UNTUK-REVISI');

        $this->assertNotificationSentTo($staff, 'Memo ditolak', function (MemoNotification $notification): bool {
            return str_contains($notification->message, 'ALASAN-RAHASIA-UNTUK-REVISI')
                && ! str_contains((string) $notification->mailMessage, 'ALASAN-RAHASIA-UNTUK-REVISI');
        });
        Notification::assertNotSentTo(
            User::where('username', 'devi')->firstOrFail(),
            MemoNotification::class,
            fn (MemoNotification $notification) => $notification->title === 'Memo menunggu persetujuan Anda',
        );

        Notification::assertSentTo($staff, MemoNotification::class, function (MemoNotification $notification) use ($staff): bool {
            if ($notification->title !== 'Memo ditolak') {
                return false;
            }

            $mail = $notification->toMail($staff);
            $text = view('emails.memo_text', $mail->viewData)->render();

            return ! str_contains((string) $mail->render(), 'ALASAN-RAHASIA-UNTUK-REVISI')
                && ! str_contains($text, 'ALASAN-RAHASIA-UNTUK-REVISI');
        });
    }

    public function test_notification_failure_does_not_roll_back_memo_submission(): void
    {
        $this->seed();
        $staff = User::where('username', 'staff.marcomm')->firstOrFail();
        $supervisor = User::where('username', 'yogi')->firstOrFail();
        $memo = $this->createMemo($staff, $supervisor);

        Notification::shouldReceive('send')
            ->once()
            ->andThrow(new RuntimeException('SMTP unavailable'));

        app(ApprovalService::class)->submit($memo, $staff);

        $this->assertDatabaseHas('memos', [
            'id' => $memo->id,
            'status' => 'pending',
        ]);
    }

    public function test_submitter_selected_users_control_the_approval_order_and_notification_recipients(): void
    {
        $this->seed();
        Notification::fake();

        $staff = User::where('username', 'staff.marcomm')->firstOrFail();
        $supervisor = User::where('username', 'yogi')->firstOrFail();
        $finance = User::where('username', 'devi')->firstOrFail();
        $secondFinance = User::factory()->create([
            'name' => 'Finance Kedua',
            'username' => 'finance.kedua',
            'email' => 'finance.kedua@example.test',
            'role_id' => $finance->role_id,
            'is_active' => true,
        ]);
        $unselectedFinance = User::factory()->create([
            'name' => 'Finance Lain',
            'username' => 'finance.lain',
            'email' => 'finance.lain@example.test',
            'role_id' => $finance->role_id,
            'is_active' => true,
        ]);
        $unselectedSupervisor = User::factory()->create([
            'name' => 'Supervisor Lain',
            'username' => 'supervisor.lain.picked.test',
            'email' => 'supervisor.lain.picked.test@example.test',
            'role_id' => $supervisor->role_id,
            'division_id' => $supervisor->division_id,
            'is_active' => true,
        ]);
        $memo = $this->createMemo($staff, $supervisor, [$finance->id, $secondFinance->id, $supervisor->id]);
        $service = app(ApprovalService::class);

        $service->submit($memo, $staff);

        $this->assertSame(
            [$finance->id, $secondFinance->id, $supervisor->id],
            $memo->fresh()->currentApprovals()->pluck('assigned_approver_id')->all(),
        );
        $this->assertNotificationSentTo($finance, 'Memo menunggu persetujuan Anda');
        Notification::assertNotSentTo($supervisor, MemoNotification::class, fn (MemoNotification $notification) => $notification->title === 'Memo menunggu persetujuan Anda');
        Notification::assertNotSentTo($secondFinance, MemoNotification::class);
        Notification::assertNotSentTo($unselectedFinance, MemoNotification::class);

        $service->approve($memo->fresh(), $finance);
        $this->assertNotificationSentTo($secondFinance, 'Memo menunggu persetujuan Anda');
        Notification::assertNotSentTo($unselectedFinance, MemoNotification::class);

        $this->actingAs($unselectedFinance)
            ->post(route('memos.approve', $memo))
            ->assertForbidden();

        $service->approve($memo->fresh(), $secondFinance);
        $this->assertNotificationSentTo($supervisor, 'Memo menunggu persetujuan Anda');

        $this->actingAs($unselectedSupervisor)
            ->post(route('memos.approve', $memo))
            ->assertForbidden();

        $this->actingAs($supervisor)
            ->post(route('memos.approve', $memo))
            ->assertRedirect(route('memos.show', $memo));

        $this->assertSame('approved', $memo->fresh()->status->value);
    }

    private function createMemo(User $submitter, User $supervisor, ?array $approverIds = null): Memo
    {
        $division = Division::where('code', 'MD')->firstOrFail();
        $branch = Branch::where('name', 'Palembang')->firstOrFail();
        $memo = Memo::create([
            'submitter_id' => $submitter->id,
            'on_behalf_of_id' => $supervisor->id,
            'division_id' => $division->id,
            'branch_id' => $branch->id,
            'subject' => 'Memo pengujian notifikasi',
            'opening_paragraph' => 'Memo untuk pengujian alur.',
            'total_amount' => 100000,
            'memo_date' => now()->toDateString(),
            'status' => 'draft',
            'approver_ids' => $approverIds ?? [
                $supervisor->id,
                User::where('username', 'devi')->value('id'),
                User::where('username', 'awanti')->value('id'),
                User::where('username', 'richie')->value('id'),
            ],
        ]);
        $memo->items()->create([
            'name' => 'Item pengujian',
            'qty' => 1,
            'unit_price' => 100000,
            'subtotal' => 100000,
        ]);

        return $memo;
    }

    private function assertNotificationSentTo(
        User $user,
        string $title,
        ?callable $condition = null,
    ): void {
        Notification::assertSentTo(
            $user,
            MemoNotification::class,
            fn (MemoNotification $notification): bool => $notification->title === $title
                && ($condition === null || $condition($notification)),
        );
    }
}
