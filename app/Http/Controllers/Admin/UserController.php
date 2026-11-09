<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Division;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::with(['role', 'division'])
            ->when($request->search, fn ($query) => $query->where(function ($query) use ($request) {
                $search = "%{$request->search}%";

                $query->where('name', 'like', $search)
                    ->orWhere('username', 'like', $search);
            }))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        [$roles, $divisions] = $this->options();
        $user = new User;

        return view('admin.users.create', compact('user', 'roles', 'divisions'));
    }

    public function store(Request $request)
    {
        $validated = $this->validated($request);
        $validated['password'] = Hash::make($validated['password']);

        User::create($validated);

        return redirect()->route('admin.users.index')->with('success', 'User berhasil ditambahkan.');
    }

    public function edit(User $user)
    {
        [$roles, $divisions] = $this->options();

        return view('admin.users.edit', compact('user', 'roles', 'divisions'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $this->validated($request, $user->getKey());

        if (! empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        return redirect()->route('admin.users.index')->with('success', 'User berhasil diperbarui.');
    }

    public function destroy(User $user)
    {
        $currentUserId = Auth::id();

        if ($user->getKey() === $currentUserId) {
            return back()->with('error', 'Tidak bisa menghapus akun Anda sendiri.');
        }

        if ($user->memoRequests()->exists()) {
            return back()->with('error', 'User tidak bisa dihapus karena punya riwayat pengajuan memo. Nonaktifkan saja.');
        }

        $user->delete();

        return back()->with('success', 'User berhasil dihapus.');
    }

    private function options(): array
    {
        return [Role::orderBy('level')->get(), Division::orderBy('name')->get()];
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        $passwordRule = $ignoreId ? ['nullable', 'string', 'min:8'] : ['required', 'string', 'min:8'];

        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', Rule::unique('users', 'username')->ignore($ignoreId)],
            'password' => $passwordRule,
            'role_id' => ['nullable', 'exists:roles,id'],
            'division_id' => ['nullable', 'exists:divisions,id'],
            'jabatan' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
            'is_admin' => ['boolean'],
        ]);
    }
}
