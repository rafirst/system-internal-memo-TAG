<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Division;
use Illuminate\Http\Request;

class DivisionController extends Controller
{
    public function index()
    {
        $divisions = Division::withCount('users')->orderBy('name')->get();

        return view('admin.divisions.index', compact('divisions'));
    }

    public function create()
    {
        $division = new Division;

        return view('admin.divisions.create', compact('division'));
    }

    public function store(Request $request)
    {
        $validated = $this->validated($request);

        Division::create($validated);

        return redirect()->route('admin.divisions.index')->with('success', 'Divisi berhasil ditambahkan.');
    }

    public function edit(Division $division)
    {
        return view('admin.divisions.edit', compact('division'));
    }

    public function update(Request $request, Division $division)
    {
        $validated = $this->validated($request, $division->id);

        $division->update($validated);

        return redirect()->route('admin.divisions.index')->with('success', 'Divisi berhasil diperbarui.');
    }

    public function destroy(Division $division)
    {
        if ($division->users()->exists()) {
            return back()->with('error', 'Divisi tidak bisa dihapus karena masih dipakai oleh user.');
        }

        $division->delete();

        return back()->with('success', 'Divisi berhasil dihapus.');
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50', 'unique:divisions,code'.($ignoreId ? ",{$ignoreId}" : '')],
        ]);
    }
}
