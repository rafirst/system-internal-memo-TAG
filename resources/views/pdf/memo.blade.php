@php
    use App\Enums\ApprovalStatus;
    use App\Enums\MemoStatus;
    use App\Support\Format;
    use App\Support\Terbilang;

    $approvals = $memo->approvals->where('round', $memo->round)->sortBy('step_order')->values();
    $supervisorApproval = $approvals->first(fn ($approval) => $approval->approverRole?->slug === 'spv');
    $approvalSignatures = $approvals->reject(fn ($approval) => $approval->approverRole?->slug === 'spv')->values();
    $last = $approvalSignatures->last();
    $middle = $approvalSignatures->slice(0, max($approvalSignatures->count() - 1, 0));
    $requester = $memo->onBehalfOf ?? $memo->submitter;
    $isPameran = $memo->memo_kind === 'pameran';
    $pameran = $memo->pameran_details ?? [];
    $requesterStamp = $supervisorApproval
        ? null
        : [
            'note' => $memo->submitted_at ? 'Diajukan' : 'Belum diajukan',
            'date' => $memo->submitted_at?->format('d/m/Y'),
            'state' => $memo->submitted_at ? 'submitted' : 'waiting',
        ];
    $final = in_array($memo->status, [MemoStatus::Approved, MemoStatus::Disbursed, MemoStatus::Completed], true);
    $logo = public_path('assets/images/Logo-TAG.png');

    $cell = function ($a) {
        $roleLabel = match ($a->approverRole->slug) {
            'admin_keuangan' => 'Kepala Keuangan',
            'admin_finance' => 'ADH',
            default => $a->approverRole->label,
        };

        if ($a->status === ApprovalStatus::Approved) {
            $title = in_array($a->approverRole->slug, ['admin_keuangan', 'admin_finance'], true)
                ? $roleLabel
                : ($a->actor?->jabatan ?: $roleLabel);

            return [
                'name' => $a->actor?->name ?? $roleLabel,
                'title' => $title,
                'note' => 'Disetujui',
                'date' => $a->acted_at?->format('d/m/Y'),
                'state' => 'approved',
            ];
        }

        $denied = $a->status === ApprovalStatus::Denied;

        return [
            'name' => $roleLabel,
            'title' => '',
            'note' => $denied ? 'Ditolak' : ($a->status === ApprovalStatus::Pending ? 'Menunggu approval' : 'Menunggu giliran'),
            'date' => $a->acted_at?->format('d/m/Y'),
            'state' => $denied ? 'denied' : 'waiting',
        ];
    };
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Internal Memo {{ $memo->memo_number }}</title>
<style>
    @page { margin: 40px 50px; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #000; line-height: 1.45; }
    .header { width: 100%; border-bottom: 1px solid #000; padding-bottom: 6px; margin-bottom: 14px; }
    .header td { vertical-align: middle; }
    .title { text-align: center; margin: 10px 0 14px; }
    .title .t { font-weight: bold; text-decoration: underline; font-size: 13px; }
    .title .n { font-weight: bold; }
    table.items { width: 100%; border-collapse: collapse; margin: 8px 0 14px; }
    table.items th, table.items td { border: 1px solid #000; padding: 5px 7px; }
    table.items th { background: #eee; text-align: center; }
    .r { text-align: right; } .c { text-align: center; }
    .sign { width: 100%; border-collapse: collapse; margin-top: 18px; }
    .sign td { text-align: center; vertical-align: top; padding: 0 4px; }
    .gap { height: 16px; }
    .nm { font-weight: bold; text-decoration: underline; }
    .stamp { display: block; width: 94%; min-height: 28px; box-sizing: border-box; margin: 0 auto 5px; padding: 4px 2px; border: 1px solid #9ca3af; border-radius: 4px; color: #475569; font-size: 8px; }
    .stamp-date { display: block; margin-top: 2px; font-size: 7px; }
    .stamp.approved { border-color: #65a30d; background: #f7fee7; color: #4d7c0f; }
    .stamp.denied { border-color: #dc2626; background: #fef2f2; color: #b91c1c; }
    .stamp.submitted { background: #f8fafc; }
    .stamp.waiting { background: #f8fafc; }
    .wm { position: fixed; top: 38%; left: 8%; font-size: 90px; color: #e5e5e5; transform: rotate(-30deg); z-index: -1; }
</style>
</head>
<body>
@unless($final)
    <div class="wm">{{ strtoupper($memo->status->label()) }}</div>
@endunless

<table class="header">
    <tr>
        <td>@if(file_exists($logo))<img src="{{ $logo }}" style="height:44px">@else<strong>PT TUNAS AUTO GRAHA</strong>@endif</td>
        <td class="r"><strong>Divisi {{ $memo->division->name }}</strong></td>
    </tr>
</table>

<div class="title">
    <div class="t">INTERNAL MEMO</div>
    <div class="n">No : {{ $memo->memo_number ?? 'DRAFT' }}</div>
</div>

<p>
    Kepada Yth,<br>
    Direksi PT. Tunas Auto Graha<br>
    @if($direksi)Bpk. {{ $direksi->name }}<br>@endif
    Di. Tempat
</p>

<p>Hal : {{ $memo->subject }}</p>
<p>Dengan Hormat,</p>
<p style="text-align:justify; white-space: pre-line;">{{ $memo->opening_paragraph }}</p>

@if($isPameran)
    <table class="items">
        <tr><th>Item</th><th>Keterangan</th><th>Ketentuan</th></tr>
        <tr><td>Lokasi</td><td colspan="2">{{ $pameran['location'] }}</td></tr>
        <tr>
            <td>Periode</td>
            <td>{{ Format::tanggal(\Illuminate\Support\Carbon::parse($pameran['event_date'])) }}</td>
            <td>{{ $pameran['duration'] }}</td>
        </tr>
        <tr><td>Unit Display</td><td colspan="2">{{ $pameran['display_unit'] }}</td></tr>
        <tr><td>SP Tools</td><td colspan="2">{{ $pameran['sp_tools'] }}</td></tr>
        <tr>
            <td>PIC</td>
            <td colspan="2">
                PIC Unit Display: {{ $pameran['display_pic'] }}<br>
                Loading in: {{ $pameran['loading_in'] }}, {{ $pameran['loading_in_time'] }}<br>
                Loading out: {{ $pameran['loading_out'] }}, {{ $pameran['loading_out_time'] }}<br>
                PIC SP Tools Pameran: {{ $pameran['sp_tools_pic'] }}<br>
                PIC Kebersihan Unit: {{ $pameran['cleaning_pic'] }}
            </td>
        </tr>
        <tr><td>Program Marketing</td><td colspan="2" style="white-space:pre-line">{{ $pameran['marketing_programs'] }}</td></tr>
        <tr>
            <td>Biaya</td>
            <td colspan="2">
                {{ Format::rupiah($pameran['budget_min']) }} - {{ Format::rupiah($pameran['budget_max']) }}<br>
                <small>Nilai maksimum menjadi total untuk approval dan pencairan.</small>
            </td>
        </tr>
        <tr>
            <td>Target</td>
            <td colspan="2">Prospect: {{ $pameran['prospect_target'] }} data<br>SPK: {{ $pameran['spk_target'] }} SPK</td>
        </tr>
    </table>
    @if(! empty($pameran['note'] ?? null))
        <p><u><strong>Note:</strong></u><br>{{ $pameran['note'] }}</p>
    @endif
@else
    <table class="items">
        <tr><th style="width:28px">No</th><th>Item</th><th style="width:50px">Jumlah</th><th style="width:90px">Harga</th><th style="width:95px">Total</th></tr>
        @foreach($memo->items as $i)
            <tr>
                <td class="c">{{ $loop->iteration }}</td>
                <td>{{ $i->name }}</td>
                <td class="c">{{ $i->qty ?? '-' }}</td>
                <td class="r">{{ Format::rupiah($i->unit_price) }}</td>
                <td class="r">{{ Format::rupiah($i->subtotal) }}</td>
            </tr>
        @endforeach
        <tr><td colspan="4" class="r"><strong>Total</strong></td><td class="r"><strong>{{ Format::rupiah($memo->total_amount) }}</strong></td></tr>
        <tr><td colspan="5" class="c" style="background:#f5f5f5"><strong>Terbilang: {{ Terbilang::rupiah($memo->total_amount) }}</strong></td></tr>
    </table>
@endif

<p>Demikianlah surat {{ $isPameran ? 'permohonan' : 'pengajuan' }} ini kami sampaikan, atas perhatian dan persetujuannya kami ucapkan terima kasih.</p>

@if(! $isPameran && $memo->bank_name)
    <p><u><strong>Note:</strong></u><br>
    <strong>Pembayaran dapat dilakukan dengan cara transfer ke rekening {{ $memo->bank_name }} {{ $memo->account_number }} an {{ $memo->account_holder }}</strong></p>
@endif

<p>{{ $memo->branch->city }}, {{ Format::tanggal($memo->memo_date) }}</p>

<table class="sign">
    <tr>
        <td style="width:25%">Hormat saya,</td>
        @if($middle->isNotEmpty())<td colspan="{{ $middle->count() }}">Mengetahui,</td>@endif
        @if($last)<td style="width:25%">Menyetujui,</td>@endif
    </tr>
    <tr><td class="gap"></td>@foreach($middle as $a)<td></td>@endforeach @if($last)<td></td>@endif</tr>
    <tr>
        <td>
            @if($supervisorApproval)
                @php $requesterStamp = $cell($supervisorApproval); @endphp
            @endif
            <span class="stamp {{ $requesterStamp['state'] }}">
                {{ $requesterStamp['note'] }}
                @if($requesterStamp['date'])<span class="stamp-date">{{ $requesterStamp['date'] }}</span>@endif
            </span>
            <span class="nm">{{ $requester->name }}</span><br>{{ $requester->jabatan }}
        </td>
        @foreach($middle as $a)
            @php $c = $cell($a); @endphp
            <td>
                <span class="stamp {{ $c['state'] }}">
                    {{ $c['note'] }}
                    @if($c['date'])<span class="stamp-date">{{ $c['date'] }}</span>@endif
                </span>
                <span class="nm">{{ $c['name'] }}</span><br>{{ $c['title'] }}
            </td>
        @endforeach
        @if($last)
            @php $c = $cell($last); @endphp
            <td>
                <span class="stamp {{ $c['state'] }}">
                    {{ $c['note'] }}
                    @if($c['date'])<span class="stamp-date">{{ $c['date'] }}</span>@endif
                </span>
                <span class="nm">{{ $c['name'] }}</span><br>{{ $c['title'] }}
            </td>
        @endif
    </tr>
</table>
</body>
</html>
