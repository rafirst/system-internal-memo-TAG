@php
    use App\Enums\ApprovalStatus;
    use App\Support\Format;
    use App\Support\Terbilang;

    $approvals = $memo->currentApprovals->sortBy('step_order')->values();
    $supervisorApproval = $approvals->first(fn ($approval) => $approval->approverRole?->slug === 'spv');
    $approvalSignatures = $approvals->reject(fn ($approval) => $approval->approverRole?->slug === 'spv')->values();
    $requester = $memo->onBehalfOf ?? $memo->submitter;

    $approvalLine = function ($approval) {
        $roleLabel = match ($approval->approverRole->slug) {
            'admin_keuangan' => 'Kepala Keuangan',
            'admin_finance' => 'ADH',
            default => $approval->approverRole->label,
        };
        $name = $approval->status === ApprovalStatus::Approved
            ? ($approval->actor?->name ?? $roleLabel)
            : $roleLabel;
        $title = $approval->status === ApprovalStatus::Approved
            ? (in_array($approval->approverRole->slug, ['admin_keuangan', 'admin_finance'], true)
                ? $roleLabel
                : ($approval->actor?->jabatan ?: $roleLabel))
            : '';
        $status = match ($approval->status) {
            ApprovalStatus::Approved => 'Disetujui',
            ApprovalStatus::Denied => 'Ditolak',
            ApprovalStatus::Pending => 'Menunggu approval',
            default => 'Menunggu giliran',
        };

        return trim($name.($title ? " — {$title}" : '').": {$status}".($approval->acted_at ? ' ('.$approval->acted_at->format('d/m/Y').')' : ''));
    };
@endphp
{{ $title }}

{{ $body }}

PT TUNAS AUTO GRAHA
Divisi {{ $memo->division->name }}

INTERNAL MEMO
No : {{ $memo->memo_number ?? 'DRAFT' }}

Kepada Yth,
Direksi PT. Tunas Auto Graha
@if($direksi)
Bpk. {{ $direksi->name }}
@endif
Di. Tempat

Hal : {{ $memo->subject }}

Dengan Hormat,

{{ $memo->opening_paragraph }}

No. | Item | Jumlah | Harga | Total
@foreach($memo->items as $item)
{{ $loop->iteration }} | {{ $item->name }} | {{ $item->qty ?? '-' }} | {{ Format::rupiah($item->unit_price) }} | {{ Format::rupiah($item->subtotal) }}
@endforeach
Total: {{ Format::rupiah($memo->total_amount) }}
Terbilang: {{ Terbilang::rupiah($memo->total_amount) }}

Demikianlah surat pengajuan ini kami sampaikan, atas perhatian dan persetujuannya kami ucapkan terima kasih.

{{ $memo->branch->city }}, {{ Format::tanggal($memo->memo_date) }}

Hormat saya:
@if($supervisorApproval)
{{ $approvalLine($supervisorApproval) }}
@else
{{ $requester->name }}{{ $requester->jabatan ? ' — '.$requester->jabatan : '' }}: {{ $memo->submitted_at ? 'Diajukan' : 'Belum diajukan' }}{{ $memo->submitted_at ? ' ('.$memo->submitted_at->format('d/m/Y').')' : '' }}
@endif
@foreach($approvalSignatures as $approval)
{{ $approvalLine($approval) }}
@endforeach

{{ $buttonLabel }}: {{ $memoUrl }}

Email ini dikirim otomatis oleh sistem Internal Memo PT Tunas Auto Graha. Mohon tidak membalas email ini.
Detail rekening tidak disertakan dalam email ini demi keamanan.
