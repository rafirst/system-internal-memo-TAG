<?php

namespace App\Http\Controllers;

use App\Models\Memo;
use App\Services\ApprovalService;
use DomainException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class MemoActionController extends Controller
{
    public function __construct(private ApprovalService $service)
    {
    }

    public function submit(Request $request, Memo $memo)
    {
        Gate::authorize('submit', $memo);

        return $this->run($memo, fn () => $this->service->submit($memo, $request->user()), 'Memo berhasil diajukan.');
    }

    public function approve(Request $request, Memo $memo)
    {
        Gate::authorize('act', $memo);
        $data = $request->validate(['comment' => ['nullable', 'string', 'max:1000']]);

        return $this->run($memo, fn () => $this->service->approve($memo, $request->user(), $data['comment'] ?? null), 'Memo disetujui.');
    }

    public function deny(Request $request, Memo $memo)
    {
        Gate::authorize('act', $memo);
        $data = $request->validate(['comment' => ['required', 'string', 'max:1000']]);

        return $this->run($memo, fn () => $this->service->deny($memo, $request->user(), $data['comment']), 'Memo ditolak dan dikembalikan ke pengaju.');
    }

    public function disburse(Request $request, Memo $memo)
    {
        Gate::authorize('disburse', $memo);

        return $this->run($memo, fn () => $this->service->disburse($memo, $request->user()), 'Dana ditandai sudah dicairkan.');
    }

    public function realization(Request $request, Memo $memo)
    {
        Gate::authorize('uploadRealization', $memo);
        $data = $request->validate([
            'files' => ['required', 'array', 'min:1', 'max:10'],
            'files.*' => ['file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ], [], ['files' => 'Bukti realisasi', 'files.*' => 'Bukti realisasi']);

        return $this->run($memo, fn () => $this->service->complete($memo, $request->user(), $data['files']), 'Bukti realisasi tersimpan. Memo selesai.');
    }

    private function run(Memo $memo, callable $action, string $success)
    {
        try {
            $action();
        } catch (DomainException $e) {
            return redirect()->route('memos.show', $memo)->with('error', $e->getMessage());
        }

        return redirect()->route('memos.show', $memo)->with('success', $success);
    }
}
