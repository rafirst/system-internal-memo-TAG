<?php

namespace App\Http\Controllers;

use App\Enums\MemoStatus;
use App\Models\Memo;
use App\Models\User;
use BaconQrCode\Renderer\GDLibRenderer;
use BaconQrCode\Writer;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdfDocument;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;

class MemoPdfController extends Controller
{
    public function __invoke(Memo $memo): Response
    {
        Gate::authorize('view', $memo);

        return $this->pdfDocument($memo)->stream($this->pdfName($memo));
    }

    public function download(Memo $memo): Response
    {
        Gate::authorize('view', $memo);

        return $this->pdfDocument($memo)->download($this->pdfName($memo));
    }

    public function showPublic(Memo $memo): Response
    {
        abort_if($memo->status === MemoStatus::Draft, 404);

        return $this->streamPdf($memo);
    }

    private function streamPdf(Memo $memo): Response
    {
        return $this->pdfDocument($memo)->stream($this->pdfName($memo));
    }

    private function pdfDocument(Memo $memo): DomPdfDocument
    {
        return Pdf::loadView('pdf.memo', $this->memoViewData($memo))
            ->setPaper('a4');
    }

    private function pdfName(Memo $memo): string
    {
        return 'Internal-Memo-'.str_replace('/', '-', $memo->memo_number ?? 'DRAFT-'.$memo->id).'.pdf';
    }

    /**
     * @return array{memo: Memo, direksi: ?User, qrCode: ?string}
     */
    private function memoViewData(Memo $memo): array
    {
        $memo->load([
            'items',
            'division',
            'branch',
            'submitter',
            'onBehalfOf',
            'approvals.actor',
            'approvals.assignedApprover',
            'approvals.approverRole',
        ]);

        $direksi = User::whereHas('role', fn ($q) => $q->where('slug', 'direksi'))
            ->where('is_active', true)->first();

        $qrCode = null;

        if ($memo->status !== MemoStatus::Draft) {
            $publicPdfUrl = URL::signedRoute('memos.public-pdf', ['memo' => $memo]);
            $renderer = new GDLibRenderer(512);
            $qrCode = 'data:image/png;base64,'.base64_encode((new Writer($renderer))->writeString($publicPdfUrl));
        }

        return compact('memo', 'direksi', 'qrCode');
    }
}
