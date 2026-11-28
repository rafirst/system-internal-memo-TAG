<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Division;
use App\Models\Role;
use App\Models\User;
use App\Notifications\TestEmailNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;
use RuntimeException;
use Throwable;

/** Manajemen pengguna (khusus administrator). */
class UserController extends Controller
{
    private const JABATAN_OPTIONS = [
        'HRD',
        'ADH',
        'Supervisor',
        'Staff',
        'Audit',
        'Direksi',
        'Kepala Keuangan',
        'Kepala Finance',
        'Kepala Cabang',
        'Kepala Kontrak',
        'Kepala Bengkel',
        'Kepala Stock',
    ];

    public function index(Request $request)
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'division_id' => ['nullable', 'integer', 'exists:divisions,id'],
            'jabatan' => ['nullable', 'string', 'max:255'],
        ]);

        $usersQuery = User::query()
            ->with(['role', 'division', 'branch'])
            ->when($filters['q'] ?? null, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('jabatan', 'like', "%{$search}%")
                        ->orWhereHas('division', fn ($divisionQuery) => $divisionQuery->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('role', fn ($roleQuery) => $roleQuery->where('label', 'like', "%{$search}%"))
                        ->orWhereHas('branch', fn ($branchQuery) => $branchQuery->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($filters['division_id'] ?? null, fn ($query, $divisionId) => $query->where('division_id', $divisionId))
            ->when($filters['jabatan'] ?? null, fn ($query, $jabatan) => $query->where('jabatan', $jabatan));

        return view('users.index', [
            'users' => $usersQuery->orderBy('name')->paginate(10)->withQueryString(),
            'divisions' => Division::orderBy('name')->get(['id', 'name']),
            'positions' => User::query()->whereNotNull('jabatan')->where('jabatan', '!=', '')
                ->distinct()->orderBy('jabatan')->pluck('jabatan'),
            'filters' => $filters,
        ]);
    }

    public function create()
    {
        return view('users.form', $this->options() + [
            'positions' => $this->positionOptions(),
            'user' => null,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        User::create($data);

        return redirect()->route('admin.users.index')->with('success', 'Pengguna ditambahkan.');
    }

    public function edit(User $user)
    {
        return view('users.form', $this->options() + [
            'positions' => $this->positionOptions($user),
            'user' => $user,
        ]);
    }

    public function update(Request $request, User $user)
    {
        $data = $this->validated($request, $user);
        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }
        if ($user->id === $request->user()->id) {
            $data['is_active'] = true; // cegah menonaktifkan diri sendiri
        }
        $user->update($data);

        return redirect()->route('admin.users.index')->with('success', 'Pengguna diperbarui.');
    }

    public function testEmail(User $user)
    {
        try {
            if (
                ! $user->is_active
                || ! is_string($user->email)
                || ! filter_var($user->email, FILTER_VALIDATE_EMAIL)
                || str_ends_with(strtolower($user->email), '.invalid')
            ) {
                throw new RuntimeException('Akun tidak aktif atau alamat email tidak valid.');
            }

            Notification::send($user, new TestEmailNotification);
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('error', 'Gagal mengirim email tes: '.mb_substr($exception->getMessage(), 0, 200));
        }

        return back()->with('success', "Email tes dikirim ke {$user->email}. Minta pengguna mengecek inbox (dan folder spam).");
    }

    private function validated(Request $request, ?User $user = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => [
                'required',
                'string',
                'min:3',
                'max:50',
                'regex:/^[a-z0-9._]+$/',
                Rule::unique('users', 'username')->ignore($user?->id),
            ],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user?->id)],
            'password' => [$user ? 'nullable' : 'required', 'string', 'min:8'],
            'role_id' => ['required', 'exists:roles,id'],
            'division_id' => ['nullable', 'exists:divisions,id'],
            'office_type' => ['required', Rule::in(['head_office', 'branch'])],
            'branch_id' => ['nullable', 'required_if:office_type,branch', 'exists:branches,id'],
            'jabatan' => ['nullable', 'string', Rule::in($this->positionOptions($user))],
        ]);
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }

    private function options(): array
    {
        return [
            'roles' => Role::orderBy('label')->get(),
            'divisions' => Division::orderBy('name')->get(),
            'branches' => Branch::orderBy('name')->get(),
        ];
    }

    private function positionOptions(?User $user = null): array
    {
        $positions = self::JABATAN_OPTIONS;

        if (filled($user?->jabatan) && ! in_array($user->jabatan, $positions, true)) {
            array_unshift($positions, $user->jabatan);
        }

        return $positions;
    }
}
