<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::withCount(['users', 'approvalSteps'])->orderBy('level')->get();

        return view('admin.roles.index', compact('roles'));
    }

    public function create()
    {
        $role = new Role;

        return view('admin.roles.create', compact('role'));
    }

    public function store(Request $request)
    {
        $validated = $this->validated($request);

        Role::create($validated);

        return redirect()->route('admin.roles.index')->with('success', 'Role berhasil ditambahkan.');
    }

    public function edit(Role $role)
    {
        return view('admin.roles.edit', compact('role'));
    }

    public function update(Request $request, Role $role)
    {
        $validated = $this->validated($request, $role->id);

        $role->update($validated);

        return redirect()->route('admin.roles.index')->with('success', 'Role berhasil diperbarui.');
    }

    public function destroy(Role $role)
    {
        if ($role->users()->exists() || $role->approvalSteps()->exists()) {
            return back()->with('error', 'Role tidak bisa dihapus karena masih dipakai oleh user atau approval flow.');
        }

        $role->delete();

        return back()->with('success', 'Role berhasil dihapus.');
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:roles,slug'.($ignoreId ? ",{$ignoreId}" : '')],
            'level' => ['required', 'integer', 'min:0'],
        ]);

        $validated['slug'] = $validated['slug'] ?: Str::slug($validated['name']);

        return $validated;
    }
}
