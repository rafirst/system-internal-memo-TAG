<?php

namespace App\Http\Controllers;

use App\Models\MemoAttachment;
use App\Models\MemoRequest;
use App\Models\MemoType;
use App\Models\Role;
use App\Services\MemoApprovalService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class MemoRequestController extends Controller
{
    public function __construct(private MemoApprovalService $approvalService) {}

    // daftar memo milik user yang login
    public function index(Request $request)
    {
        $memos = MemoRequest::with(['memoType', 'flow'])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(10);

        return view('memos.index', compact('memos'));
    }

    public function create()
    {
        $memoTypes = MemoType::where('is_active', true)->get();

        return view('memos.create', compact('memoTypes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'memo_type_id' => ['required', 'exists:memo_types,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'estimated_amount' => ['nullable', 'numeric', 'min:0'],
            'attachments.*' => ['nullable', 'file', 'max:10240'], // max 10MB per file
        ]);

        $memoType = MemoType::findOrFail($validated['memo_type_id']);
        $flow = $memoType->activeFlow()->first();

        abort_if(! $flow, 422, 'Alur approval untuk jenis memo ini belum diatur. Hubungi admin.');

        $memo = MemoRequest::create([
            'memo_number' => $this->generateMemoNumber(),
            'user_id' => $request->user()->id,
            'memo_type_id' => $memoType->id,
            'approval_flow_id' => $flow->id,
            'title' => $validated['title'],
            'description' => $validated['description'],
            'estimated_amount' => $validated['estimated_amount'] ?? null,
            'status' => 'draft',
        ]);

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('memo-attachments/'.$memo->id, 'public');

                MemoAttachment::create([
                    'memo_request_id' => $memo->id,
                    'original_name' => $file->getClientOriginalName(),
                    'file_path' => $path,
                    'mime_type' => $file->getClientMimeType(),
                    'size' => $file->getSize(),
                    'uploaded_by' => $request->user()->id,
                ]);
            }
        }

        // langsung ajukan (bisa dipisah jadi tombol "Simpan Draft" vs "Ajukan" jika perlu)
        $this->approvalService->submit($memo);

        return redirect()
            ->route('memos.show', $memo)
            ->with('success', 'Memo berhasil diajukan dengan nomor '.$memo->memo_number);
    }

    public function show(MemoRequest $memo)
    {
        $this->authorizeView($memo);

        $memo->load(['approvals.role', 'approvals.approver', 'attachments', 'memoType', 'user']);

        return view('memos.show', compact('memo'));
    }

    public function resubmit(Request $request, MemoRequest $memo)
    {
        abort_unless($memo->user_id === $request->user()->id, 403);
        abort_unless($memo->status === 'revision', 422, 'Memo ini tidak dalam status revisi.');

        $this->approvalService->resubmit($memo);

        return back()->with('success', 'Memo berhasil diajukan ulang.');
    }

    private function authorizeView(MemoRequest $memo): void
    {
        // pemilik memo, atau siapapun yang pernah/akan jadi approver, boleh lihat
        /** @var User $user */
        $user = Auth::user();
        $isOwner = $memo->user_id === $user->id;
        $isApprover = $memo->approvals()->where('role_id', $user->role_id)->exists();

        abort_unless($isOwner || $isApprover || $user->hasRole('admin_finance'), 403);
    }

    private function generateMemoNumber(): string
    {
        $prefix = 'MEMO/'.now()->format('Y/m');
        $countThisMonth = MemoRequest::whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->count();

        return $prefix.'/'.str_pad($countThisMonth + 1, 4, '0', STR_PAD_LEFT);
    }
}
