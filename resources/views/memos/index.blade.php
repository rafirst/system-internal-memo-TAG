@extends('layouts.app')
@section('title', 'Daftar Memo Internal')

@push('styles')
<style>
    .memo-index-hero {
        position: relative;
        min-height: 138px;
        overflow: hidden;
        border: 1px solid rgba(220, 228, 239, .9);
        border-radius: 12px;
        background:
            linear-gradient(90deg, rgba(255, 255, 255, .96) 0%, rgba(255, 255, 255, .88) 34%, rgba(255, 255, 255, .12) 100%),
            url('{{ asset('assets/images/showroom-tag.png') }}') center 53% / cover no-repeat;
        box-shadow: 0 6px 18px rgba(34, 55, 85, .07);
    }
    .memo-index-hero::before,
    .memo-index-hero::after {
        position: absolute;
        z-index: 1;
        width: 38px;
        height: 38px;
        content: "";
        pointer-events: none;
    }
    .memo-index-hero::before {
        top: 0;
        left: 0;
        background: linear-gradient(135deg, #a9001e 0 48%, transparent 49%);
    }
    .memo-index-hero::after {
        right: 0;
        bottom: 0;
        background: linear-gradient(315deg, #b5122b 0 45%, transparent 46%);
    }
    .memo-index-table th {
        background: #f1f5f9;
        color: #617086;
        font-size: 10px;
        font-weight: 700;
        letter-spacing: .04em;
        text-transform: uppercase;
    }
    .memo-index-table td { border-top: 1px solid #edf1f6; }
    @media (max-width: 600px) {
        .memo-index-hero { min-height: 128px; background-position: 60% center; }
    }
</style>
@endpush

@section('content')
<div class="space-y-4">
    <section class="memo-index-hero flex items-center px-6 py-5 sm:px-9" aria-label="Daftar memo internal">
        <div class="relative z-[2] max-w-lg">
            <p class="mb-1 text-[10px] font-bold uppercase tracking-[.14em] text-rose-700">Data &amp; Informasi</p>
            <h1 class="text-2xl font-bold tracking-tight text-slate-800 sm:text-[30px]">Daftar Memo Internal</h1>
            <p class="mt-1 max-w-md text-xs leading-relaxed text-slate-600 sm:text-sm">
                Kelola dan pantau seluruh memo internal perusahaan dengan mudah dan cepat.
            </p>
        </div>
    </section>

    <section class="rounded-xl border border-slate-100 bg-white p-3 shadow-sm sm:p-4" aria-label="Filter memo">
        <form method="GET" action="{{ route('memos.index') }}" class="grid grid-cols-1 gap-2 sm:grid-cols-2 xl:grid-cols-[minmax(180px,1.5fr)_minmax(130px,1fr)_minmax(130px,1fr)_minmax(230px,1.35fr)_auto_auto]">
            <label class="relative block">
                <span class="sr-only">Cari memo</span>
                <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.8"/><path stroke-linecap="round" d="m16 16 4.5 4.5"/></svg>
                <input type="search" name="q" value="{{ request('q') }}" placeholder="Cari nomor memo, judul, atau pengaju..." class="w-full rounded-lg border border-slate-200 py-2.5 pl-9 pr-3 text-xs text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-rose-400 focus:ring-2 focus:ring-rose-100">
            </label>

            <label>
                <span class="sr-only">Filter divisi</span>
                <select name="division_id" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-xs text-slate-700 outline-none focus:border-rose-400 focus:ring-2 focus:ring-rose-100">
                    <option value="">Semua Divisi</option>
                    @foreach($divisions as $division)
                        <option value="{{ $division->id }}" @selected((string) request('division_id') === (string) $division->id)>{{ $division->name }}</option>
                    @endforeach
                </select>
            </label>

            <label>
                <span class="sr-only">Filter status</span>
                <select name="status" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-xs text-slate-700 outline-none focus:border-rose-400 focus:ring-2 focus:ring-rose-100">
                    <option value="">Semua Status</option>
                    @foreach($statuses as $status)
                        <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
                    @endforeach
                </select>
            </label>

            <fieldset class="grid grid-cols-2 gap-2">
                <legend class="sr-only">Rentang tanggal memo</legend>
                <label>
                    <span class="sr-only">Tanggal mulai</span>
                    <input type="date" name="date_from" value="{{ request('date_from') }}" aria-label="Tanggal mulai" class="w-full rounded-lg border border-slate-200 px-2 py-2.5 text-xs text-slate-600 outline-none focus:border-rose-400 focus:ring-2 focus:ring-rose-100">
                </label>
                <label>
                    <span class="sr-only">Tanggal akhir</span>
                    <input type="date" name="date_to" value="{{ request('date_to') }}" aria-label="Tanggal akhir" class="w-full rounded-lg border border-slate-200 px-2 py-2.5 text-xs text-slate-600 outline-none focus:border-rose-400 focus:ring-2 focus:ring-rose-100">
                </label>
            </fieldset>

            <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-lg bg-slate-800 px-4 py-2.5 text-xs font-semibold text-white transition hover:bg-slate-900">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 5h16l-6.5 7.5V19l-3 1v-7.5L4 5Z"/></svg>
                Filter
            </button>

            @can('create', App\Models\Memo::class)
                <a href="{{ route('memos.create') }}" class="inline-flex items-center justify-center gap-2 rounded-lg bg-rose-700 px-4 py-2.5 text-xs font-semibold text-white shadow-sm transition hover:bg-rose-800">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" d="M12 5v14m7-7H5"/></svg>
                    Memo
                </a>
            @endcan
        </form>

        @if($errors->any())
            <div class="mt-3 rounded-lg border border-rose-100 bg-rose-50 px-3 py-2 text-xs text-rose-700" role="alert">
                {{ $errors->first() }}
            </div>
        @endif

        @if(request()->hasAny(['q', 'status', 'division_id', 'date_from', 'date_to']))
            <div class="mt-2 text-right">
                <a href="{{ route('memos.index') }}" class="text-xs font-semibold text-rose-700 hover:text-rose-900">Hapus Filter</a>
            </div>
        @endif
    </section>

    <section class="overflow-hidden rounded-xl border border-slate-100 bg-white shadow-sm" aria-label="Daftar memo">
        @include('partials.memo-table', ['memos' => $memos])

        <div class="flex flex-col gap-3 border-t border-slate-100 px-4 py-3 sm:flex-row sm:items-center sm:justify-between sm:px-5">
            <p class="text-[11px] text-slate-500">
                @if($memos->total() > 0)
                    Menampilkan {{ $memos->firstItem() }}–{{ $memos->lastItem() }} dari {{ $memos->total() }} memo
                @else
                    Menampilkan 0 memo
                @endif
            </p>
            <div class="text-xs">{{ $memos->links() }}</div>
        </div>
    </section>
</div>
@endsection
