<?php

namespace Tests\Feature;

use App\Enums\MemoStatus;
use App\Models\Branch;
use App\Models\Division;
use App\Models\Memo;
use App\Models\MemoAttachment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MemoFilePreviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_memo_and_supported_attachments_can_be_previewed_and_downloaded(): void
    {
        $this->seed();
        Storage::fake('local');

        $submitter = User::where('username', 'staff.marcomm')->firstOrFail();
        $memo = $this->createMemo($submitter);
        $pdfAttachment = $this->createAttachment($memo, $submitter, 'supporting.pdf', "%PDF-1.4\n%%EOF");
        $documentAttachment = $this->createAttachment($memo, $submitter, 'supporting.docx', 'document content');

        $showResponse = $this->actingAs($submitter)
            ->get(route('memos.show', $memo))
            ->assertOk()
            ->assertSeeText('Preview Memo')
            ->assertSeeText('Unduh PDF')
            ->assertSee(route('memos.pdf', $memo))
            ->assertSee(route('memos.attachments.preview', [$memo, $pdfAttachment]))
            ->assertSee(route('memos.attachments.download', [$memo, $pdfAttachment]))
            ->assertDontSee(route('memos.attachments.preview', [$memo, $documentAttachment]));

        $this->assertNotEmpty($showResponse->getContent());

        $this->get(route('memos.attachments.preview', [$memo, $pdfAttachment]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader('content-disposition', 'inline; filename=supporting.pdf');

        $this->get(route('memos.attachments.download', [$memo, $pdfAttachment]))
            ->assertOk()
            ->assertHeader('content-disposition', 'attachment; filename=supporting.pdf');

        $this->get(route('memos.attachments.preview', [$memo, $documentAttachment]))
            ->assertStatus(415);

        $this->get(route('memos.attachments.download', [$memo, $documentAttachment]))
            ->assertOk()
            ->assertHeader('content-disposition', 'attachment; filename=supporting.docx');

        $this->get(route('memos.pdf', $memo))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader('content-disposition', 'inline; filename=Internal-Memo-DRAFT-'.$memo->id.'.pdf');

        $this->get(route('memos.pdf.download', $memo))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader('content-disposition', 'attachment; filename=Internal-Memo-DRAFT-'.$memo->id.'.pdf');
    }

    private function createMemo(User $submitter): Memo
    {
        $division = Division::where('code', 'MD')->firstOrFail();
        $branch = Branch::where('name', 'Palembang')->firstOrFail();

        return Memo::create([
            'submitter_id' => $submitter->id,
            'division_id' => $division->id,
            'branch_id' => $branch->id,
            'subject' => 'Memo pengujian preview',
            'opening_paragraph' => 'Memo untuk pengujian preview dan unduh.',
            'total_amount' => 100000,
            'memo_date' => now()->toDateString(),
            'status' => MemoStatus::Draft,
        ]);
    }

    private function createAttachment(
        Memo $memo,
        User $uploader,
        string $originalName,
        string $contents,
    ): MemoAttachment {
        $path = "memos/{$memo->id}/supporting/{$originalName}";
        Storage::disk('local')->put($path, $contents);

        return MemoAttachment::create([
            'memo_id' => $memo->id,
            'type' => 'supporting',
            'original_name' => $originalName,
            'path' => $path,
            'uploaded_by' => $uploader->id,
        ]);
    }
}
