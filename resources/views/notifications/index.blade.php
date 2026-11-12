@extends('layouts.app')
@section('title', 'Notifikasi')

@push('styles')
    <style>
        .notification-hero {
            position: relative;
            min-height: 132px;
            overflow: hidden;
            border: 1px solid #dbe6f2;
            border-radius: 14px;
            background: #eaf2fb;
        }
        .notification-hero::before {
            position: absolute;
            inset: 0;
            background: linear-gradient(90deg, rgba(255,255,255,.98) 0%, rgba(255,255,255,.93) 40%, rgba(255,255,255,.18) 100%), url('{{ asset('assets/images/showroom-tag.png') }}') center 54% / cover;
            content: '';
        }
        .notification-hero::after {
            position: absolute;
            right: 0;
            bottom: 0;
            width: 7px;
            height: 100%;
            background: #bd102b;
            content: '';
        }
        .notification-hero-content { position: relative; z-index: 1; padding: 21px 24px; }
        .notification-layout {
            display: grid;
            grid-template-columns: minmax(0, 1.8fr) minmax(245px, .78fr);
            gap: 16px;
            align-items: start;
        }
        .notification-tabs { display: flex; gap: 8px; overflow-x: auto; padding: 1px 0 5px; }
        .notification-tab {
            display: inline-flex;
            flex: none;
            align-items: center;
            gap: 7px;
            min-height: 38px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            background: #fff;
            padding: 0 11px;
            color: #475569;
            font-size: 11px;
            font-weight: 700;
            box-shadow: 0 2px 5px rgba(15,23,42,.03);
            transition: all 150ms ease;
        }
        .notification-tab:hover { border-color: #fda4af; color: #a4001e; }
        .notification-tab.is-active { border-color: #b5122b; background: #b5122b; color: #fff; }
        .notification-tab-count {
            display: inline-flex;
            min-width: 19px;
            height: 19px;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            background: #f1f5f9;
            padding: 0 5px;
            color: #64748b;
            font-size: 10px;
        }
        .notification-tab.is-active .notification-tab-count { background: #fff; color: #a4001e; }
        .notification-card {
            display: grid;
            grid-template-columns: 42px minmax(0, 1fr) auto 14px;
            gap: 11px;
            align-items: center;
            border: 1px solid #e2eaf3;
            border-left: 3px solid transparent;
            border-radius: 9px;
            background: #fff;
            padding: 11px 12px;
            color: inherit;
            box-shadow: 0 2px 7px rgba(15,23,42,.025);
            transition: border-color 150ms ease, background-color 150ms ease, transform 150ms ease;
        }
        .notification-card:hover { transform: translateY(-1px); border-color: #cbd8e7; }
        .notification-card.is-unread { border-left-color: #dc1837; background: linear-gradient(100deg, #fff7f7, #fff 82%); }
        .notification-icon {
            display: flex;
            width: 40px;
            height: 40px;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
        }
        .notification-icon svg { width: 19px; height: 19px; }
        .notification-icon--updated { background: #e8f0ff; color: #2563eb; }
        .notification-icon--approval { background: #e4f7ee; color: #059669; }
        .notification-icon--denied { background: #fff0f1; color: #dc2626; }
        .notification-icon--default { background: #edf2f8; color: #475569; }
        .notification-message { display: -webkit-box; overflow: hidden; -webkit-box-orient: vertical; -webkit-line-clamp: 2; }
        .notification-promo {
            position: relative;
            min-height: 145px;
            overflow: hidden;
            border-radius: 10px;
            background: #1e293b;
        }
        .notification-promo img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; }
        .notification-promo::after { position: absolute; inset: 35% 0 0; background: linear-gradient(transparent, rgba(10,20,38,.9)); content: ''; }
        .notification-promo-copy { position: absolute; z-index: 1; right: 13px; bottom: 13px; left: 13px; color: #fff; }
        .notification-promo-copy::before { display: block; width: 27px; height: 2px; margin-bottom: 7px; background: #e11d48; content: ''; }
        @media (max-width: 900px) {
            .notification-layout { grid-template-columns: minmax(0, 1fr) 250px; }
        }
        @media (max-width: 720px) {
            .notification-layout { grid-template-columns: minmax(0, 1fr); }
            .notification-sidebar { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; }
            .notification-summary { grid-column: 1 / -1; }
        }
        @media (max-width: 480px) {
            .notification-hero-content { padding: 18px; }
            .notification-card { grid-template-columns: 36px minmax(0, 1fr) 12px; gap: 9px; padding: 10px; }
            .notification-icon { width: 35px; height: 35px; }
            .notification-card-time { display: none; }
            .notification-sidebar { grid-template-columns: minmax(0, 1fr); }
            .notification-summary { grid-column: auto; }
        }
    </style>
@endpush

@section('content')
    @php
        $categoryIcons = [
            'all' => '<circle cx="12" cy="12" r="9"/><path d="M8 12h8m-4-4v8"/>',
            'updated' => '<path d="M12 8v4l3 2"/><circle cx="12" cy="12" r="9"/>',
            'approval' => '<path d="m7 12 3 3 7-7"/><circle cx="12" cy="12" r="9"/>',
            'denied' => '<path d="m9 9 6 6m0-6-6 6"/><circle cx="12" cy="12" r="9"/>',
        ];
        $categoryStyles = [
            'all' => 'notification-icon--default',
            'updated' => 'notification-icon--updated',
            'approval' => 'notification-icon--approval',
            'denied' => 'notification-icon--denied',
        ];
    @endphp

    <div class="content-shell space-y-3.5">
        <section class="notification-hero shadow-sm">
            <div class="notification-hero-content">
                <p class="mb-1 text-[10px] font-bold uppercase tracking-[.16em] text-rose-700">Notifikasi</p>
                <h1 class="text-2xl font-bold tracking-tight text-slate-800">Pemberitahuan Terbaru</h1>
                <p class="mt-1 max-w-lg text-xs leading-relaxed text-slate-600">
                    Pantau semua notifikasi terkait memo, pengajuan,<br class="hidden sm:block"> dan aktivitas sistem lainnya.
                </p>
            </div>
        </section>

        <div class="notification-layout">
            <section class="min-w-0">
                <div class="mb-2 flex items-center justify-between gap-3">
                    <nav class="notification-tabs" aria-label="Filter kategori notifikasi">
                        @foreach($categories as $key => $category)
                            <a href="{{ route('notifications.index', ['category' => $key]) }}"
                               class="notification-tab {{ $activeCategory === $key ? 'is-active' : '' }}"
                               @if($activeCategory === $key) aria-current="page" @endif>
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    {!! $categoryIcons[$key] !!}
                                </svg>
                                {{ $category['label'] }}
                                <span class="notification-tab-count">{{ $category['count'] }}</span>
                            </a>
                        @endforeach
                    </nav>
                    @if(!$isActivityFeed && $unreadNotifications > 0)
                        <form method="POST" action="{{ route('notifications.readAll') }}" class="hidden shrink-0 sm:block">
                            @csrf
                            <button class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-[10px] font-semibold text-slate-600 shadow-sm transition hover:border-rose-200 hover:text-rose-700">
                                Tandai semua dibaca
                            </button>
                        </form>
                    @endif
                </div>

                <div class="space-y-2">
                    @forelse($notifications as $notification)
                        @php
                            $title = data_get($notification->data, 'title', 'Notifikasi');
                            $isUnread = !$isActivityFeed && !$notification->read_at;
                            $category = str_contains(mb_strtolower($title), 'ditolak')
                                ? 'denied'
                                : (str_contains(mb_strtolower($title), 'persetujuan') || str_contains(mb_strtolower($title), 'disetujui')
                                    ? 'approval'
                                    : (str_contains(mb_strtolower($title), 'diperbarui') || str_contains(mb_strtolower($title), 'dicairkan') || str_contains(mb_strtolower($title), 'diunggah')
                                        ? 'updated'
                                        : 'all'));
                        @endphp
                        <a href="{{ $isActivityFeed ? data_get($notification->data, 'url', route('dashboard')) : route('notifications.open', $notification->id) }}"
                           class="notification-card {{ $isUnread ? 'is-unread' : '' }}">
                            <span class="notification-icon {{ $categoryStyles[$category] }}">
                                @if($category === 'denied')
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path stroke-linecap="round" d="m8 8 8 8m0-8-8 8"/><circle cx="12" cy="12" r="9"/></svg>
                                @elseif($category === 'approval')
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m7 12 3 3 7-7"/><circle cx="12" cy="12" r="9"/></svg>
                                @elseif($category === 'updated')
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 2"/><circle cx="12" cy="12" r="9"/></svg>
                                @else
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8 10h8m-8 4h5m-8 7 3-3h10a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v16Z"/></svg>
                                @endif
                            </span>

                            <span class="min-w-0">
                                <span class="mb-0.5 flex items-center gap-2">
                                    <span class="truncate text-[11px] font-bold text-slate-800">{{ $title }}</span>
                                    @if($isUnread)
                                        <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-rose-600" aria-label="Belum dibaca"></span>
                                    @endif
                                </span>
                                <span class="notification-message text-[10px] leading-relaxed text-slate-600">{{ data_get($notification->data, 'message', '') }}</span>
                                <span class="mt-1 block truncate text-[9px] font-medium text-slate-500">
                                    {{ $category === 'denied' ? 'Ditolak' : ($category === 'approval' ? 'Persetujuan' : ($category === 'updated' ? 'Diperbarui' : 'Aktivitas')) }}
                                    @if(data_get($notification->data, 'memo_number'))
                                        <span class="mx-1 text-slate-300">·</span>{{ data_get($notification->data, 'memo_number') }}
                                    @endif
                                </span>
                            </span>

                            <span class="notification-card-time whitespace-nowrap text-[9px] text-slate-400">{{ $notification->created_at->diffForHumans() }}</span>
                            <svg class="h-3.5 w-3.5 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m9 18 6-6-6-6"/></svg>
                        </a>
                    @empty
                        <div class="rounded-xl border border-dashed border-slate-300 bg-white px-5 py-12 text-center">
                            <span class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-500">
                                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></svg>
                            </span>
                            <p class="text-sm font-semibold text-slate-700">Belum ada pemberitahuan</p>
                            <p class="mt-1 text-xs text-slate-500">Notifikasi baru akan muncul di sini.</p>
                        </div>
                    @endforelse
                </div>

                @php
                    $firstVisiblePage = min($notifications->currentPage(), max($notifications->lastPage() - 1, 1));
                    $visiblePages = range($firstVisiblePage, min($firstVisiblePage + 1, $notifications->lastPage()));
                @endphp
                <div class="mt-3 flex justify-end">
                    <nav aria-label="Pagination notifikasi" class="flex items-center gap-1 rounded-lg border border-slate-200 bg-white p-1 shadow-sm">
                    @if($notifications->previousPageUrl())
                        <a href="{{ $notifications->previousPageUrl() }}"
                           aria-label="Halaman sebelumnya"
                           class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 text-slate-500 transition hover:border-rose-200 hover:bg-rose-50 hover:text-rose-700">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path d="m15 18-6-6 6-6" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </a>
                    @else
                        <span aria-label="Halaman sebelumnya" aria-disabled="true" class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-100 text-slate-300">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path d="m15 18-6-6 6-6" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </span>
                    @endif

                    @foreach($visiblePages as $page)
                        @if($page === $notifications->currentPage())
                            <span aria-current="page" aria-label="Halaman {{ $page }}" class="flex h-9 min-w-9 items-center justify-center rounded-lg bg-rose-700 px-2 text-xs font-semibold text-white">
                                {{ $page }}
                            </span>
                        @else
                            <a href="{{ $notifications->url($page) }}"
                               aria-label="Halaman {{ $page }}"
                               class="flex h-9 min-w-9 items-center justify-center rounded-lg px-2 text-xs font-semibold text-slate-600 transition hover:bg-rose-50 hover:text-rose-700">
                                {{ $page }}
                            </a>
                        @endif
                    @endforeach

                    @if($notifications->nextPageUrl())
                        <a href="{{ $notifications->nextPageUrl() }}"
                           aria-label="Halaman berikutnya"
                           class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 text-slate-500 transition hover:border-rose-200 hover:bg-rose-50 hover:text-rose-700">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path d="m9 18 6-6-6-6" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </a>
                    @else
                        <span aria-label="Halaman berikutnya" aria-disabled="true" class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-100 text-slate-300">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path d="m9 18 6-6-6-6" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </span>
                    @endif
                    </nav>
                </div>
            </section>

            <aside class="notification-sidebar space-y-3">
                <section class="notification-summary overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                    <div class="flex items-center gap-2.5 border-b border-slate-100 px-4 py-3">
                        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-rose-50 text-rose-700">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 19V5m0 14h17M8 15l3-4 3 2 5-6"/></svg>
                        </span>
                        <h2 class="text-xs font-bold text-slate-800">Ringkasan Notifikasi</h2>
                    </div>
                    <div class="divide-y divide-slate-100 px-3">
                        @foreach([
                            ['label' => 'Semua', 'count' => $totalNotifications, 'category' => 'all'],
                            ['label' => 'Persetujuan', 'count' => $approvalNotifications, 'category' => 'approval'],
                            ['label' => 'Ditolak', 'count' => $deniedNotifications, 'category' => 'denied'],
                            ['label' => 'Diperbarui', 'count' => $updatedNotifications, 'category' => 'updated'],
                        ] as $summary)
                            <a href="{{ route('notifications.index', ['category' => $summary['category']]) }}"
                               class="flex items-center gap-2.5 py-2.5 transition hover:text-rose-700">
                                <span class="notification-icon {{ $categoryStyles[$summary['category']] }} !h-7 !w-7">
                                    <svg class="!h-3.5 !w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">{!! $categoryIcons[$summary['category']] !!}</svg>
                                </span>
                                <span class="flex-1 text-[10px] font-semibold text-slate-600">{{ $summary['label'] }}</span>
                                <span class="text-[10px] font-bold text-slate-700">{{ $summary['count'] }}</span>
                                <svg class="h-3 w-3 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m9 18 6-6-6-6"/></svg>
                            </a>
                        @endforeach
                    </div>
                </section>

                <section class="notification-promo shadow-sm">
                    <img src="{{ asset('assets/images/showroom-tag.png') }}" alt="" loading="lazy">
                    <div class="notification-promo-copy">
                        <p class="text-[10px] font-bold leading-snug">Selalu terhubung dengan informasi penting untuk mendukung kinerja Anda.</p>
                    </div>
                </section>
            </aside>
        </div>
    </div>
@endsection
