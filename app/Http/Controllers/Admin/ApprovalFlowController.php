<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApprovalFlow;
use App\Models\MemoType;
use App\Models\Role;
use Illuminate\Http\Request;

class ApprovalFlowController extends Controller
{
    public function index()
    {
        $flows = ApprovalFlow::with(['memoType', 'steps.role'])
            ->withCount('steps')
            ->latest()
            ->get();

        return view('admin.approval-flows.index', compact('flows'));
    }

    // form buat flow baru, opsional pre-select jenis memo lewat query string
    public function create(Request $request)
    {
        $memoTypes = MemoType::orderBy('name')->get();
        $roles = Role::orderBy('level')->get();
        $selectedMemoTypeId = $request->query('memo_type_id');

        return view('admin.approval-flows.create', compact('memoTypes', 'roles', 'selectedMemoTypeId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'memo_type_id' => ['required', 'exists:memo_types,id'],
            'name' => ['required', 'string', 'max:255'],
            'is_active' => ['boolean'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['required', 'exists:roles,id'],
        ], [
            'roles.required' => 'Minimal harus ada 1 step approval.',
        ]);

        $flow = ApprovalFlow::create([
            'memo_type_id' => $validated['memo_type_id'],
            'name' => $validated['name'],
            'is_active' => $request->boolean('is_active', true),
        ]);

        // urutan step_order mengikuti urutan role yang dikirim dari form (drag-order di UI)
        foreach (array_values($validated['roles']) as $index => $roleId) {
            $flow->steps()->create([
                'step_order' => $index + 1,
                'role_id' => $roleId,
                'label' => 'Persetujuan '.Role::find($roleId)->name,
            ]);
        }

        return redirect()->route('admin.approval-flows.index')->with('success', 'Alur approval berhasil dibuat.');
    }

    public function edit(ApprovalFlow $approvalFlow)
    {
        $approvalFlow->load('steps.role');
        $memoTypes = MemoType::orderBy('name')->get();
        $roles = Role::orderBy('level')->get();

        return view('admin.approval-flows.edit', [
            'flow' => $approvalFlow,
            'memoTypes' => $memoTypes,
            'roles' => $roles,
        ]);
    }

    // update info dasar flow (nama, jenis memo, aktif/tidak) - urutan step diatur lewat ApprovalStepController
    public function update(Request $request, ApprovalFlow $approvalFlow)
    {
        $validated = $request->validate([
            'memo_type_id' => ['required', 'exists:memo_types,id'],
            'name' => ['required', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ]);

        $approvalFlow->update([
            'memo_type_id' => $validated['memo_type_id'],
            'name' => $validated['name'],
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'Info alur approval berhasil diperbarui.');
    }

    public function destroy(ApprovalFlow $approvalFlow)
    {
        if ($approvalFlow->memoType->memoRequests()->where('approval_flow_id', $approvalFlow->id)->exists()) {
            return back()->with('error', 'Alur ini tidak bisa dihapus karena sudah pernah dipakai untuk pengajuan memo. Nonaktifkan saja.');
        }

        $approvalFlow->delete(); // steps ikut terhapus (cascadeOnDelete di migration)

        return redirect()->route('admin.approval-flows.index')->with('success', 'Alur approval berhasil dihapus.');
    }
}
