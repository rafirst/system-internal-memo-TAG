@extends('layouts.app')
@section('title', 'Manajemen Pengguna')

@push('styles')
    <style>
        .users-hero {
            position: relative;
            min-height: 108px;
            overflow: hidden;
            border: 1px solid #dbe6f2;
            border-radius: 13px;
            background: #eaf2fb;
        }
        .users-hero::before {
            position: absolute;
            inset: 0;
            background: linear-gradient(90deg, rgba(255,255,255,.98) 0%, rgba(255,255,255,.94) 42%, rgba(255,255,255,.15) 100%), url('{{ asset('assets/images/showroom-tag.png') }}') center 52% / cover;
            content: '';
        }
        .users-hero::after {
            position: absolute;
            right: 0;
            bottom: 0;
            width: 7px;
            height: 100%;
            background: #bd102b;
            content: '';
        }
        .users-hero-content { position: relative; z-index: 1; padding: 17px 20px; }
        .users-layout {
            display: grid;
            grid-template-columns: minmax(0, 1fr);
            align-items: start;
        }
        .users-table th { white-space: nowrap; }
        .users-table td { vertical-align: middle; }
        .user-avatar {
            display: flex;
            width: 29px;
            height: 29px;
            flex: none;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            font-size: 9px;
            font-weight: 800;
        }
        .user-avatar-0 { background: #fee2e2; color: #b91c1c; }
        .user-avatar-1 { background: #dbeafe; color: #1d4ed8; }
        .user-avatar-2 { background: #ede9fe; color: #6d28d9; }
        .user-avatar-3 { background: #d1fae5; color: #047857; }
        .user-avatar-4 { background: #ffedd5; color: #c2410c; }
        .users-filter-control {
            display: block;
            width: 100%;
            border: 1px solid #dbe4ef;
            border-radius: 6px;
            background: #fff;
            padding: 7px 9px;
            color: #334155;
            font-size: 10px;
        }
        .users-filter-control:focus { border-color: #be123c; outline: 2px solid rgba(190,18,60,.12); }
        .users-search-control {
            display: block;
            width: 100%;
            border: 1px solid #dbe4ef;
            border-radius: 7px;
            padding: 8px 10px;
            font-size: 10px;
        }
        .users-search-control:focus { border-color: #be123c; outline: 2px solid rgba(190,18,60,.12); }
        .users-filter-field { min-width: 0; flex: 1 1 0%; }
        .users-filter-action { flex: none; }
        @media (max-width: 640px) {
            .users-filter-field { flex: 1 1 calc(50% - 8px); }
            .users-search-field { flex: 1 1 100%; }
        }
        @media (max-width: 480px) {
            .users-hero-content { padding: 15px; }
        }
    </style>
@endpush

@section('content')
    <div class="content-shell space-y-3">
        <section class="users-hero shadow-sm">
            <div class="users-hero-content">
                <p class="mb-0.5 text-[9px] font-bold uppercase tracking-[.15em] text-rose-700">Manajemen Pengguna</p>
                <h1 class="text-xl font-bold tracking-tight text-slate-800">Daftar Pengguna</h1>
                <p class="mt-1 max-w-lg text-[10px] leading-relaxed text-slate-600">
                    Kelola akun pengguna sistem Internal Memo. Anda dapat menambah, mengubah, atau menonaktifkan akun sesuai kebutuhan.
                </p>
            </div>
        </section>

        <div class="users-layout">
            <section class="min-w-0">
                <form method="GET" action="{{ route('admin.users.index') }}" class="mb-2 flex flex-wrap items-center gap-2 rounded-xl border border-slate-200 bg-white p-2.5 shadow-sm">
                    <label class="users-search-field relative min-w-0 flex-1">
                        <span class="sr-only">Cari pengguna</span>
                        <svg class="absolute left-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="m16 16 4 4"/></svg>
                        <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" class="users-search-control pl-8" placeholder="Cari nama, username, divisi, atau jabatan...">
                    </label>
                    <label class="users-filter-field">
                        <span class="sr-only">Filter divisi</span>
                        <select name="division_id" aria-label="Filter divisi" class="users-filter-control">
                            <option value="">Semua Divisi</option>
                            @foreach($divisions as $division)
                                <option value="{{ $division->id }}" @selected(($filters['division_id'] ?? '') == $division->id)>{{ $division->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="users-filter-field">
                        <span class="sr-only">Filter jabatan</span>
                        <select name="jabatan" aria-label="Filter jabatan" class="users-filter-control">
                            <option value="">Semua Jabatan</option>
                            @foreach($positions as $position)
                                <option value="{{ $position }}" @selected(($filters['jabatan'] ?? '') === $position)>{{ $position }}</option>
                            @endforeach
                        </select>
                    </label>
                    <button type="submit" class="users-filter-action inline-flex min-h-[34px] items-center justify-center gap-1.5 rounded-md bg-slate-800 px-3 text-[10px] font-semibold text-white transition hover:bg-slate-700">
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.3-4.3M10.8 18a7.2 7.2 0 1 0 0-14.4 7.2 7.2 0 0 0 0 14.4Z"/></svg>
                        Filter
                    </button>
                    <a href="{{ route('admin.users.create') }}" class="users-filter-action inline-flex min-h-[34px] items-center justify-center gap-1.5 rounded-md bg-rose-700 px-3 text-[10px] font-semibold text-white transition hover:bg-rose-800">
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" d="M12 5v14m7-7H5"/></svg>
                        Tambah User
                    </a>
                </form>

                <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                    <div class="overflow-x-auto">
                        <table class="users-table min-w-[920px] w-full text-left">
                            <thead class="bg-slate-50 text-[8px] font-bold uppercase tracking-wide text-slate-500">
                                <tr>
                                    <th class="px-3 py-2.5">No.</th>
                                    <th class="px-3 py-2.5">Nama</th>
                                    <th class="px-3 py-2.5">Username</th>
                                    <th class="px-3 py-2.5">Email (notifikasi)</th>
                                    <th class="px-3 py-2.5">Divisi</th>
                                    <th class="px-3 py-2.5">Jabatan</th>
                                    <th class="px-3 py-2.5">Role</th>
                                    <th class="px-3 py-2.5">Cabang</th>
                                    <th class="px-3 py-2.5 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse($users as $user)
                                    <tr class="transition hover:bg-slate-50/80">
                                        <td class="px-3 py-2.5 text-[9px] text-slate-500">{{ $users->firstItem() + $loop->index }}</td>
                                        <td class="px-3 py-2.5">
                                            <div class="flex items-center gap-2">
                                                <span class="user-avatar user-avatar-{{ $loop->index % 5 }}">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</span>
                                                <span class="max-w-36 truncate text-[10px] font-bold text-slate-700">{{ $user->name }}</span>
                                            </div>
                                        </td>
                                        <td class="px-3 py-2.5 text-[9px] text-slate-600">{{ $user->username }}</td>
                                        <td class="px-3 py-2.5 text-[9px] text-slate-600">{{ $user->email }}</td>
                                        <td class="px-3 py-2.5 text-[9px] text-slate-600">{{ $user->division?->name ?? '—' }}</td>
                                        <td class="px-3 py-2.5 text-[9px] text-slate-600">{{ $user->jabatan ?: '—' }}</td>
                                        <td class="px-3 py-2.5 text-[9px] text-slate-600">
                                            @php($roleLabel = $user->role?->label ?? '—')
                                            <span @class([
                                                'inline-flex rounded-md px-2 py-1 text-[9px] font-semibold',
                                                'bg-emerald-100 text-emerald-800' => mb_strtoupper($roleLabel) === 'ADH',
                                                'bg-cyan-100 text-cyan-800' => mb_strtoupper($roleLabel) !== 'ADH',
                                            ])>{{ $roleLabel }}</span>
                                        </td>
                                        <td class="px-3 py-2.5 text-[9px] text-slate-600">{{ $user->branch?->name ?? '—' }}</td>
                                        <td class="px-3 py-2.5 text-center">
                                            <a href="{{ route('admin.users.edit', $user) }}" aria-label="Edit {{ $user->name }}" title="Edit pengguna"
                                               class="inline-flex h-7 w-7 items-center justify-center rounded-md text-slate-500 transition hover:bg-rose-50 hover:text-rose-700">
                                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m16 5 3 3M4 20l4.5-1 11-11a2.1 2.1 0 0 0-3-3l-11 11L4 20Z"/></svg>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="px-4 py-12 text-center">
                                            <span class="mx-auto mb-2 flex h-10 w-10 items-center justify-center rounded-full bg-slate-100 text-slate-500">
                                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="9" cy="8" r="4"/><path stroke-linecap="round" stroke-linejoin="round" d="M3 21v-2a6 6 0 0 1 12 0v2m3-11h6m-3-3v6"/></svg>
                                            </span>
                                            <p class="text-xs font-semibold text-slate-700">Pengguna tidak ditemukan</p>
                                            <p class="mt-1 text-[10px] text-slate-500">Ubah kata kunci atau sesuaikan filter Anda.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="flex flex-wrap items-center justify-between gap-2 border-t border-slate-100 px-3 py-2.5">
                        <p class="text-[9px] text-slate-500">
                            Menampilkan {{ $users->firstItem() ?? 0 }}–{{ $users->lastItem() ?? 0 }} dari {{ $users->total() }} pengguna
                        </p>
                        <div class="text-xs">{{ $users->links() }}</div>
                    </div>
                </div>
            </section>

        </div>
    </div>
@endsection
