<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Division;
use App\Models\Memo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class PameranMemoTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_submit_a_pameran_memo_with_the_maximum_budget_for_approval(): void
    {
        $this->seed();

        $administrator = User::where('username', 'admin')->firstOrFail();
        $division = Division::where('code', 'MD')->firstOrFail();
        $branch = Branch::where('name', 'Palembang')->firstOrFail();
        $approver = User::where('username', 'yogi')->firstOrFail();

        $this->actingAs($administrator)
            ->get(route('memos.create'))
            ->assertOk()
            ->assertSee('name="memo_kind"', false)
            ->assertSee('name="pameran[location]"', false)
            ->assertSee('items[', false);

        $response = $this->post(route('memos.store'), [
            'division_id' => $division->id,
            'branch_id' => $branch->id,
            'memo_kind' => 'pameran',
            'subject' => 'Public Display Cust Gathering',
            'opening_paragraph' => 'Pengajuan Public Display di Vamos Padel.',
            'memo_date' => '2026-01-30',
            'pameran' => $this->pameranDetails(),
            'approver_ids' => [$approver->id],
            'action' => 'submit',
        ]);

        $memo = Memo::where('subject', 'Public Display Cust Gathering')->firstOrFail();

        $response->assertRedirect(route('memos.show', $memo));
        $this->assertSame('pameran', $memo->memo_kind);
        $this->assertSame('Vamos Padel Palembang', $memo->pameran_details['location']);
        $this->assertSame(500000.0, (float) $memo->total_amount);
        $this->assertDatabaseHas('memo_items', [
            'memo_id' => $memo->id,
            'name' => 'Estimasi biaya Pameran (maksimum)',
            'unit_price' => 500000,
        ]);

        $this->get(route('memos.show', $memo))
            ->assertOk()
            ->assertSeeText('Detail Pameran')
            ->assertSeeText('Vamos Padel Palembang')
            ->assertSeeText('Rp 350.000,-')
            ->assertSeeText('Rp 500.000,-');

        $this->get(route('memos.preview', $memo))
            ->assertOk()
            ->assertSeeText('INTERNAL MEMO')
            ->assertSee('class="preview-toolbar"', false)
            ->assertSee('@media screen and (max-width: 600px)', false);

        $this->get(route('memos.pdf', $memo))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $publicPdfUrl = URL::signedRoute('memos.public-pdf', ['memo' => $memo]);
        auth()->logout();

        $publicPdfResponse = $this->get($publicPdfUrl)
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
        preg_match_all('/\/Subtype\s*\/Image/', $publicPdfResponse->getContent(), $pdfImages);
        $this->assertGreaterThanOrEqual(2, count($pdfImages[0]));
        $this->assertMatchesRegularExpression('/\/Width\s+512\b/', $publicPdfResponse->getContent());

        $memo->update(['status' => 'approved']);
        $this->get($publicPdfUrl)
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->get(route('memos.public-pdf', $memo))->assertForbidden();

        $memo->update(['status' => 'draft']);
        $draftPdfUrl = URL::signedRoute('memos.public-pdf', ['memo' => $memo]);

        $this->get($draftPdfUrl)->assertNotFound();
    }

    public function test_pameran_memo_rejects_a_budget_range_where_the_maximum_is_less_than_the_minimum(): void
    {
        $this->seed();

        $administrator = User::where('username', 'admin')->firstOrFail();
        $division = Division::where('code', 'MD')->firstOrFail();
        $branch = Branch::where('name', 'Palembang')->firstOrFail();
        $pameran = $this->pameranDetails();
        $pameran['budget_min'] = 500000;
        $pameran['budget_max'] = 350000;

        $this->actingAs($administrator)
            ->post(route('memos.store'), [
                'division_id' => $division->id,
                'branch_id' => $branch->id,
                'memo_kind' => 'pameran',
                'subject' => 'Rentang biaya tidak valid',
                'opening_paragraph' => 'Pengajuan Pameran.',
                'memo_date' => '2026-01-30',
                'pameran' => $pameran,
            ])
            ->assertSessionHasErrors('pameran.budget_max');

        $this->assertDatabaseMissing('memos', ['subject' => 'Rentang biaya tidak valid']);
    }

    /**
     * @return array<string, int|string>
     */
    private function pameranDetails(): array
    {
        return [
            'location' => 'Vamos Padel Palembang',
            'event_date' => '2026-02-01',
            'duration' => '1 Hari',
            'display_unit' => 'Alphard',
            'sp_tools' => 'Tripod Banner, Spanduk, Name Plat',
            'display_pic' => 'PIC Unit Display',
            'loading_in' => 'PIC Unit Display',
            'loading_in_time' => 'Minggu siang',
            'loading_out' => 'PDC',
            'loading_out_time' => 'Minggu sore',
            'sp_tools_pic' => 'Marcomm',
            'cleaning_pic' => 'PDC',
            'marketing_programs' => 'Program Spesial Alphard & Vellfire',
            'budget_min' => 350000,
            'budget_max' => 500000,
            'prospect_target' => 25,
            'spk_target' => 3,
            'note' => 'Team Sales wajib mengikuti SOP Guidance Public Display',
        ];
    }
}
