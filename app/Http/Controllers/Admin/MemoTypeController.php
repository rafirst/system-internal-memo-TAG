<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MemoType;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MemoTypeController extends Controller
{
    public function index()
    {
        $memoTypes = MemoType::withCount(['approvalFlows', 'memoRequests'])->orderBy('name')->get();

        return view('admin.memo-types.index', compact('memoTypes'));
    }

    public function create()
    {
        $memoType = new MemoType;

        return view('admin.memo-types.create', compact('memoType'));
    }

    public function store(Request $request)
    {
        $validated = $this->validated($request);

        $memoType = MemoType::create($validated);

        return redirect()
            ->route('admin.approval-flows.create', ['memo_type_id' => $memoType->id])
            ->with('success', 'Jenis memo berhasil dibuat. Sekarang atur alur approval-nya.');
    }

    public function edit(MemoType $memoType)
    {
        return view('admin.memo-types.edit', compact('memoType'));
    }

    public function update(Request $request, MemoType $memoType)
    {
        $validated = $this->validated($request, $memoType->id);

        $memoType->update($validated);

        return redirect()->route('admin.memo-types.index')->with('success', 'Jenis memo berhasil diperbarui.');
    }

    public function destroy(MemoType $memoType)
    {
        if ($memoType->memoRequests()->exists()) {
            return back()->with('error', 'Jenis memo tidak bisa dihapus karena sudah ada pengajuan. Nonaktifkan saja.');
        }

        $memoType->delete();

        return back()->with('success', 'Jenis memo berhasil dihapus.');
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', Rule::unique('memo_types', 'code')->ignore($ignoreId)],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);
    }
}
