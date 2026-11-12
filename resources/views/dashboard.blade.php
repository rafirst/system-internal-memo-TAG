@extends('layouts.app')
@section('title', 'Dashboard')

@push('styles')
<style>
    .dashboard-grid { display: grid; grid-template-columns: minmax(0, 1fr) 268px; gap: 16px; }
    .hero-showroom {
        position: relative;
        min-height: 184px;
        overflow: hidden;
        border: 1px solid rgba(220, 228, 239, .9);
        border-radius: 14px;
        background:
            linear-gradient(90deg, rgba(255, 255, 255, .96) 0%, rgba(255, 255, 255, .88) 35%, rgba(255, 255, 255, .28) 100%),
            url('{{ asset('assets/images/showroom-tag.png') }}') center 52% / cover no-repeat;
        box-shadow: 0 8px 24px rgba(34, 55, 85, .08);
    }
    .hero-showroom::before, .hero-showroom::after {
        position: absolute;
        z-index: 1;
        width: 76px;
        height: 76px;
        content: "";
        pointer-events: none;
    }
    .hero-showroom::before {
        top: 0;
        left: 0;
        background: linear-gradient(135deg, #a9001e 0 48%, transparent 49%);
    }
    .hero-showroom::after {
        right: 0;
        bottom: 0;
        background: linear-gradient(315deg, #b5122b 0 45%, transparent 46%);
    }
    .stat-card { transition: transform 160ms ease, box-shadow 160ms ease; }
    .stat-card:hover { transform: translateY(-2px); box-shadow: 0 12px 24px rgba(32, 52, 80, .1); }
    .stat-card::after {
        position: absolute;
        right: -13px;
        bottom: -28px;
        width: 72px;
        height: 84px;
        border-radius: 24px;
        background: var(--accent-soft);
        content: "";
        transform: rotate(38deg);
    }
    .dashboard-table th { background: #f1f5f9; color: #617086; font-size: 10px; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; }
    .dashboard-table td { border-top: 1px solid #edf1f6; }
    .activity-line { position: relative; }
    .activity-line:not(:last-child)::before {
        position: absolute;
        top: 30px;
        bottom: -10px;
        left: 13px;
        width: 1px;
        background: #e2e8f0;
        content: "";
    }
    @media (max-width: 1100px) {
        .dashboard-grid { grid-template-columns: minmax(0, 1fr) 240px; }
    }
    @media (max-width: 850px) {
        .dashboard-grid { grid-template-columns: 1fr; }
        .dashboard-side { display: grid; grid-template-columns: 1fr; }
    }
    @media (max-width: 600px) {
        .hero-showroom { min-height: 178px; background-position: 58% center; }
        .dashboard-table th:nth-child(3), .dashboard-table td:nth-child(3),
        .dashboard-table th:nth-child(4), .dashboard-table td:nth-child(4) { display: none; }
    }
</style>
@endpush

@section('content')
@php
    $user = auth()->user();
    $statCards = [
        ['status' => \App\Enums\MemoStatus::Pending, 'icon' => 'document', 'color' => '#e11d48', 'soft' => '#ffe4e9'],
        ['status' => \App\Enums\MemoStatus::Approved, 'icon' => 'shield', 'color' => '#2563eb', 'soft' => '#e4edff'],
        ['status' => \App\Enums\MemoStatus::Denied, 'icon' => 'close', 'color' => '#10a879', 'soft' => '#dcf7ed'],
        ['status' => \App\Enums\MemoStatus::Disbursed, 'icon' => 'clock', 'color' => '#d97706', 'soft' => '#fff0d8'],
    ];
    // Warna dan ikon bulatan per jenis kejadian pada kartu aktivitas.
    $activityStyles = [
        'submitted' => ['bg-blue-500', '<path stroke-linecap="round" stroke-linejoin="round" d="M4 12l16-8-6 16-3-5-7-3Z"/>'],
        'approved' => ['bg-emerald-500', '<path stroke-linecap="round" stroke-linejoin="round" d="m5 12 4 4L19 6"/>'],
        'denied' => ['bg-rose-500', '<path stroke-linecap="round" d="m8 8 8 8m0-8-8 8"/>'],
        'disbursed' => ['bg-amber-500', '<path stroke-linecap="round" stroke-linejoin="round" d="M4 8h16v9H4zM4 8l8-4 8 4M9 12.5h1.5"/>'],
        'completed' => ['bg-sky-500', '<path stroke-linecap="round" stroke-linejoin="round" d="M7 17V7l5 3 5-3v10"/>'],
        'drafted' => ['bg-slate-400', '<path stroke-linecap="round" stroke-linejoin="round" d="M7 3h7l5 5v13H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M14 3v5h5"/>'],
    ];
@endphp

<div class="space-y-4">
    <section class="hero-showroom flex items-center px-7 py-6 sm:px-10" aria-label="Selamat datang">
        <div class="relative z-[2] max-w-lg">
            <p class="mb-1.5 text-xs font-bold uppercase tracking-[.12em] text-rose-700">Selamat datang</p>
            <h1 class="text-3xl font-bold tracking-tight text-slate-800 sm:text-[34px]">{{ $user->name }}</h1>
            <p class="mt-2 max-w-md text-sm leading-relaxed text-slate-600">
                Kelola pengajuan, pantau, dan selesaikan persetujuan internal memo dengan mudah dan cepat.
            </p>
        </div>
    </section>

    <section class="grid grid-cols-2 gap-3 sm:grid-cols-4" aria-label="Ringkasan memo">
        @foreach($statCards as $card)
            @php($status = $card['status'])
            <a href="{{ route('memos.index', ['status' => $status->value]) }}"
               class="stat-card relative isolate overflow-hidden rounded-xl border border-slate-100 bg-white p-4 shadow-sm"
               style="--accent-soft: {{ $card['soft'] }}">
                <span class="relative z-[1] mb-3 flex h-10 w-10 items-center justify-center rounded-full" style="background: {{ $card['soft'] }}; color: {{ $card['color'] }}">
                    @if($card['icon'] === 'document')
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M7 3h7l5 5v13H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M14 3v5h5M9 13h6M9 17h6"/></svg>
                    @elseif($card['icon'] === 'shield')
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m12 3 8 3v5c0 5-3.4 8.5-8 10-4.6-1.5-8-5-8-10V6l8-3Z"/><path stroke-linecap="round" stroke-linejoin="round" d="m9 12 2 2 4-4"/></svg>
                    @elseif($card['icon'] === 'close')
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="m9 9 6 6m0-6-6 6"/></svg>
                    @else
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 7v5l3 2"/></svg>
                    @endif
                </span>
                <span class="relative z-[1] block text-2xl font-bold leading-none text-slate-800">{{ $counts[$status->value] ?? 0 }}</span>
                <span class="relative z-[1] mt-2 flex items-center justify-between gap-2 text-xs text-slate-500">
                    {{ $status->label() }}
                    <svg class="h-4 w-4 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m-6-6 6 6-6 6"/></svg>
                </span>
            </a>
        @endforeach
    </section>

    <div class="dashboard-grid">
        <div class="min-w-0">
            <section class="overflow-hidden rounded-xl border border-slate-100 bg-white shadow-sm">
                <div class="flex items-center justify-between gap-3 px-4 py-3.5 sm:px-5">
                    <h2 class="flex items-center gap-2 text-sm font-bold text-slate-800">
                        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-rose-50 text-rose-600">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M7 3h7l5 5v13H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M14 3v5h5M9 13h6M9 17h6"/></svg>
                        </span>
                        Memo Terbaru
                    </h2>
                    <a href="{{ route('memos.index') }}" class="whitespace-nowrap text-xs font-semibold text-rose-700 hover:text-rose-900">
                        Lihat Semua Memo <span aria-hidden="true">→</span>
                    </a>
                </div>
                <div class="overflow-x-auto">
                    <table class="dashboard-table w-full min-w-[600px] text-left text-xs">
                        <thead>
                            <tr>
                                <th class="px-4 py-2.5">No.</th>
                                <th class="px-3 py-2.5">Judul Memo</th>
                                <th class="px-3 py-2.5">Divisi</th>
                                <th class="px-3 py-2.5">Pengaju</th>
                                <th class="px-3 py-2.5">Tanggal</th>
                                <th class="px-3 py-2.5">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentMemos as $memo)
                                <tr class="transition hover:bg-slate-50">
                                    <td class="px-4 py-3 text-slate-500">{{ $loop->iteration }}</td>
                                    <td class="max-w-[220px] px-3 py-3">
                                        <a href="{{ route('memos.show', $memo) }}" class="block truncate font-semibold text-slate-700 hover:text-rose-700" title="{{ $memo->subject }}">
                                            {{ $memo->subject }}
                                        </a>
                                        <span class="mt-1 inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-[9px] font-semibold text-slate-600">{{ $memo->memo_kind === 'pameran' ? 'Pameran' : 'Pengajuan' }}</span>
                                        <span class="mt-1 block truncate text-[10px] text-slate-400">{{ $memo->memo_number ?? 'Draft' }}</span>
                                    </td>
                                    <td class="px-3 py-3 text-slate-600">{{ $memo->division?->name ?? '—' }}</td>
                                    <td class="px-3 py-3 text-slate-600">{{ $memo->submitter?->name ?? '—' }}</td>
                                    <td class="whitespace-nowrap px-3 py-3 text-slate-500">{{ $memo->submitted_at?->format('d M Y') ?? $memo->created_at?->format('d M Y') }}</td>
                                    <td class="px-3 py-3">
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-[10px] font-semibold {{ $memo->status->color() }}">{{ $memo->status->label() }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-10 text-center">
                                        <span class="mx-auto mb-2 flex h-10 w-10 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M7 3h7l5 5v13H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M14 3v5h5"/></svg>
                                        </span>
                                        <span class="block font-semibold text-slate-600">Belum ada memo</span>
                                        <span class="mt-1 block text-slate-400">Memo yang bisa Anda akses akan muncul di sini.</span>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <aside class="dashboard-side content-start gap-4">
            <section class="rounded-xl border border-slate-100 bg-white p-4 shadow-sm sm:p-5">
                <div class="mb-4 flex items-center justify-between gap-2">
                    <div class="flex items-center gap-2">
                        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-rose-50 text-rose-600">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 7v5l3 2"/></svg>
                        </span>
                        <h2 class="text-sm font-bold text-slate-800">{{ $activityHeading }}</h2>
                    </div>
                    <a href="{{ route('memos.index') }}" class="whitespace-nowrap text-[11px] font-semibold text-rose-700 hover:text-rose-900">
                        Semua <span aria-hidden="true">→</span>
                    </a>
                </div>

                <div>
                    @forelse($recentActivity as $activity)
                        @php($style = $activityStyles[$activity['type']] ?? $activityStyles['submitted'])
                        <a href="{{ $activity['url'] }}" class="activity-line flex gap-3 pb-4 last:pb-0">
                            <span class="relative z-[1] flex h-7 w-7 shrink-0 items-center justify-center rounded-full {{ $style[0] }} text-white">
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">{!! $style[1] !!}</svg>
                            </span>
                            <span class="min-w-0 pt-0.5">
                                <span class="block truncate text-xs font-semibold text-slate-700">{{ $activity['title'] }}</span>
                                <span class="mt-1 block text-[11px] leading-relaxed text-slate-500">{{ $activity['message'] }}</span>
                                <span class="mt-1 block text-[10px] text-slate-400">
                                    @if($activity['memo_number'])<span class="font-medium text-slate-500">{{ $activity['memo_number'] }}</span> <span class="text-slate-300">·</span>@endif
                                    {{ $activity['at']?->diffForHumans() }}
                                </span>
                            </span>
                        </a>
                    @empty
                        <div class="rounded-lg bg-slate-50 px-3 py-5 text-center">
                            <p class="text-xs font-semibold text-slate-600">Belum ada aktivitas</p>
                            <p class="mt-1 text-[11px] text-slate-400">Kejadian memo terbaru akan muncul di sini.</p>
                        </div>
                    @endforelse
                </div>
            </section>
        </aside>
    </div>
</div>
@endsection
