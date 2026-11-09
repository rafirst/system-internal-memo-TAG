<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UsernameBackfillMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_username_backfill_sanitizes_and_deduplicates_email_local_parts(): void
    {
        $firstUser = User::factory()->create([
            'username' => 'username.lama.1',
            'email' => 'Marcomm.User@example.test',
        ]);
        $secondUser = User::factory()->create([
            'username' => 'username.lama.2',
            'email' => 'marcomm.user@another.test',
        ]);

        $migration = require base_path('database/migrations/2026_10_30_014146_backfill_usernames_and_require_user_emails.php');
        $migration->up();

        $this->assertSame('marcomm.user', $firstUser->fresh()->username);
        $this->assertSame('marcomm.user.2', $secondUser->fresh()->username);
    }
}
