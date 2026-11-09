<?php

namespace App\Http\Controllers;

use App\Models\MemoRequest;
use App\Services\MemoApprovalService;
use Illuminate\Http\Request;

class ApprovalController extends Controller
{
    public function __construct(private MemoApprovalService $approvalService) {}

    // daftar memo yang butuh action dari role user yang login, di step aktifnya masing-masing
    public function index(Request $request)
    {
        $user = $request->user();

        $memos = MemoRequest::with(['memoType', 'user'])
            ->whereIn('status', ['submitted', 'in_progress'])
            ->whereHas('approvals', function ($q) use ($user) {
                $q->whereColumn('memo_approvals.step_order', 'memo_requests.current_step')
                    ->where('memo_approvals.role_id', $user->role_id)
                    ->where('memo_approvals.status', 'pending');
            })
            ->latest()
            ->paginate(10);

        return view('approvals.index', compact('memos'));
    }

    public function approve(Request $request, MemoRequest $memo)
    {
        $request->validate(['notes' => ['nullable', 'string']]);

        $this->approvalService->approve($memo, $request->user(), $request->input('notes'));

        return back()->with('success', 'Memo berhasil di-approve, lanjut ke step berikutnya.');
    }

    public function reject(Request $request, MemoRequest $memo)
    {
        $request->validate(['notes' => ['required', 'string']]);

        $this->approvalService->reject($memo, $request->user(), $request->input('notes'));

        return back()->with('success', 'Memo ditolak.');
    }

    public function revision(Request $request, MemoRequest $memo)
    {
        $request->validate(['notes' => ['required', 'string']]);

        $this->approvalService->requestRevision($memo, $request->user(), $request->input('notes'));

        return back()->with('success', 'Memo dikembalikan ke pengaju untuk direvisi.');
    }
}
