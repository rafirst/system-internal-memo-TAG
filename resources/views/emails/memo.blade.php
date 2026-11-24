@php
    use App\Enums\ApprovalStatus;
    use App\Support\Format;
    use App\Support\Terbilang;

    $approvals = $memo->currentApprovals->sortBy('step_order')->values();
    $approvalSignatures = $approvals;
    $requester = $memo->onBehalfOf ?? $memo->submitter;
    $logoPath = public_path('assets/images/Logo-TAG.png');
    if (! is_file($logoPath)) {
        $logoPath = public_path('assets/images/Logo-TAG-white.png');
    }

    $approvalCell = function ($approval) {
        $roleLabel = match ($approval->approverRole->slug) {
            'admin_keuangan' => 'Kepala Keuangan',
            'admin_finance' => 'ADH',
            default => $approval->approverRole->label,
        };

        if ($approval->status === ApprovalStatus::Approved) {
            return [
                'name' => $approval->actor?->name ?? $roleLabel,
                'title' => in_array($approval->approverRole->slug, ['admin_keuangan', 'admin_finance'], true)
                    ? $roleLabel
                    : ($approval->actor?->jabatan ?: $roleLabel),
                'note' => 'Disetujui',
                'date' => $approval->acted_at?->format('d/m/Y'),
                'color' => '#4d7c0f',
                'background' => '#f7fee7',
            ];
        }

        return [
            'name' => $approval->assignedApprover?->name ?? $roleLabel,
            'title' => $approval->assignedApprover?->jabatan ?: $roleLabel,
            'note' => $approval->status === ApprovalStatus::Denied
                ? 'Ditolak'
                : ($approval->status === ApprovalStatus::Pending ? 'Menunggu approval' : 'Menunggu giliran'),
            'date' => $approval->acted_at?->format('d/m/Y'),
            'color' => $approval->status === ApprovalStatus::Denied ? '#b91c1c' : '#475569',
            'background' => $approval->status === ApprovalStatus::Denied ? '#fef2f2' : '#f8fafc',
        ];
    };

    $requesterSignature = [
        'name' => $requester->name,
        'title' => $requester->jabatan,
        'note' => $memo->submitted_at ? 'Diajukan' : 'Belum diajukan',
        'date' => $memo->submitted_at?->format('d/m/Y'),
        'color' => '#475569',
        'background' => '#f8fafc',
    ];
