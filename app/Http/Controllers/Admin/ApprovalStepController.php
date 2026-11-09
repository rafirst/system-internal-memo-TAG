<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApprovalFlow;
use App\Models\ApprovalStep;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApprovalStepController extends Controller
{
    // tambah 1 step baru di urutan paling akhir flow
    public function store(Request $request, ApprovalFlow $approvalFlow)
    {
        $validated = $request->validate([
            'role_id' => ['required', 'exists:roles,id'],
        ]);

        $nextOrder = $approvalFlow->steps()->max('step_order') + 1;

        $approvalFlow->steps()->create([
            'step_order' => $nextOrder,
            'role_id' => $validated['role_id'],
            'label' => 'Persetujuan '.Role::find($validated['role_id'])->name,
        ]);

        return back()->with('success', 'Step approval berhasil ditambahkan di urutan ke-'.$nextOrder.'.');
    }

    // hapus 1 step, lalu rapikan ulang step_order sisanya biar tetap berurutan 1,2,3...
    public function destroy(ApprovalFlow $approvalFlow, ApprovalStep $step)
    {
        abort_unless($step->approval_flow_id === $approvalFlow->id, 404);

        DB::transaction(function () use ($approvalFlow, $step) {
            $step->delete();

            $remaining = $approvalFlow->steps()->orderBy('step_order')->get();
            foreach ($remaining as $index => $s) {
                $s->update(['step_order' => $index + 1]);
            }
        });

        return back()->with('success', 'Step approval berhasil dihapus, urutan dirapikan otomatis.');
    }

    // geser urutan step naik/turun satu posisi (tukar step_order dengan tetangganya)
    public function move(Request $request, ApprovalFlow $approvalFlow, ApprovalStep $step)
    {
        abort_unless($step->approval_flow_id === $approvalFlow->id, 404);

        $direction = $request->validate(['direction' => ['required', 'in:up,down']])['direction'];
        $targetOrder = $direction === 'up' ? $step->step_order - 1 : $step->step_order + 1;

        $neighbor = $approvalFlow->steps()->where('step_order', $targetOrder)->first();

        if (! $neighbor) {
            return back()->with('error', 'Step sudah berada di posisi paling '.($direction === 'up' ? 'atas' : 'bawah').'.');
        }

        DB::transaction(function () use ($step, $neighbor) {
            $originalOrder = $step->step_order;
            $step->update(['step_order' => $neighbor->step_order]);
            $neighbor->update(['step_order' => $originalOrder]);
        });

        return back()->with('success', 'Urutan step berhasil diubah.');
    }
}
