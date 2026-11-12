<?php

namespace App\Http\Controllers;

use App\Enums\MemoStatus;
use App\Models\Memo;
use App\Models\User;
use BaconQrCode\Renderer\GDLibRenderer;
use BaconQrCode\Writer;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;

class MemoPdfController extends Controller
{
    public function __invoke(Memo $memo): Response
    {
        Gate::authorize('view', $memo);

        return $this->streamPdf($memo);
    }

    public function showPublic(Memo $memo): Response
    {
        abort_if($memo->status === MemoStatus::Draft, 404);

        return $this->streamPdf($memo);
    }

    private function streamPdf(Memo $memo): Response
    {
        $memo->load(['items', 'division', 'branch', 'submitter', 'onBehalfOf', 'approvals.actor', 'approvals.approverRole']);

        $direksi = User::whereHas('role', fn ($q) => $q->where('slug', 'direksi'))
            ->where('is_active', true)->first();

        $name = 'Internal-Memo-'.str_replace('/', '-', $memo->memo_number ?? 'DRAFT-'.$memo->id).'.pdf';
        $qrCode = null;

        if ($memo->status !== MemoStatus::Draft) {
            $publicPdfUrl = URL::signedRoute('memos.public-pdf', ['memo' => $memo]);
            $renderer = new GDLibRenderer(512);
            $qrCode = 'data:image/png;base64,'.base64_encode((new Writer($renderer))->writeString($publicPdfUrl));
        }

        return Pdf::loadView('pdf.memo', compact('memo', 'direksi', 'qrCode'))->setPaper('a4')->stream($name);
    }
}
