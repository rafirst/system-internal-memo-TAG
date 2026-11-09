<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Division;
use App\Models\Memo;
use App\Models\User;
use App\Notifications\MemoNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemoEmailNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_memo_email_matches_the_memo_document_without_bank_account_details(): void
    {
        $this->seed();
        $submitter = User::where('username', 'staff.marcomm')->firstOrFail();
        $division = Division::where('code', 'MD')->firstOrFail();
        $branch = Branch::where('name', 'Palembang')->firstOrFail();
        $memo = Memo::create([
            'memo_number' => 'IM/EMAIL/2026/0001',
            'submitter_id' => $submitter->id,
            'division_id' => $division->id,
            'branch_id' => $branch->id,
            'subject' => 'Pengujian notifikasi email',
            'opening_paragraph' => 'Memo pengujian.',
            'total_amount' => 987654321,
            'bank_name' => 'BANK-RAHASIA',
            'account_number' => '1234567890123456',
            'account_holder' => 'Rahasia',
            'memo_date' => now()->toDateString(),
            'status' => 'pending',
        ]);
        $memo->items()->create([
            'name' => 'ITEM-EMAIL-TEST',
            'qty' => 2,
            'unit_price' => 12345,
            'subtotal' => 24690,
        ]);
        $notification = new MemoNotification(
            $memo,
            'Memo menunggu persetujuan Anda',
            'Pesan in-app.',
            'Silakan masuk ke sistem untuk memeriksa memo.',
        );

        $mail = $notification->toMail($submitter);
        $html = (string) $mail->render();
        $text = view('emails.memo_text', $mail->viewData)->render();

        $this->assertSame(
            '[Internal Memo] Memo menunggu persetujuan Anda — IM/EMAIL/2026/0001',
            $mail->subject,
        );
        $this->assertStringContainsString('Buka Memo', $html);
        $this->assertStringContainsString('IM/EMAIL/2026/0001', $html);
        $this->assertStringContainsString('IM/EMAIL/2026/0001', $text);
        $this->assertStringContainsString('INTERNAL MEMO', $html);
        $this->assertStringContainsString('Dengan Hormat,', $html);
        $this->assertStringContainsString('ITEM-EMAIL-TEST', $html);
        $this->assertStringContainsString('Rp 12.345,-', $html);
        $this->assertStringContainsString('Terbilang:', $html);
        $this->assertStringContainsString('INTERNAL MEMO', $text);
        $this->assertStringContainsString('ITEM-EMAIL-TEST', $text);
        $this->assertStringContainsString('Rp 12.345,-', $text);
        $this->assertStringContainsString('Rp 987.654.321,-', $html);
        $this->assertStringContainsString('Rp 987.654.321,-', $text);
        $this->assertStringNotContainsString('BANK-RAHASIA', $html);
        $this->assertStringNotContainsString('BANK-RAHASIA', $text);
        $this->assertStringNotContainsString('1234567890123456', $html);
        $this->assertStringNotContainsString('1234567890123456', $text);
        $this->assertStringContainsString('Detail rekening tidak disertakan', $html);
    }
}
