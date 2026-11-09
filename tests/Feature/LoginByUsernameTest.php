<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginByUsernameTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_accepts_username_case_insensitively_but_not_email(): void
    {
        $this->seed();

        $this->post(route('login'), [
            'username' => 'STAFF.MARCOMM',
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $this->post(route('logout'))->assertRedirect(route('login'));

        $this->post(route('login'), [
            'username' => 'staff.marcomm@tag.test',
            'password' => 'password',
        ])->assertSessionHasErrors('username');
    }

    public function test_inactive_user_cannot_log_in(): void
    {
        $this->seed();
        $staffRole = Role::where('slug', 'staff')->firstOrFail();
        User::factory()->create([
            'name' => 'Pengguna Nonaktif',
            'username' => 'pengguna.nonaktif',
            'email' => 'pengguna.nonaktif@example.test',
            'password' => 'password',
            'role_id' => $staffRole->id,
            'is_active' => false,
        ]);

        $this->post(route('login'), [
            'username' => 'PENGGUNA.NONAKTIF',
            'password' => 'password',
        ])->assertSessionHasErrors('username');
    }
}
