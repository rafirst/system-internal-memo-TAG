@extends('layouts.app')
@section('title', $memo->memo_number ?? 'Draft Memo')

@push('styles')
<style>
    .memo-show-hero {
        position: relative;
        min-height: 104px;
        overflow: hidden;
        border: 1px solid rgba(220, 228, 239, .9);
        border-radius: 12px;
        background:
            linear-gradient(90deg, rgba(255, 255, 255, .97) 0%, rgba(255, 255, 255, .87) 42%, rgba(255, 255, 255, .16) 100%),
            url('{{ asset('assets/images/showroom-tag.png') }}') center 54% / cover no-repeat;
        box-shadow: 0 6px 18px rgba(34, 55, 85, .07);
    }
    .memo-show-hero::before,
    .memo-show-hero::after {
        position: absolute;
        z-index: 1;
        width: 34px;
        height: 34px;
        content: "";
        pointer-events: none;
    }
    .memo-show-hero::before {
        top: 0;
        left: 0;
        background: linear-gradient(135deg, #a9001e 0 48%, transparent 49%);
    }
    .memo-show-hero::after {
        right: 0;
        bottom: 0;
        background: linear-gradient(315deg, #b5122b 0 45%, transparent 46%);
    }
    .memo-detail-table th {
        background: #eef4fc;
        color: #64748b;
        font-size: 10px;
        font-weight: 700;
        letter-spacing: .04em;
        text-transform: uppercase;
    }
    .memo-detail-table td { border-top: 1px solid #e8eef6; }
    .memo-detail-table tfoot tr:last-child { background: #eef4fc; }
    .approval-step:not(:last-child)::before {
        position: absolute;
        top: 23px;
        bottom: -8px;
        left: 11px;
        width: 2px;
        background: #dce6f2;
        content: "";
    }
    @media (max-width: 600px) {
        .memo-show-hero {
            min-height: 112px;
            background-position: 60% center;
        }
    }
</style>
@endpush

@section('content')
@php
    use App\Enums\ApprovalStatus;
    use App\Enums\MemoStatus;
    use App\Support\Format;
    use App\Support\Terbilang;

    $currentApprovals = $memo->approvals->where('round', $memo->round);
    $isPameran = $memo->memo_kind === 'pameran';
    $pameran = $memo->pameran_details ?? [];
    $lastDenied = $memo->status === MemoStatus::Denied
        ? $currentApprovals->first(fn ($approval) => $approval->status === ApprovalStatus::Denied)
        : null;
@endphp

<div class="space-y-3">
    <section class="memo-show-hero flex items-center px-5 py-4 sm:px-7" aria-label="Detail memo internal">
        <div class="relative z-[2] max-w-lg">
            <p class="mb-0.5 text-[9px] font-bold uppercase tracking-[.14em] text-rose-700">Detail Memo</p>
            <h1 class="text-base font-bold leading-tight text-slate-800 sm:text-lg">Detail Memo Internal</h1>
            <p class="mt-1 max-w-md text-[9px] leading-relaxed text-slate-600 sm:text-[10px]">
            {{ $isPameran ? 'Memo ini berisi rencana kegiatan Pameran/Public Display beserta target dan estimasi biayanya.' : 'Memo ini berisi permohonan pembelian atau kebutuhan untuk mendukung kegiatan operasional.' }}
            </p>
        </div>
    </section>

    @if($lastDenied)
        <section class="rounded-lg border border-red-200 bg-red-50 px-3 py-2.5 text-xs text-red-800" role="alert">
            <strong>Ditolak oleh {{ $lastDenied->actor?->name }} ({{ $lastDenied->approverRole?->label }}):</strong>
            {{ $lastDenied->comment }}
        </section>
    @endif

    <div class="grid items-start gap-3 lg:grid-cols-[minmax(0,2fr)_minmax(270px,0.88fr)]">
        <div class="min-w-0 space-y-3">
            <section class="overflow-hidden rounded-xl border border-slate-100 bg-white shadow-sm" aria-label="Informasi memo">
                <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-2 px-3 py-2.5 sm:px-4">
                    <div class="flex min-w-0 items-center gap-2.5">
                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-md bg-rose-50 text-rose-700">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M7 3h7l5 5v13H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M14 3v5h5M9 13h6M9 17h6"/></svg>
                        </span>
                        <div class="min-w-0">
                            <p class="truncate text-[11px] font-bold text-slate-800">No. {{ $memo->memo_number ?? 'OTOMATIS' }}</p>
                            <p class="truncate text-[10px] text-slate-500">{{ $memo->subject }}</p>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-[10px] text-slate-500">{{ ($memo->submitted_at ?? $memo->created_at)?->format('d M Y') }}</span>
                    </div>
                </div>

                <div class="grid border-t border-slate-100 md:grid-cols-2">
                    <dl class="grid grid-cols-[100px_minmax(0,1fr)] gap-x-2 gap-y-2 px-3 py-2.5 text-[10px] sm:grid-cols-[104px_minmax(0,1fr)] sm:px-3.5">
                        <dt class="flex items-center gap-1.5 text-slate-500">
                            <svg class="h-3 w-3 shrink-0 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="8" r="3.5"/><path stroke-linecap="round" stroke-linejoin="round" d="M5 20a7 7 0 0 1 14 0"/></svg>
                            Pengaju
                        </dt>
                        <dd class="font-semibold text-slate-700">{{ $memo->submitter?->name ?? '—' }}</dd>

                        {{-- <dt class="flex items-center gap-1.5 text-slate-500">
                            <svg class="h-3 w-3 shrink-0 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="8" r="3.5"/><path stroke-linecap="round" stroke-linejoin="round" d="M5 20a7 7 0 0 1 14 0"/></svg>
                            Atas nama
                        </dt>
                        <dd class="font-medium text-slate-700">
                            {{ $memo->onBehalfOf?->name ?? '—' }}
                            @if($memo->onBehalfOf?->jabatan)
                                <span class="text-slate-500">({{ $memo->onBehalfOf->jabatan }})</span>
                            @endif
                        </dd> --}}

                        <dt class="flex items-center gap-1.5 text-slate-500">
                            <svg class="h-3 w-3 shrink-0 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-5h6v5M8 10h.01M16 10h.01"/></svg>
                            Divisi / Cabang
                        </dt>
                        <dd class="font-medium text-slate-700">{{ $memo->division?->name ?? '—' }} / {{ $memo->branch?->name ?? '—' }}</dd>

                        <dt class="flex items-center gap-1.5 text-slate-500">
                            <svg class="h-3 w-3 shrink-0 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"/><path stroke-linecap="round" stroke-linejoin="round" d="M16 3v4M8 3v4M3 10h18"/></svg>
                            Tanggal memo
                        </dt>
                        <dd class="font-medium text-slate-700">{{ Format::tanggal($memo->memo_date) }}</dd>

                        <dt class="flex items-center gap-1.5 text-slate-500">
                            <svg class="h-3 w-3 shrink-0 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h3"/></svg>
                            Pembayaran
                        </dt>
                        <dd class="break-words font-medium text-slate-700">
                            {{ $memo->bank_name ? $memo->bank_name . ' ' . $memo->account_number . ' a.n. ' . $memo->account_holder : '—' }}
                        </dd>
                    </dl>

                    <div class="border-t border-slate-100 p-2 md:border-l md:border-t-0">
                        <div class="h-full rounded-lg border border-rose-100 bg-rose-50/70 p-2.5">
                            <p class="mb-1.5 flex items-center gap-1.5 text-[10px] font-bold text-slate-800">
                                <svg class="h-3.5 w-3.5 text-rose-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h8m-8 4h5"/></svg>
                                Dengan Hormat,
                            </p>
                            <p class="whitespace-pre-line text-[10px] leading-relaxed text-slate-600">{{ preg_replace('/^\s*Dengan\s+Hormat,?\s*/iu', '', $memo->opening_paragraph ?? '') }}</p>
                        </div>
                    </div>
                </div>
            </section>

            <section class="overflow-hidden rounded-xl border border-slate-100 bg-white shadow-sm" aria-label="{{ $isPameran ? 'Detail Pameran' : 'Rincian pengajuan' }}">
                <div class="flex items-center gap-2 px-3 py-2 sm:px-4">
                    <span class="flex h-6 w-6 items-center justify-center rounded-md bg-rose-50 text-rose-700">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M7 3h7l5 5v13H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M14 3v5h5M9 13h6M9 17h6"/></svg>
                    </span>
                    <h2 class="text-xs font-bold text-slate-800">{{ $isPameran ? 'Detail Pameran' : 'Rincian Pengajuan' }}</h2>
                </div>
                @if($isPameran)
                    <div class="overflow-x-auto px-3 pb-3 sm:px-4">
                        <table class="memo-detail-table w-full min-w-[560px] text-left text-xs">
                            <thead>
                                <tr><th>Item</th><th>Keterangan</th><th>Ketentuan</th></tr>
                            </thead>
                            <tbody>
                                <tr><td class="px-3 py-2 font-medium">Lokasi</td><td colspan="2" class="px-3 py-2">{{ $pameran['location'] }}</td></tr>
                                <tr>
                                    <td class="px-3 py-2 font-medium">Periode</td>
                                    <td class="px-3 py-2">{{ Format::tanggal(\Illuminate\Support\Carbon::parse($pameran['event_date'])) }}</td>
                                    <td class="px-3 py-2">{{ $pameran['duration'] }}</td>
                                </tr>
                                <tr><td class="px-3 py-2 font-medium">Unit Display</td><td colspan="2" class="px-3 py-2">{{ $pameran['display_unit'] }}</td></tr>
                                <tr><td class="px-3 py-2 font-medium">SP Tools</td><td colspan="2" class="px-3 py-2">{{ $pameran['sp_tools'] }}</td></tr>
                                <tr>
                                    <td class="px-3 py-2 font-medium">PIC</td>
                                    <td colspan="2" class="whitespace-pre-line px-3 py-2">
                                        PIC Unit Display: {{ $pameran['display_pic'] }}<br>
                                        Loading in: {{ $pameran['loading_in'] }}, {{ $pameran['loading_in_time'] }}<br>
                                        Loading out: {{ $pameran['loading_out'] }}, {{ $pameran['loading_out_time'] }}<br>
                                        PIC SP Tools Pameran: {{ $pameran['sp_tools_pic'] }}<br>
                                        PIC Kebersihan Unit: {{ $pameran['cleaning_pic'] }}
                                    </td>
                                </tr>
                                <tr><td class="px-3 py-2 font-medium">Program Marketing</td><td colspan="2" class="whitespace-pre-line px-3 py-2">{{ $pameran['marketing_programs'] }}</td></tr>
                                <tr>
                                    <td class="px-3 py-2 font-medium">Biaya</td>
                                    <td colspan="2" class="px-3 py-2">{{ Format::rupiah($pameran['budget_min']) }} - {{ Format::rupiah($pameran['budget_max']) }}</td>
                                </tr>
                                <tr><td class="px-3 py-2 font-medium">Target</td><td colspan="2" class="whitespace-pre-line px-3 py-2">Prospect: {{ $pameran['prospect_target'] }} data<br>SPK: {{ $pameran['spk_target'] }} SPK</td></tr>
                            </tbody>
                        </table>
                        @if(! empty($pameran['note'] ?? null))
                            <p class="mt-2 whitespace-pre-line text-xs text-slate-600"><strong>Note:</strong> {{ $pameran['note'] }}</p>
                        @endif
                        <p class="mt-2 text-[10px] text-slate-500">Nilai maksimum {{ Format::rupiah($memo->total_amount) }} digunakan sebagai total untuk approval dan pencairan.</p>
                    </div>
                @else
                <div class="overflow-x-auto">
                    <table class="memo-detail-table w-full min-w-[560px] text-left text-xs">
                        <thead>
                            <tr>
                                <th class="w-12 px-3 py-2">No.</th>
                                <th class="px-3 py-2">Item</th>
                                <th class="px-3 py-2 text-right">Jumlah</th>
                                <th class="px-3 py-2 text-right">Harga Satuan</th>
                                <th class="px-3 py-2 text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($memo->items as $item)
                                <tr class="hover:bg-slate-50">
                                    <td class="px-3 py-2 text-slate-500">{{ $loop->iteration }}</td>
                                    <td class="px-3 py-2 font-medium text-slate-700">{{ $item->name }}</td>
                                    <td class="px-3 py-2 text-right text-slate-600">{{ $item->qty ?? '—' }}</td>
                                    <td class="whitespace-nowrap px-3 py-2 text-right text-slate-600">{{ Format::rupiah($item->unit_price) }}</td>
                                    <td class="whitespace-nowrap px-3 py-2 text-right font-medium text-slate-700">{{ Format::rupiah($item->subtotal) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-3 py-8 text-center text-slate-400">Belum ada rincian item.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot class="border-t border-slate-200">
                            <tr>
                                <td colspan="4" class="px-3 py-3 text-right font-bold text-slate-700">Total</td>
                                <td class="whitespace-nowrap px-3 py-3 text-right font-bold text-slate-800">{{ Format::rupiah($memo->total_amount) }}</td>
                            </tr>
                            <tr>
                                <td colspan="5" class="px-3 py-2.5 text-center text-[10px] font-semibold text-slate-600">
                                    <span class="inline-flex items-center gap-1.5">
                                        <svg class="h-3.5 w-3.5 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M4 12h10M4 17h7"/><circle cx="18" cy="16" r="3"/><path stroke-linecap="round" d="m17 16 1 1 2-2"/></svg>
                                        Terbilang: {{ Terbilang::rupiah($memo->total_amount) }}
                                    </span>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                @endif
            </section>

            @foreach(['supporting' => 'Lampiran Pendukung', 'realization' => 'Bukti Realisasi'] as $type => $title)
                @php
                    $files = $memo->attachments->where('type', $type);
                @endphp
                @if($files->isNotEmpty())
                    <section class="rounded-xl border border-slate-100 bg-white p-3 shadow-sm sm:p-4" aria-label="{{ $title }}">
                        <div class="mb-2.5 flex items-center gap-2">
                            <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-rose-50 text-rose-700">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12.5 14.5 6a4 4 0 0 1 5.7 5.7l-8.5 8.5a6 6 0 0 1-8.5-8.5l8.5-8.5"/></svg>
                            </span>
                            <h2 class="text-xs font-bold text-slate-800">{{ $title }}</h2>
                        </div>
                        <ul class="space-y-2">
                            @foreach($files as $attachment)
                                <li class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-slate-100 px-2.5 py-2">
                                    <div class="flex min-w-0 items-center gap-2">
                                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-md bg-rose-50 text-rose-600">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M7 3h7l5 5v13H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M14 3v5h5M9 14h6M9 17h4"/></svg>
                                        </span>
                                        <span class="min-w-0">
                                            <span class="block break-all text-[11px] font-semibold text-slate-700">{{ $attachment->original_name }}</span>
                                            <span class="mt-0.5 block text-[9px] text-slate-400">{{ $attachment->uploader?->name ?? '—' }} · {{ $attachment->created_at->format('d/m/Y H:i') }}</span>
                                        </span>
                                    </div>
                                    <div class="ml-auto flex shrink-0 items-center gap-1.5">
                                        @if(in_array(strtolower(pathinfo($attachment->original_name, PATHINFO_EXTENSION)), ['pdf', 'jpg', 'jpeg', 'png'], true))
                                            <a href="{{ route('memos.attachments.preview', [$memo, $attachment]) }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 rounded-md border border-slate-200 px-2.5 py-1.5 text-[9px] font-semibold text-slate-600 transition hover:border-rose-200 hover:bg-rose-50 hover:text-rose-700">
                                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.5 12s3.4-6 9.5-6 9.5 6 9.5 6-3.4 6-9.5 6-9.5-6-9.5-6Z"/><circle cx="12" cy="12" r="2.5"/></svg>
                                                Preview
                                            </a>
                                        @endif
                                        <a href="{{ route('memos.attachments.download', [$memo, $attachment]) }}" class="inline-flex items-center gap-1.5 rounded-md border border-slate-200 px-2.5 py-1.5 text-[9px] font-semibold text-slate-600 transition hover:border-rose-200 hover:bg-rose-50 hover:text-rose-700">
                                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v12m0 0 4-4m-4 4-4-4M5 17v3h14v-3"/></svg>
                                            Unduh
                                        </a>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endif
            @endforeach
        </div>

        <aside class="min-w-0 space-y-3">
            <section class="rounded-xl border border-slate-100 bg-white p-3 shadow-sm" aria-label="Status memo">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="flex items-center gap-2 text-xs font-bold text-slate-800">
                        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-rose-50 text-rose-700">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 7v5l3 2"/></svg>
                        </span>
                        Status Memo
                    </h2>
                    <x-status-badge :status="$memo->status" class="px-2 py-0.5 text-[9px]" />
                </div>
                @if($memo->status === MemoStatus::Disbursed && $memo->disbursed_at)
                    <p class="mt-2 text-[10px] text-slate-500">Dicairkan {{ $memo->disbursed_at->format('d/m/Y H:i') }}</p>
                @elseif($memo->status === MemoStatus::Completed && $memo->completed_at)
                    <p class="mt-2 text-[10px] text-slate-500">Selesai {{ $memo->completed_at->format('d/m/Y H:i') }}</p>
                @endif
            </section>

            <section class="rounded-xl border border-slate-100 bg-white p-3 shadow-sm" aria-label="Riwayat persetujuan">
                <h2 class="mb-3 flex items-center gap-2 text-xs font-bold text-slate-800">
                    <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-rose-50 text-rose-700">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 5h14M5 12h14M5 19h14"/><circle cx="8" cy="5" r="1.5" fill="currentColor"/><circle cx="16" cy="12" r="1.5" fill="currentColor"/><circle cx="10" cy="19" r="1.5" fill="currentColor"/></svg>
                    </span>
                    Riwayat Persetujuan
                </h2>

                @forelse($memo->approvals->groupBy('round')->sortKeysDesc() as $round => $rows)
                    <div class="mb-3 last:mb-0">
                        <p class="mb-2 text-[9px] font-semibold uppercase tracking-wide text-slate-400">
                            Pengajuan {{ $round }}{{ $round == $memo->round ? ' (terkini)' : '' }}
                        </p>
                        <ol class="space-y-2">
                            @foreach($rows as $approval)
                                @php
                                    $approvalColor = match ($approval->status) {
                                        ApprovalStatus::Approved => 'bg-emerald-500',
                                        ApprovalStatus::Denied => 'bg-rose-500',
                                        default => $approval->status === ApprovalStatus::Pending ? 'bg-amber-400' : 'bg-slate-300',
                                    };
                                @endphp
                                <li class="approval-step relative flex gap-2.5">
                                    <span class="relative z-[1] mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full {{ $approvalColor }} text-white">
                                        @if($approval->status === ApprovalStatus::Approved)
                                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m5 12 4 4L19 6"/></svg>
                                        @elseif($approval->status === ApprovalStatus::Denied)
                                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" d="m7 7 10 10M17 7 7 17"/></svg>
                                        @else
                                            <span class="text-[10px] font-bold">{{ $approval->step_order }}</span>
                                        @endif
                                    </span>
                                    <div class="min-w-0 flex-1 pb-1">
                                        <div class="flex flex-wrap items-center justify-between gap-x-2 gap-y-1">
                                            <p class="text-[10px] font-semibold text-slate-700">
                                                {{ $approval->approverRole?->label ?? 'Approver' }}
                                                @if($approval->assignedApprover)
                                                    <span class="font-normal text-slate-500">· {{ $approval->assignedApprover->name }}</span>
                                                @endif
                                            </p>
                                            <x-status-badge :status="$approval->status" class="text-[9px] px-2 py-0.5" />
                                        </div>
                                        @if($approval->actor)
                                            <p class="mt-0.5 text-[9px] text-slate-500">{{ $approval->actor->name }} · {{ $approval->acted_at?->format('d/m/Y') }}</p>
                                        @endif
                                        @if($approval->comment)
                                            <p class="mt-0.5 text-[9px] italic leading-relaxed text-slate-500">“{{ $approval->comment }}”</p>
                                        @endif
                                    </div>
                                </li>
                            @endforeach
                        </ol>
                    </div>
                @empty
                    <p class="rounded-lg bg-slate-50 px-3 py-4 text-center text-xs text-slate-400">Memo belum diajukan.</p>
                @endforelse
            </section>

            <section class="rounded-xl border border-slate-100 bg-white p-3 shadow-sm" aria-label="Preview Memo">
                <h2 class="mb-2 flex items-center gap-2 text-xs font-bold text-slate-800">
                    <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-rose-50 text-rose-700">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.5 12s3.4-6 9.5-6 9.5 6 9.5 6-3.4 6-9.5 6-9.5-6-9.5-6Z"/><circle cx="12" cy="12" r="2.5"/></svg>
                    </span>
                    Preview Memo
                </h2>
                <div class="space-y-2">
                    @can('update', $memo)
                        <a href="{{ route('memos.edit', $memo) }}" aria-label="Edit memo" class="flex w-full items-center justify-center gap-2 rounded-md border border-slate-300 bg-white px-3 py-2 text-[11px] font-semibold text-slate-700 transition hover:border-rose-300 hover:bg-rose-50 hover:text-rose-700">
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m16 5 3 3M4 20l4.5-1 10-10a2.1 2.1 0 0 0-3-3l-10 10L4 20Z"/></svg>
                            Edit Memo
                        </a>
                    @endcan

                    <a href="{{ route('memos.pdf', $memo) }}" target="_blank" rel="noopener" class="flex w-full items-center justify-center gap-2 rounded-md border border-slate-200 px-3 py-2 text-[11px] font-semibold text-slate-700 transition hover:border-rose-200 hover:bg-rose-50 hover:text-rose-700">
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.5 12s3.4-6 9.5-6 9.5 6 9.5 6-3.4 6-9.5 6-9.5-6-9.5-6Z"/><circle cx="12" cy="12" r="2.5"/></svg>
                        Preview Memo
                    </a>
                    <a href="{{ route('memos.pdf.download', $memo) }}" class="flex w-full items-center justify-center gap-2 rounded-md border border-slate-200 px-3 py-2 text-[11px] font-semibold text-slate-700 transition hover:border-rose-200 hover:bg-rose-50 hover:text-rose-700">
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v12m0 0 4-4m-4 4-4-4M5 17v3h14v-3"/></svg>
                        Unduh PDF
                    </a>

                    @can('delete', $memo)
                        <form method="POST" action="{{ route('memos.destroy', $memo) }}" onsubmit="return confirm('Hapus memo ini secara permanen?')">@csrf @method('DELETE')
                            <button class="w-full rounded-lg border border-red-200 px-3 py-2 text-xs font-semibold text-red-600 transition hover:bg-red-50">Hapus Memo</button>
                        </form>
                    @endcan
                </div>

                @can('act', $memo)
                    <div class="mt-4 border-t border-slate-100 pt-4" x-data="{ deny: false }">
                        <h3 class="mb-2 text-xs font-bold text-slate-700">Keputusan Anda · {{ auth()->user()->role?->label }}</h3>
                        <form method="POST" action="{{ route('memos.approve', $memo) }}" x-show="!deny">@csrf
                            <label class="sr-only" for="approval-comment">Komentar persetujuan</label>
                            <input id="approval-comment" type="text" name="comment" placeholder="Komentar (opsional)" class="mb-2 w-full rounded-lg border border-slate-200 px-3 py-2 text-xs outline-none focus:border-rose-400 focus:ring-2 focus:ring-rose-100">
                            <div class="flex gap-2">
                                <button class="flex-1 rounded-lg bg-emerald-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-emerald-700" onclick="return confirm('Setujui memo ini?')">Setujui</button>
                                <button type="button" @click="deny = true" class="rounded-lg border border-red-200 px-3 py-2 text-xs font-semibold text-red-600 transition hover:bg-red-50">Tolak</button>
                            </div>
                        </form>
                        <form method="POST" action="{{ route('memos.deny', $memo) }}" x-show="deny" x-cloak>@csrf
                            <label class="sr-only" for="denial-comment">Alasan penolakan</label>
                            <textarea id="denial-comment" name="comment" rows="3" required placeholder="Alasan penolakan (wajib)" class="mb-2 w-full rounded-lg border border-slate-200 px-3 py-2 text-xs outline-none focus:border-rose-400 focus:ring-2 focus:ring-rose-100"></textarea>
                            <div class="flex gap-2">
                                <button class="flex-1 rounded-lg bg-red-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-red-700">Kirim Penolakan</button>
                                <button type="button" @click="deny = false" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-600">Batal</button>
                            </div>
                        </form>
                    </div>
                @endcan

                @can('disburse', $memo)
                    <div class="mt-4 border-t border-slate-100 pt-4">
                        <p class="mb-3 text-[11px] leading-relaxed text-slate-600">
                            Transfer ke <strong>{{ $memo->bank_name }} {{ $memo->account_number }}</strong>
                            a.n. <strong>{{ $memo->account_holder }}</strong> sebesar
                            <strong>{{ Format::rupiah($memo->total_amount) }}</strong>.
                        </p>
                        <form method="POST" action="{{ route('memos.disburse', $memo) }}" onsubmit="return confirm('Tandai dana sudah dicairkan?')">@csrf
                            <button class="w-full rounded-lg bg-emerald-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-emerald-700">Tandai Dana Dicairkan</button>
                        </form>
                    </div>
                @endcan

                @can('uploadRealization', $memo)
                    <div class="mt-4 border-t border-slate-100 pt-4">
                        <h3 class="mb-1 text-xs font-bold text-slate-700">Unggah Bukti Realisasi</h3>
                        <p class="mb-3 text-[10px] leading-relaxed text-slate-500">Unggah invoice atau foto barang/tools yang dibeli untuk menyelesaikan memo.</p>
                        <form method="POST" action="{{ route('memos.realization', $memo) }}" enctype="multipart/form-data" class="space-y-2">@csrf
                            <input type="file" name="files[]" multiple required class="w-full text-[10px] text-slate-600" accept=".pdf,.jpg,.jpeg,.png">
                            <button class="w-full rounded-lg bg-blue-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-blue-700">Unggah &amp; Selesaikan</button>
                        </form>
                    </div>
                @endcan
            </section>
        </aside>
    </div>
</div>
@endsection
