<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Division;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_create_users_with_a_unique_username(): void
    {
        $this->seed();

        $administrator = User::where('username', 'admin')->firstOrFail();
        $this->actingAs($administrator);

        $this->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('Username')
            ->assertSee('Email (notifikasi)')
            ->assertSee('staff.marcomm@tag.test')
            ->assertSee('admin');

        $this->get(route('admin.users.create'))
            ->assertOk()
            ->assertSee('name="username"', false)
            ->assertSee('name="email"', false)
            ->assertSee('id="jabatan" name="jabatan"', false)
            ->assertSee('<option value="HRD"', false)
            ->assertSee('<option value="Kepala Kontrak"', false);

        $response = $this->post(route('admin.users.store'), [
            'name' => 'Pengguna Baru',
            'username' => 'pengguna.baru',
            'email' => 'pengguna.baru@example.test',
            'password' => 'password123',
            'role_id' => Role::where('slug', 'staff')->value('id'),
            'jabatan' => 'HRD',
            'is_active' => '1',
        ]);

        $user = User::where('username', 'pengguna.baru')->firstOrFail();

        $response->assertRedirect(route('admin.users.index'));
        $this->assertSame('pengguna.baru@example.test', $user->email);
        $this->assertSame('HRD', $user->jabatan);

        $this->post(route('admin.users.store'), [
            'name' => 'Jabatan Tidak Valid',
            'username' => 'jabatan.tidak.valid',
            'email' => 'jabatan.tidak.valid@example.test',
            'password' => 'password123',
            'role_id' => Role::where('slug', 'staff')->value('id'),
            'jabatan' => 'Staff Marcomm',
        ])->assertSessionHasErrors('jabatan');

        $this->post(route('admin.users.store'), [
            'name' => 'Username Duplikat',
            'username' => 'pengguna.baru',
            'email' => 'duplikat@example.test',
            'password' => 'password123',
            'role_id' => Role::where('slug', 'staff')->value('id'),
        ])->assertSessionHasErrors('username');

        $this->post(route('admin.users.store'), [
            'name' => 'Username Tidak Valid',
            'username' => 'Nama Pengguna',
            'email' => 'tidak.valid@example.test',
            'password' => 'password123',
            'role_id' => Role::where('slug', 'staff')->value('id'),
        ])->assertSessionHasErrors('username');
    }

    public function test_administrator_can_search_and_filter_users(): void
    {
        $this->seed();

        $administrator = User::where('username', 'admin')->firstOrFail();
        $division = Division::where('code', 'MD')->firstOrFail();
        $branch = Branch::where('name', 'Palembang')->firstOrFail();
        $staffRole = Role::where('slug', 'staff')->firstOrFail();

        User::factory()->create([
            'name' => 'Akun Nonaktif Contoh',
            'username' => 'akun.nonaktif',
            'email' => 'akun.nonaktif@example.test',
            'role_id' => $staffRole->id,
            'division_id' => $division->id,
            'branch_id' => $branch->id,
            'jabatan' => 'Staff Marcomm',
            'is_active' => false,
        ]);

        $this->actingAs($administrator)
            ->get(route('admin.users.index', [
                'q' => 'akun.nonaktif@example.test',
                'division_id' => $division->id,
                'jabatan' => 'Staff Marcomm',
            ]))
            ->assertOk()
            ->assertSee('Daftar Pengguna')
            ->assertSee('Akun Nonaktif Contoh')
            ->assertSee('akun.nonaktif@example.test')
            ->assertSee('Nonaktif')
            ->assertDontSee('admin_keuangan');
    }

    public function test_user_index_shows_ten_users_per_page_and_keeps_search_when_paginating(): void
    {
        $this->seed();

        $administrator = User::where('username', 'admin')->firstOrFail();
        $users = collect(range(1, 11))->map(fn (int $number) => User::factory()->create([
            'name' => sprintf('Pagination User %02d', $number),
            'username' => sprintf('pagination.user.%02d', $number),
            'email' => sprintf('pagination.user.%02d@example.test', $number),
        ]));

        $this->actingAs($administrator)
            ->get(route('admin.users.index', ['q' => 'Pagination User']))
            ->assertOk()
            ->assertSee('Menampilkan 1–10 dari 11 pengguna')
            ->assertSee('Pagination User 01')
            ->assertSee('Pagination User 10')
            ->assertDontSee('Pagination User 11')
            ->assertSee('aria-label="Halaman berikutnya"', false)
            ->assertSee('bg-rose-700', false)
            ->assertSee('q=Pagination%20User', false);

        $this->get(route('admin.users.index', ['q' => 'Pagination User', 'page' => 2]))
            ->assertOk()
            ->assertSee('Menampilkan 11–11 dari 11 pengguna')
            ->assertSee('Pagination User 11')
            ->assertSee('aria-label="Halaman sebelumnya"', false)
            ->assertSee('aria-current="page" aria-label="Halaman 2"', false)
            ->assertSee('q=Pagination%20User', false);
    }
}