@endphp
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>
</head>
<body style="margin:0;padding:0;background-color:#f3f4f6;font-family:Arial,Helvetica,sans-serif;color:#111827;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f3f4f6;padding:24px 10px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:760px;background-color:#ffffff;border-collapse:collapse;">
                    <tr>
                        <td style="padding:24px 30px 8px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border-bottom:1px solid #111827;">
                                <tr>
                                    <td style="padding:0 0 10px;vertical-align:middle;">
                                        @if(isset($message) && is_file($logoPath))
                                            <img src="{{ $message->embed($logoPath) }}" width="145" alt="Tunas Auto Graha" style="display:block;width:145px;max-width:100%;height:auto;border:0;">
                                        @else
                                            <strong style="font-size:14px;">PT TUNAS AUTO GRAHA</strong>
                                        @endif
                                    </td>
                                    <td align="right" style="padding:0 0 10px;vertical-align:middle;font-size:13px;font-weight:bold;">Divisi {{ $memo->division->name }}</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="padding:14px 30px 20px;">
                            <div style="font-size:16px;font-weight:bold;text-decoration:underline;">INTERNAL MEMO</div>
                            <div style="margin-top:4px;font-size:14px;font-weight:bold;">No : {{ $memo->memo_number ?? 'DRAFT' }}</div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:0 30px 24px;font-size:13px;line-height:1.6;">
                            <p style="margin:0 0 16px;">
                                Kepada Yth,<br>
                                Direksi PT. Tunas Auto Graha<br>
                                @if($direksi)Bpk. {{ $direksi->name }}<br>@endif
                                Di. Tempat
                            </p>

                            <p style="margin:0 0 12px;">Hal : {{ $memo->subject }}</p>
                            <p style="margin:0 0 8px;">Dengan Hormat,</p>
                            <p style="margin:0 0 12px;text-align:justify;white-space:pre-line;">{{ $memo->opening_paragraph }}</p>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:14px 0;border-collapse:collapse;font-size:12px;">
                                <tr style="background-color:#eeeeee;">
                                    <th align="center" style="width:32px;padding:7px 5px;border:1px solid #111827;">No</th>
                                    <th align="center" style="padding:7px 6px;border:1px solid #111827;">Item</th>
                                    <th align="center" style="width:60px;padding:7px 5px;border:1px solid #111827;">Jumlah</th>
                                    <th align="center" style="width:115px;padding:7px 5px;border:1px solid #111827;">Harga</th>
                                    <th align="center" style="width:115px;padding:7px 5px;border:1px solid #111827;">Total</th>
                                </tr>
                                @foreach($memo->items as $item)
                                    <tr>
                                        <td align="center" style="padding:7px 5px;border:1px solid #111827;">{{ $loop->iteration }}</td>
                                        <td style="padding:7px 6px;border:1px solid #111827;">{{ $item->name }}</td>
                                        <td align="center" style="padding:7px 5px;border:1px solid #111827;">{{ $item->qty ?? '-' }}</td>
                                        <td align="right" style="padding:7px 5px;border:1px solid #111827;white-space:nowrap;">{{ Format::rupiah($item->unit_price) }}</td>
                                        <td align="right" style="padding:7px 5px;border:1px solid #111827;white-space:nowrap;">{{ Format::rupiah($item->subtotal) }}</td>
                                    </tr>
                                @endforeach
                                <tr>
                                    <td colspan="4" align="right" style="padding:7px 6px;border:1px solid #111827;"><strong>Total</strong></td>
                                    <td align="right" style="padding:7px 5px;border:1px solid #111827;white-space:nowrap;"><strong>{{ Format::rupiah($memo->total_amount) }}</strong></td>
                                </tr>
                                <tr>
                                    <td colspan="5" align="center" style="padding:8px 6px;border:1px solid #111827;background-color:#f5f5f5;"><strong>Terbilang: {{ Terbilang::rupiah($memo->total_amount) }}</strong></td>
                                </tr>
                            </table>

                            <p style="margin:14px 0;">Demikianlah surat pengajuan ini kami sampaikan, atas perhatian dan persetujuannya kami ucapkan terima kasih.</p>
                            <p style="margin:14px 0;text-align:right;">{{ $memo->branch->city }}, {{ Format::tanggal($memo->memo_date) }}</p>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top:16px;border-collapse:collapse;text-align:center;">
                                <tr>
                                    <td style="padding:5px 3px;font-size:12px;">Hormat saya,</td>
                                    @if($approvalSignatures->count() === 1)
                                        <td style="padding:5px 3px;font-size:12px;">Menyetujui,</td>
                                    @elseif($approvalSignatures->count() > 1)
                                        <td colspan="{{ $approvalSignatures->count() - 1 }}" style="padding:5px 3px;font-size:12px;">Mengetahui,</td>
                                            <td style="padding:5px 3px;font-size:12px;">Menyetujui,</td>
                                    @endif
                                </tr>
                                <tr>
                                    <td style="width:{{ 100 / ($approvalSignatures->count() + 1) }}%;padding:5px 3px;vertical-align:top;">
                                        <span style="display:block;min-height:28px;margin-bottom:5px;padding:5px 2px;border:1px solid {{ $requesterSignature['color'] }};background-color:{{ $requesterSignature['background'] }};color:{{ $requesterSignature['color'] }};font-size:10px;">
                                            {{ $requesterSignature['note'] }}
                                            @if($requesterSignature['date'])<br>{{ $requesterSignature['date'] }}@endif
                                        </span>
                                        <strong><u>{{ $requesterSignature['name'] }}</u></strong>
                                        @if($requesterSignature['title'])<br>{{ $requesterSignature['title'] }}@endif
                                    </td>
                                    @foreach($approvalSignatures as $approval)
                                        @php($signature = $approvalCell($approval))
                                        <td style="width:{{ 100 / ($approvalSignatures->count() + 1) }}%;padding:5px 3px;vertical-align:top;">
                                            <span style="display:block;min-height:28px;margin-bottom:5px;padding:5px 2px;border:1px solid {{ $signature['color'] }};background-color:{{ $signature['background'] }};color:{{ $signature['color'] }};font-size:10px;">
                                                {{ $signature['note'] }}
                                                @if($signature['date'])<br>{{ $signature['date'] }}@endif
                                            </span>
                                            <strong><u>{{ $signature['name'] }}</u></strong>
                                            @if($signature['title'])<br>{{ $signature['title'] }}@endif
                                        </td>
                                    @endforeach
                                </tr>
                            </table>

                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:28px 0 10px;">
                                <tr>
                                    <td align="center" style="border-radius:4px;background-color:#B3001B;">
                                        <a href="{{ $memoUrl }}" style="display:inline-block;padding:12px 22px;border:1px solid #B3001B;border-radius:4px;color:#ffffff;font-size:14px;font-weight:bold;text-decoration:none;">{{ $buttonLabel }}</a>
                                    </td>
                                </tr>
                            </table>
                            <p style="margin:0 0 18px;color:#6b7280;font-size:12px;line-height:1.5;word-break:break-all;">Jika tombol tidak dapat dibuka, salin tautan ini:<br>{{ $memoUrl }}</p>
                            <p style="margin:0;color:#6b7280;font-size:11px;line-height:1.6;">Email ini dikirim otomatis oleh sistem Internal Memo PT Tunas Auto Graha. Mohon tidak membalas email ini.<br>Detail rekening tidak disertakan dalam email ini demi keamanan.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
