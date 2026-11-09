<?php

namespace App\Http\Controllers;

use App\Models\Memo;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Gate;

class MemoPdfController extends Controller
{
    public function __invoke(Memo $memo)
    {
        Gate::authorize('view', $memo);

        $memo->load(['items', 'division', 'branch', 'submitter', 'onBehalfOf', 'approvals.actor', 'approvals.approverRole']);

        $direksi = User::whereHas('role', fn ($q) => $q->where('slug', 'direksi'))
            ->where('is_active', true)->first();

        $name = 'Internal-Memo-' . str_replace('/', '-', $memo->memo_number ?? 'DRAFT-' . $memo->id) . '.pdf';

        return Pdf::loadView('pdf.memo', compact('memo', 'direksi'))->setPaper('a4')->stream($name);
    }
}
