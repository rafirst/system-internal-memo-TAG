<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotificationIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_notification_page_filters_categories_and_shows_summary_counts(): void
    {
        $this->seed();

        $staff = User::where('username', 'staff.marcomm')->firstOrFail();
        $notifications = [
            ['title' => 'Memo menunggu persetujuan Anda', 'message' => 'Menunggu approval.'],
            ['title' => 'Persetujuan memo diperbarui', 'message' => 'Memo diteruskan.'],
            ['title' => 'Memo ditolak', 'message' => 'Perlu revisi.'],
            ['title' => 'Dana telah dicairkan', 'message' => 'Dana sudah dicairkan.'],
        ];

        foreach ($notifications as $index => $notification) {
            DB::table('notifications')->insert([
                'id' => (string) Str::uuid(),
                'type' => 'App\\Notifications\\MemoNotification',
                'notifiable_type' => User::class,
                'notifiable_id' => $staff->id,
                'data' => json_encode([
                    ...$notification,
                    'memo_id' => $index + 1,
                    'memo_number' => 'TEST/2026/'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT),
                    'url' => route('memos.index'),
                ], JSON_THROW_ON_ERROR),
                'read_at' => null,
                'created_at' => now()->subMinutes($index),
                'updated_at' => now()->subMinutes($index),
            ]);
        }

        $this->actingAs($staff)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('aria-label="Profil Staff Marcomm"', false)
            ->assertSee('x-show="profileOpen"', false)
            ->assertSee('action="'.route('logout').'"', false)
            ->assertSee('Pemberitahuan Terbaru')
            ->assertSee('Ringkasan Notifikasi')
            ->assertSee('Memo menunggu persetujuan Anda')
            ->assertSee('Memo ditolak')
            ->assertSee('Dana telah dicairkan');

        $this->get(route('notifications.index', ['category' => 'approval']))
            ->assertOk()
            ->assertSee('Memo menunggu persetujuan Anda')
            ->assertSee('Persetujuan memo diperbarui')
            ->assertDontSee('Memo ditolak')
            ->assertDontSee('Dana telah dicairkan');

        $this->get(route('notifications.index', ['category' => 'denied']))
            ->assertOk()
            ->assertSee('Memo ditolak')
            ->assertDontSee('Memo menunggu persetujuan Anda');

        $this->get(route('notifications.index', ['category' => 'updated']))
            ->assertOk()
            ->assertSee('Dana telah dicairkan')
            ->assertDontSee('Persetujuan memo diperbarui')
            ->assertDontSee('Memo ditolak')
            ->assertDontSee('Memo menunggu persetujuan Anda');
    }
}
