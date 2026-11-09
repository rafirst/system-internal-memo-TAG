@extends('layouts.app')
@section('title', $user ? 'Edit Pengguna' : 'Tambah Pengguna')

@push('styles')
    <style>
        .user-form-hero {
            position: relative;
            min-height: 108px;
            overflow: hidden;
            border: 1px solid #dbe6f2;
            border-radius: 13px;
            background: #eaf2fb;
        }
        .user-form-hero::before {
            position: absolute;
            inset: 0;
            background: linear-gradient(90deg, rgba(255,255,255,.98) 0%, rgba(255,255,255,.94) 42%, rgba(255,255,255,.15) 100%), url('{{ asset('assets/images/showroom-tag.png') }}') center 52% / cover;
            content: '';
        }
        .user-form-hero::after {
            position: absolute;
            right: 0;
            bottom: 0;
            width: 7px;
            height: 100%;
            background: #bd102b;
            content: '';
        }
        .user-form-hero-content { position: relative; z-index: 1; padding: 17px 20px; }
        .user-form-control {
            display: block;
            width: 100%;
            height: 36px;
            border: 1px solid #dbe4ef;
            border-radius: 7px;
            background-color: #fff;
            padding: 0 10px 0 34px;
            color: #334155;
            font-size: 11px;
            transition: border-color 150ms ease, box-shadow 150ms ease;
        }
        .user-form-control::placeholder { color: #94a3b8; }
        .user-form-control:focus {
            border-color: #be123c;
            outline: none;
            box-shadow: 0 0 0 3px rgba(190,18,60,.1);
        }
        .user-form-field-icon {
            position: absolute;
            top: 50%;
            left: 10px;
            width: 14px;
            height: 14px;
            transform: translateY(-50%);
            color: #64748b;
            pointer-events: none;
        }
        @media (max-width: 480px) {
            .user-form-hero-content { padding: 15px; }
        }
    </style>
@endpush

@section('content')
    <div class="content-shell space-y-3">
        <section class="user-form-hero shadow-sm">
            <div class="user-form-hero-content">
                <p class="mb-0.5 text-[9px] font-bold uppercase tracking-[.15em] text-rose-700">Manajemen Pengguna</p>
                <h1 class="text-xl font-bold tracking-tight text-slate-800">{{ $user ? 'Edit Pengguna' : 'Tambah Pengguna' }}</h1>
                <p class="mt-1 max-w-lg text-[10px] leading-relaxed text-slate-600">
                    Kelola akun pengguna sistem Internal Memo. Anda dapat menambah pengguna baru sesuai kebutuhan.
                </p>
            </div>
        </section>

        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="flex items-center gap-3 px-4 py-3 sm:px-5">
                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-rose-50 text-rose-700">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2m6-10a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm8-3v6m3-3h-6"/>
                    </svg>
                </span>
                <h2 class="shrink-0 text-xs font-bold text-slate-800">Formulir Pengguna</h2>
                <span class="h-px flex-1 bg-rose-200"></span>
            </div>

            <form method="POST" action="{{ $user ? route('admin.users.update', $user) : route('admin.users.store') }}" class="px-4 pb-4 sm:px-5 sm:pb-5">
                @csrf
                @if($user) @method('PUT') @endif

                <div class="grid grid-cols-1 gap-x-5 gap-y-3 sm:grid-cols-2">
                    <div>
                        <label for="name" class="mb-1 block text-[10px] font-semibold text-slate-700">Nama <span class="text-rose-600">*</span></label>
                        <div class="relative">
                            <svg class="user-form-field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path stroke-linecap="round" stroke-linejoin="round" d="M5 21v-1a7 7 0 0 1 14 0v1"/></svg>
                            <input id="name" name="name" required value="{{ old('name', $user?->name) }}" autocomplete="name" placeholder="Masukkan nama lengkap" class="user-form-control">
                        </div>
                    </div>

                    <div>
                        <label for="username" class="mb-1 block text-[10px] font-semibold text-slate-700">Username <span class="text-rose-600">*</span></label>
                        <div class="relative">
                            <svg class="user-form-field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" stroke-linejoin="round" d="M15.5 8.5a3.5 3.5 0 1 0 0 7h1a2 2 0 0 0 2-2v-.5M8.5 12a3.5 3.5 0 0 0 7 0"/></svg>
                            <input id="username" name="username" required minlength="3" maxlength="50" pattern="[a-z0-9._]{3,50}" value="{{ old('username', $user?->username) }}" autocomplete="username" placeholder="Masukkan username" class="user-form-control">
                        </div>
                        @error('username')<p class="mt-1 text-[10px] text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="email" class="mb-1 block text-[10px] font-semibold text-slate-700">Email (untuk notifikasi) <span class="text-rose-600">*</span></label>
                        <div class="relative">
                            <svg class="user-form-field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path stroke-linecap="round" stroke-linejoin="round" d="m4 7 8 6 8-6"/></svg>
                            <input id="email" type="email" name="email" required maxlength="255" value="{{ old('email', $user?->email) }}" autocomplete="email" placeholder="contoh@email.com" class="user-form-control">
                        </div>
                        @error('email')<p class="mt-1 text-[10px] text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="password" class="mb-1 block text-[10px] font-semibold text-slate-700">Password @unless($user)<span class="text-rose-600">*</span>@else<span class="font-normal text-slate-500">(kosongkan jika tidak diubah)</span>@endunless</label>
                        <div class="relative">
                            <svg class="user-form-field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="4" y="10" width="16" height="11" rx="2"/><path stroke-linecap="round" stroke-linejoin="round" d="M8 10V7a4 4 0 1 1 8 0v3m-4 5v2"/></svg>
                            <input id="password" type="password" name="password" {{ $user ? '' : 'required' }} autocomplete="new-password" placeholder="Minimal 8 karakter" class="user-form-control">
                        </div>
                    </div>

                    <div>
                        <label for="jabatan" class="mb-1 block text-[10px] font-semibold text-slate-700">Jabatan</label>
                        <div class="relative">
                            <svg class="user-form-field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="3" y="7" width="18" height="14" rx="2"/><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m-13 5h18m-11 0v2h4v-2"/></svg>
                            <select id="jabatan" name="jabatan" class="user-form-control">
                                <option value="">Pilih jabatan</option>
                                @foreach($positions as $position)
                                    <option value="{{ $position }}" @selected(old('jabatan', $user?->jabatan) === $position)>{{ $position }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label for="role_id" class="mb-1 block text-[10px] font-semibold text-slate-700">Role <span class="text-rose-600">*</span></label>
                        <div class="relative">
                            <svg class="user-form-field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m12 3 8 4v5c0 5-3.5 8-8 9-4.5-1-8-4-8-9V7l8-4Z"/><path stroke-linecap="round" stroke-linejoin="round" d="m9 12 2 2 4-4"/></svg>
                            <select id="role_id" name="role_id" required class="user-form-control">
                                @foreach($roles as $r)<option value="{{ $r->id }}" @selected(old('role_id', $user?->role_id) == $r->id)>{{ $r->label }}</option>@endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label for="division_id" class="mb-1 block text-[10px] font-semibold text-slate-700">Divisi</label>
                        <div class="relative">
                            <svg class="user-form-field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="5" r="2"/><circle cx="6" cy="19" r="2"/><circle cx="18" cy="19" r="2"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 7v5m0 0H6v5m6-5h6v5"/></svg>
                            <select id="division_id" name="division_id" class="user-form-control">
                                <option value="">Pilih divisi</option>
                                @foreach($divisions as $d)<option value="{{ $d->id }}" @selected(old('division_id', $user?->division_id) == $d->id)>{{ $d->name }}</option>@endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label for="branch_id" class="mb-1 block text-[10px] font-semibold text-slate-700">Cabang</label>
                        <div class="relative">
                            <svg class="user-form-field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/></svg>
                            <select id="branch_id" name="branch_id" class="user-form-control">
                                <option value="">Pilih cabang</option>
                                @foreach($branches as $b)<option value="{{ $b->id }}" @selected(old('branch_id', $user?->branch_id) == $b->id)>{{ $b->name }}</option>@endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <label class="mt-3 flex cursor-pointer items-start gap-2.5 rounded-lg border border-rose-100 bg-rose-50/80 px-3 py-2.5">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $user?->is_active ?? true)) class="mt-0.5 h-4 w-4 shrink-0 accent-rose-700">
                    <span>
                        <span class="block text-[10px] font-semibold text-slate-800">Akun aktif</span>
                        <span class="mt-0.5 block text-[9px] leading-relaxed text-slate-500">Staff wajib punya divisi dan cabang agar bisa membuat memo. Akun aktif dapat langsung menggunakan sistem.</span>
                    </span>
                </label>

                <div class="mt-3 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <a href="{{ route('admin.users.index') }}" class="inline-flex min-h-9 items-center justify-center gap-2 rounded-md border border-slate-300 bg-white px-4 text-[10px] font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 14 4 9l5-5M4 9h10a6 6 0 0 1 0 12h-2"/></svg>
                        Batal
                    </a>
                    <button type="submit" class="inline-flex min-h-9 items-center justify-center gap-2 rounded-md bg-rose-700 px-4 text-[10px] font-semibold text-white shadow-sm transition hover:bg-rose-800 focus:outline-none focus:ring-2 focus:ring-rose-300">
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 4h12l3 3v13H4V4h1Zm3 0v6h8V4m-8 16v-6h8v6"/></svg>
                        Simpan
                    </button>
                </div>
            </form>
        </section>
    </div>
@endsection
