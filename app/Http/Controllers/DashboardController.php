<?php

namespace App\Http\Controllers;

use App\Enums\MemoStatus;
use App\Models\Memo;
use App\Services\MemoActivityFeed;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request, MemoActivityFeed $feed)
    {
        $user = $request->user();
        $with = ['submitter', 'onBehalfOf', 'division'];

        // Menunggu keputusan user yang ditugaskan pada giliran aktif.
        $awaiting = Memo::where('status', MemoStatus::Pending->value)
            ->whereHas('approvals', fn ($q) => $q
                ->whereColumn('step_order', 'memos.current_step')
                ->whereColumn('round', 'memos.round')
                ->where('status', 'pending')
                ->where(fn ($approvers) => $approvers
                    ->where('assigned_approver_id', $user->id)
                    ->orWhere(fn ($legacyApprovers) => $legacyApprovers
                        ->whereNull('assigned_approver_id')
                        ->where('approver_role_id', $user->role_id))))
            ->with($with)->latest('submitted_at')->get();

        // Antrian pencairan (Admin Keuangan)
        $toDisburse = $user->hasRole('admin_keuangan')
            ? Memo::where('status', MemoStatus::Approved->value)->with($with)->latest('approved_at')->get()
            : collect();

        // Memo milik sendiri yang butuh tindakan: revisi (denied) atau unggah realisasi (disbursed)
        $mine = Memo::where('submitter_id', $user->id)
            ->whereIn('status', [MemoStatus::Denied->value, MemoStatus::Disbursed->value, MemoStatus::Draft->value])
            ->with($with)->latest()->get();

        $counts = Memo::visibleTo($user)
            ->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        $recentMemos = Memo::visibleTo($user)
            ->with($with)
            ->latest()
            ->limit(5)
            ->get();

        // Umpan kejadian memo, cakupan dan jenisnya mengikuti role pengguna.
        $recentActivity = $feed->forUser($user, 4);
        $activityHeading = $feed->headingFor($user);

        return view('dashboard', compact(
            'awaiting',
            'toDisburse',
            'mine',
            'counts',
            'recentMemos',
            'recentActivity',
            'activityHeading',
        ));
    }
}
