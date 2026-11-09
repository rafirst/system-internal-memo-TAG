<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#a9001e">
    <title>@yield('title', 'Internal Memo') · PT Tunas Auto Graha</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
        :root { color-scheme: light; font-family: Arial, Helvetica, sans-serif; }
        body { margin: 0; background: #f2f6fc; color: #172033; }
        .brand-stripes {
            background: repeating-linear-gradient(130deg, #a4001e 0, #a4001e 88px, #c40028 88px, #c40028 122px, #900019 122px, #900019 198px);
        }
        .workspace {
            display: grid;
            grid-template-columns: 220px minmax(0, 1fr);
            min-height: calc(100vh - 104px);
        }
        .sidebar {
            display: flex;
            flex-direction: column;
            border-right: 1px solid #e4eaf2;
            background: rgba(255, 255, 255, .88);
        }
        .sidebar-link {
            display: flex;
            min-height: 42px;
            align-items: center;
            gap: 11px;
            border-radius: 0 7px 7px 0;
            padding: 0 18px;
            color: #334155;
            font-size: 13px;
            font-weight: 600;
            transition: background-color 150ms ease, color 150ms ease;
        }
        .sidebar-link:hover { background: #fff1f2; color: #a4001e; }
        .sidebar-link.is-active { background: #b5122b; color: #fff; box-shadow: 0 5px 12px rgba(164, 0, 30, .16); }
        .sidebar-link svg { width: 17px; height: 17px; flex: none; }
        .app-main { min-width: 0; padding: 16px 20px 24px; }
        .content-shell { width: min(100%, 1440px); margin: 0 auto; }
        .brand-logo { display: block; width: 94px; height: 36px; object-fit: contain; }
        @media (max-width: 760px) {
            .workspace { grid-template-columns: 1fr; min-height: calc(100vh - 104px); }
            .sidebar { display: block; overflow-x: auto; border-right: 0; border-bottom: 1px solid #e4eaf2; }
            .sidebar nav { display: flex; min-width: max-content; padding: 8px 10px; }
            .sidebar-link { border-radius: 8px; padding: 0 12px; }
            .sidebar-brand { display: none; }
            .app-main { padding: 14px 12px 22px; }
        }
        @media (max-width: 520px) {
            .brand-logo { width: 72px; }
            .header-title { font-size: 13px !important; }
            .user-details { display: none; }
        }
    </style>
    @stack('styles')
</head>
<body class="min-h-screen">
@php
    $authUser = auth()->user();
    $roleLabel = $authUser->role?->name ?: ($authUser->role?->label ?: 'Pengguna');
    $unreadNotifications = $authUser->unreadNotifications()->count();
@endphp

<div class="flex min-h-screen flex-col">
    <header class="brand-stripes relative z-10 flex h-14 shrink-0 items-center justify-between px-4 text-white shadow-md sm:px-7">
        <div class="flex min-w-0 items-center gap-4">
            <a href="{{ route('dashboard') }}" aria-label="Dashboard Internal Memo">
                <img class="brand-logo" src="{{ asset('assets/images/Logo-TAG-white.png') }}" alt="Tunas Auto Graha">
            </a>
            <span class="h-7 w-px bg-white/60"></span>
            <span class="header-title truncate text-base font-bold tracking-wide">Internal Memo</span>
        </div>

        <div class="flex items-center gap-3 sm:gap-5">
            <a href="{{ route('notifications.index') }}" class="relative rounded-full p-2 text-white/95 transition hover:bg-white/10" aria-label="Notifikasi">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/>
                </svg>
                @if($unreadNotifications > 0)
                    <span class="absolute right-0 top-0 flex h-4 min-w-4 items-center justify-center rounded-full bg-rose-400 px-1 text-[10px] font-bold text-white">{{ $unreadNotifications }}</span>
                @endif
            </a>

            <div class="relative" x-data="{ profileOpen: false }" @click.outside="profileOpen = false" @keydown.escape.window="profileOpen = false">
                <button type="button"
                        class="flex items-center gap-2.5 rounded-lg px-1.5 py-1 transition hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-white/70"
                        aria-label="Profil {{ $authUser->name }}"
                        aria-haspopup="true"
                        :aria-expanded="profileOpen.toString()"
                        @click="profileOpen = !profileOpen">
                    <span class="flex h-9 w-9 items-center justify-center rounded-full border border-white/60 bg-white/95 text-sm font-bold text-rose-900">
                        {{ mb_strtoupper(mb_substr($authUser->name, 0, 1)) }}
                    </span>
                    <span class="user-details hidden text-left leading-tight sm:block">
                        <span class="block max-w-40 truncate text-xs font-bold">{{ $authUser->name }}</span>
                        <span class="mt-0.5 block text-[10px] text-white/75">{{ $roleLabel }}</span>
                    </span>
                    <svg class="hidden h-3.5 w-3.5 text-white/80 sm:block" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m7 10 5 5 5-5"/>
                    </svg>
                </button>

                <div x-cloak x-show="profileOpen" x-transition
                     class="absolute right-0 top-full z-30 mt-2 w-52 overflow-hidden rounded-xl border border-slate-200 bg-white py-1.5 text-slate-700 shadow-xl"
                     role="menu">
                    <div class="border-b border-slate-100 px-4 py-2.5">
                        <p class="truncate text-xs font-bold text-slate-800">{{ $authUser->name }}</p>
                        <p class="mt-0.5 text-[10px] text-slate-500">{{ $roleLabel }}</p>
                    </div>
                    <form method="POST" action="{{ route('logout') }}" class="px-2 py-1">
                        @csrf
                        <button type="submit" role="menuitem"
                                class="flex w-full items-center gap-2 rounded-lg px-2.5 py-2 text-left text-xs font-semibold text-rose-700 transition hover:bg-rose-50 focus:bg-rose-50 focus:outline-none">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10 17l5-5-5-5m5 5H3m9-9h5a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-5"/>
                            </svg>
                            Keluar
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </header>

    <div class="workspace flex-1">
        <aside class="sidebar">
            <nav class="space-y-1 py-4">
                <a href="{{ route('dashboard') }}" class="sidebar-link {{ request()->routeIs('dashboard') ? 'is-active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m3 10 9-7 9 7v10a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1V10Z"/></svg>
                    Dashboard
                </a>
                <a href="{{ route('memos.index') }}" class="sidebar-link {{ request()->routeIs('memos.index', 'memos.show') ? 'is-active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M7 3h7l5 5v12a1 1 0 0 1-1 1H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M14 3v5h5M9 13h6M9 17h6"/></svg>
                    Semua Memo
                </a>
                @can('create', App\Models\Memo::class)
                    <a href="{{ route('memos.create') }}" class="sidebar-link {{ request()->routeIs('memos.create') ? 'is-active' : '' }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14m7-7H5"/><circle cx="12" cy="12" r="9"/></svg>
                        Pengajuan
                    </a>
                @endcan
                @if($authUser->hasRole('administrator'))
                    <a href="{{ route('admin.users.index') }}" class="sidebar-link {{ request()->routeIs('admin.users.*') ? 'is-active' : '' }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2m6-10a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm10 10v-2a4 4 0 0 0-3-3.87m-1-11.13a4 4 0 0 1 0 7.75"/></svg>
                        Pengguna
                    </a>
                @endif
                <a href="{{ route('notifications.index') }}" class="sidebar-link {{ request()->routeIs('notifications.*') ? 'is-active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></svg>
                    Notifikasi
                </a>
            </nav>

            {{-- <div class="sidebar-brand brand-stripes mt-auto px-5 py-7 text-center text-white">
                <img class="mx-auto mb-3 h-14 w-36 object-contain" src="{{ asset('assets/images/Logo-TAG-white.png') }}" alt="Tunas Auto Graha">
                <p class="text-[10px] font-bold tracking-[.12em]">INTERNAL MEMO SYSTEM</p>
            </div> --}}
        </aside>

        <main class="app-main">
            <div class="content-shell">
                @if(session('success'))
                    <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
                @endif
                @if(session('error'))
                    <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
                @endif
                @if($errors->any())
                    <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                        <ul class="list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                    </div>
                @endif
                @yield('content')
            </div>
        </main>
    </div>

    <footer class="brand-stripes flex min-h-12 shrink-0 items-center justify-between gap-4 px-5 text-[9px] font-bold tracking-wide text-white sm:px-7 sm:text-[10px]">
        <span>PT. TUNAS AUTO GRAHA {{ now()->year }} ©</span>
        <span class="text-right">SYARAT &amp; KETENTUAN BERLAKU</span>
    </footer>
</div>
</body>
</html>
