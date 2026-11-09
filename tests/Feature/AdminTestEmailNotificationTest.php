<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\TestEmailNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AdminTestEmailNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_send_a_branded_test_email(): void
    {
        $this->seed();
        Notification::fake();
        $administrator = User::where('username', 'admin')->firstOrFail();
        $recipient = User::where('username', 'staff.marcomm')->firstOrFail();

        $this->actingAs($administrator)
            ->from(route('admin.users.index'))
            ->post(route('admin.users.test-email', $recipient))
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas(
                'success',
                "Email tes dikirim ke {$recipient->email}. Minta pengguna mengecek inbox (dan folder spam).",
            );

        Notification::assertSentTo($recipient, TestEmailNotification::class);
        $this->actingAs($administrator)
            ->get(route('admin.users.index'))
            ->assertSee('Kirim email tes');
    }

    public function test_non_administrator_cannot_send_a_test_email(): void
    {
        $this->seed();
        Notification::fake();
        $staff = User::where('username', 'staff.marcomm')->firstOrFail();
        $recipient = User::where('username', 'yogi')->firstOrFail();

        $this->actingAs($staff)
            ->post(route('admin.users.test-email', $recipient))
            ->assertForbidden();

        Notification::assertNothingSent();
    }
}
