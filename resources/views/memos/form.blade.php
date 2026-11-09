@extends('layouts.app')
@section('title', $memo ? 'Edit Memo' : 'Buat Memo')

@push('styles')
    <style>
        .memo-composer { max-width: 1480px; margin: 0 auto; }
        .memo-composer-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.65fr) minmax(320px, .8fr);
            grid-template-areas:
                "general preview"
                "detail preview"
                "payment guidance"
                "approvers ."
                "attachment ."
                "actions .";
            gap: 14px 18px;
            align-items: stretch;
        }
        .memo-composer-grid > form,
        .memo-composer-grid > aside { display: contents; }
        .memo-composer-intro { display: grid; gap: 12px; margin-bottom: 14px; }
        .memo-composer-grid > form > section:nth-of-type(1) { grid-area: general; }
        .memo-composer-grid > form > section:nth-of-type(2) { grid-area: detail; }
        .memo-composer-grid > form > section:nth-of-type(3) { grid-area: payment; }
        .memo-composer-grid > form > section:nth-of-type(4) { grid-area: approvers; }
        .memo-composer-grid > form > section:nth-of-type(5) { grid-area: attachment; }
        .memo-composer-actions { grid-area: actions; }
        .memo-composer-preview-card { grid-area: preview; }
        .memo-composer-guidance { grid-area: guidance; }
        .memo-composer-hero { position: relative; min-height: 126px; overflow: hidden; border-radius: 14px; background: #eff6ff; }
        .memo-composer-hero::before { position: absolute; inset: 0; background: linear-gradient(90deg, rgba(255,255,255,.98) 0%, rgba(255,255,255,.92) 44%, rgba(255,255,255,.15) 100%), url('/assets/images/showroom-tag.png') center 52% / cover; content: ''; }
        .memo-composer-hero::after { position: absolute; right: 0; bottom: 0; width: 8px; height: 100%; background: #c4142d; content: ''; }
        .memo-composer-hero-content { position: relative; z-index: 1; padding: 22px 24px; }
        .memo-composer-input { display: block; width: 100%; border: 1px solid #d7e0ec; border-radius: 7px; background: #fff; padding: 9px 11px; color: #172033; font-size: 13px; line-height: 1.4; }
        .memo-composer-input:focus { border-color: #bf1e32; outline: 2px solid rgba(191,30,50,.12); }
        .memo-composer-label { display: block; margin-bottom: 5px; color: #334155; font-size: 12px; font-weight: 650; }
        .memo-composer-section { border: 1px solid #e3eaf2; border-radius: 11px; background: #fff; padding: 16px; }
        .memo-composer-paper { min-height: 620px; background: #fff; padding: 20px 18px; color: #111827; font: 9px/1.55 Arial, Helvetica, sans-serif; }
        .memo-composer-paper-header { width: 100%; border-bottom: 1px solid #111827; margin-bottom: 14px; padding-bottom: 7px; }
        .memo-composer-paper-header td { vertical-align: middle; }
        .memo-composer-paper-logo { display: block; width: auto; height: 34px; object-fit: contain; }
        .memo-composer-paper-title { margin: 10px 0 14px; text-align: center; }
        .memo-composer-paper-title strong { text-decoration: underline; font-size: 11px; }
        .memo-composer-paper-items { width: 100%; border-collapse: collapse; margin: 8px 0 14px; font-size: 8px; }
        .memo-composer-paper-items th, .memo-composer-paper-items td { border: 1px solid #111827; padding: 4px; }
        .memo-composer-paper-items th { background: #eee; text-align: center; }
        .memo-composer-paper-sign { margin-top: 22px; text-align: center; }
        .memo-composer-guidance { padding: 16px; }
        .memo-composer-sample { position: relative; min-height: 116px; overflow: hidden; border: 1px solid #e4eaf2; border-radius: 8px; background: linear-gradient(145deg, #f8fafc, #fff); padding: 14px; box-shadow: 0 2px 8px rgba(15,23,42,.05); }
        .memo-composer-sample::before { position: absolute; top: 0; left: 0; width: 100%; height: 5px; background: #b5122b; content: ''; }
        .memo-composer-sample::after { position: absolute; right: -14px; bottom: -22px; width: 62px; height: 62px; transform: rotate(45deg); background: #b5122b; content: ''; }
        .memo-composer-sample-lines { display: grid; gap: 6px; margin-top: 12px; }
        .memo-composer-sample-lines span { display: block; height: 4px; border-radius: 4px; background: #e5ebf2; }
        @media (max-width: 1020px) {
            .memo-composer-grid {
                grid-template-columns: minmax(0, 1fr);
                grid-template-areas:
                    "general"
                    "detail"
                    "payment"
                    "approvers"
                    "attachment"
                    "actions"
                    "preview"
                    "guidance";
                gap: 14px;
            }
            .memo-composer-intro { margin-bottom: 14px; }
        }
        @media (max-width: 640px) {
            .memo-composer-hero-content { padding: 18px; }
            .memo-composer-section { padding: 13px; }
            .memo-composer-paper { min-height: 0; padding: 16px 12px; }
        }
    </style>
@endpush

@section('content')
@php
    $isRevision = $memo && $memo->status === \App\Enums\MemoStatus::Denied;
    $isPendingEdit = $memo && $memo->status === \App\Enums\MemoStatus::Pending;
    $oldPameran = old('pameran');
    $initialPameran = array_replace([
        'location' => '',
        'event_date' => '',
        'duration' => '',
        'display_unit' => '',
        'sp_tools' => '',
        'display_pic' => '',
        'loading_in' => '',
        'loading_in_time' => '',
        'loading_out' => '',
        'loading_out_time' => '',
        'sp_tools_pic' => '',
        'cleaning_pic' => '',
        'marketing_programs' => '',
        'budget_min' => '',
        'budget_max' => '',
        'prospect_target' => '',
        'spk_target' => '',
        'note' => '',
    ], is_array($oldPameran) ? $oldPameran : ($memo?->pameran_details ?? []));
    $initialItems = old('items', $memo
        ? $memo->items->map(fn ($i) => ['name' => $i->name, 'qty' => $i->qty, 'unit_price' => (float) $i->unit_price])->all()
        : [['name' => '', 'qty' => '', 'unit_price' => '']]);
    $approverOptions = $approvers->map(fn ($approver) => [
        'id' => (string) $approver->id,
        'name' => $approver->name,
        'role' => $approver->role?->label ?? $approver->role?->name ?? 'Lainnya',
        'jabatan' => $approver->jabatan ?? '',
    ])->values();
    $approverOptionsById = $approverOptions->keyBy('id');
    $initialApproverIds = array_map('strval', old('approver_ids', $memo?->approver_ids ?? []));
    $initialApproverRows = collect($initialApproverIds)
        ->map(fn ($id) => [
            'id' => $id,
            'query' => data_get($approverOptionsById->get($id), 'name', ''),
            'open' => false,
        ])
        ->values()
        ->all();
    if ($initialApproverRows === []) {
        $initialApproverRows[] = ['id' => '', 'query' => '', 'open' => false];
    }
    $lastDenied = $isRevision ? $memo->approvals->where('status', \App\Enums\ApprovalStatus::Denied)->sortByDesc('round')->first() : null;
    $selectedDivision = isset($divisions) ? $divisions->firstWhere('id', old('division_id')) : null;
    $selectedBranch = isset($branches) ? $branches->firstWhere('id', old('branch_id')) : null;
    $divisionName = $memo?->division?->name ?? $selectedDivision?->name ?? auth()->user()->division?->name ?? '';
    $branchCity = $memo?->branch?->city ?? $selectedBranch?->city ?? auth()->user()->branch?->city ?? '';
    $requester = $memo?->onBehalfOf ?? $memo?->submitter ?? auth()->user();
@endphp

<div
    class="memo-composer"
    x-data="{
        items: {{ \Illuminate\Support\Js::from($initialItems) }},
        approverOptions: {{ \Illuminate\Support\Js::from($approverOptions) }},
        approverRows: {{ \Illuminate\Support\Js::from($initialApproverRows) }},
        memoKind: {{ \Illuminate\Support\Js::from(old('memo_kind', $memo?->memo_kind ?? 'pengajuan')) }},
        pameran: {{ \Illuminate\Support\Js::from($initialPameran) }},
        subject: {{ \Illuminate\Support\Js::from(old('subject', $memo?->subject ?? '')) }},
        openingParagraph: {{ \Illuminate\Support\Js::from(old('opening_paragraph', $memo?->opening_paragraph ?? '')) }},
        memoDate: {{ \Illuminate\Support\Js::from(old('memo_date', $memo?->memo_date?->toDateString() ?? now()->toDateString())) }},
        bankName: {{ \Illuminate\Support\Js::from(old('bank_name', $memo?->bank_name ?? '')) }},
        accountNumber: {{ \Illuminate\Support\Js::from(old('account_number', $memo?->account_number ?? '')) }},
        accountHolder: {{ \Illuminate\Support\Js::from(old('account_holder', $memo?->account_holder ?? '')) }},
        attachmentNames: [],
        divisionName: {{ \Illuminate\Support\Js::from($divisionName) }},
        branchCity: {{ \Illuminate\Support\Js::from($branchCity) }},
        add() { this.items.push({ name: '', qty: '', unit_price: '' }) },
        remove(i) { if (this.items.length > 1) this.items.splice(i, 1) },
        addApprover() {
            this.approverRows.push({ id: '', query: '', open: false })
        },
        removeApprover(index) {
            if (this.approverRows.length > 1) this.approverRows.splice(index, 1)
        },
        setApproverQuery(index, query) {
            this.approverRows[index].query = query
            this.approverRows[index].id = ''
            this.approverRows[index].open = true
        },
        filteredApprovers(index) {
            const query = this.approverRows[index].query.trim().toLowerCase()
            const selectedIds = this.approverRows
                .filter((row, rowIndex) => rowIndex !== index && row.id)
                .map(row => row.id)

            return this.approverOptions.filter(approver => {
                const searchable = [approver.name, approver.role, approver.jabatan].join(' ').toLowerCase()
                return !selectedIds.includes(approver.id) && (!query || searchable.includes(query))
            })
        },
        selectApprover(index, approver) {
            this.approverRows[index].id = approver.id
            this.approverRows[index].query = approver.name
            this.approverRows[index].open = false
        },
        sub(it) { return (Number(it.qty) || 1) * (Number(it.unit_price) || 0) },
        get total() { return this.items.reduce((sum, item) => sum + this.sub(item), 0) },
        fmt(n) { return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(n || 0) },
        rupiah(n) { return 'Rp ' + this.fmt(n) + ',-' },
        sayNumber(n) {
            const words = ['', 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan', 'sepuluh', 'sebelas']
            const rest = (value) => value > 0 ? ' ' + this.sayNumber(value) : ''
            if (n < 12) return words[n]
            if (n < 20) return this.sayNumber(n - 10) + ' belas'
            if (n < 100) return this.sayNumber(Math.floor(n / 10)) + ' puluh' + rest(n % 10)
            if (n < 200) return 'seratus' + rest(n - 100)
            if (n < 1000) return this.sayNumber(Math.floor(n / 100)) + ' ratus' + rest(n % 100)
            if (n < 2000) return 'seribu' + rest(n - 1000)
            if (n < 1000000) return this.sayNumber(Math.floor(n / 1000)) + ' ribu' + rest(n % 1000)
            if (n < 1000000000) return this.sayNumber(Math.floor(n / 1000000)) + ' juta' + rest(n % 1000000)
            if (n < 1000000000000) return this.sayNumber(Math.floor(n / 1000000000)) + ' miliar' + rest(n % 1000000000)
            return this.sayNumber(Math.floor(n / 1000000000000)) + ' triliun' + rest(n % 1000000000000)
        },
        terbilang(n) {
            const value = Math.round(Number(n) || 0)
            const words = value === 0 ? 'nol' : this.sayNumber(value)
            return words.replace(/\b[a-z]/g, (letter) => letter.toUpperCase()) + ' Rupiah'
        },
        dateLabel() {
            if (!this.memoDate) return '-'
            return new Intl.DateTimeFormat('id-ID', { day: '2-digit', month: 'long', year: 'numeric', timeZone: 'UTC' })
                .format(new Date(this.memoDate + 'T00:00:00Z'))
        },
        pameranDateLabel() {
            if (!this.pameran.event_date) return '-'
            return new Intl.DateTimeFormat('id-ID', { day: '2-digit', month: 'long', year: 'numeric', timeZone: 'UTC' })
                .format(new Date(this.pameran.event_date + 'T00:00:00Z'))
        }
    }"
>
    <div class="memo-composer-intro">
        <header class="memo-composer-hero border border-blue-100 shadow-sm">
            <div class="memo-composer-hero-content">
                <p class="mb-1 text-[10px] font-bold uppercase tracking-[.16em] text-red-700">Buat Internal Memo</p>
                <h1 class="text-2xl font-bold tracking-tight text-slate-800">{{ $memo ? 'Perbarui Memo' : 'Ajukan Memo Baru' }}</h1>
                <p class="mt-1 max-w-lg text-xs leading-relaxed text-slate-600">
                    {{ auth()->user()->hasRole('administrator') && ! $memo
                        ? 'Pilih divisi, cabang, dan urutan pemberi persetujuan memo.'
                        : 'Isi detail pengajuan memo internal dengan lengkap sebelum menyimpan atau mengajukan.' }}
                </p>
            </div>
        </header>

        @if ($errors->any())
            <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700" role="alert">
                <p class="mb-1 font-semibold">Periksa kembali data berikut:</p>
                <ul class="list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if($lastDenied)
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                <strong>Ditolak oleh {{ $lastDenied->actor?->name }} ({{ $lastDenied->approverRole->label }}):</strong>
                {{ $lastDenied->comment }}
            </div>
        @endif
    </div>

    <div class="memo-composer-grid">
            <form method="POST" enctype="multipart/form-data"
                  action="{{ $memo ? route('memos.update', $memo) : route('memos.store') }}">
                @csrf
                @if($memo) @method('PUT') @endif

                <section class="memo-composer-section shadow-sm">
                    <div class="mb-3 flex items-start gap-2.5">
                        <span class="grid h-7 w-7 shrink-0 place-items-center rounded-md bg-red-50 text-red-700" aria-hidden="true">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 6h13M8 12h13M8 18h13M3.5 6h.01M3.5 12h.01M3.5 18h.01" />
                            </svg>
                        </span>
                        <div>
                            <h2 class="text-sm font-bold text-slate-800">Informasi Umum</h2>
                            <p class="text-[11px] text-slate-500">Lengkapi informasi dasar yang akan tercantum pada memo.</p>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="memo_kind" class="memo-composer-label">Jenis Internal Memo <span class="text-red-600">*</span></label>
                        <select id="memo_kind" name="memo_kind" x-model="memoKind" required class="memo-composer-input">
                            <option value="pengajuan">Pengajuan</option>
                            <option value="pameran">Pameran</option>
                        </select>
                        @error('memo_kind')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>

                    @if(auth()->user()->hasRole('administrator') && ! $memo)
                        <div class="mb-3 grid gap-3 sm:grid-cols-2">
                            <div>
                                <label for="division_id" class="memo-composer-label">Divisi <span class="text-red-600">*</span></label>
                                <select id="division_id" name="division_id" required
                                        class="memo-composer-input"
                                        @change="divisionName = $event.target.selectedOptions[0].textContent.trim()">
                                    <option value="">Pilih divisi</option>
                                    @foreach($divisions as $division)
                                        <option value="{{ $division->id }}" @selected(old('division_id') == $division->id)>{{ $division->name }}</option>
                                    @endforeach
                                </select>
                                @error('division_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="branch_id" class="memo-composer-label">Cabang <span class="text-red-600">*</span></label>
                                <select id="branch_id" name="branch_id" required
                                        class="memo-composer-input"
                                        @change="branchCity = $event.target.selectedOptions[0].dataset.city || ''">
                                    <option value="">Pilih cabang</option>
                                    @foreach($branches as $branch)
                                        <option value="{{ $branch->id }}" data-city="{{ $branch->city }}" @selected(old('branch_id') == $branch->id)>{{ $branch->name }}</option>
                                    @endforeach
                                </select>
                                @error('branch_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    @endif

                    <div class="grid gap-3 sm:grid-cols-[minmax(0,1fr)_190px]">
                        <div>
                            <label for="subject" class="memo-composer-label">Hal <span class="text-red-600">*</span></label>
                            <input id="subject" type="text" name="subject" required maxlength="255"
                                   x-model="subject" value="{{ old('subject', $memo?->subject) }}"
                                   class="memo-composer-input" :placeholder="memoKind === 'pameran' ? 'Contoh: Pengajuan Public Display di Vamos Padel' : 'Contoh: Pengajuan Peralatan Kerja'">
                            @error('subject')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="memo_date" class="memo-composer-label">Tanggal memo <span class="text-red-600">*</span></label>
                            <input id="memo_date" type="date" name="memo_date" required
                                   x-model="memoDate" value="{{ old('memo_date', $memo?->memo_date?->toDateString() ?? now()->toDateString()) }}"
                                   class="memo-composer-input">
                            @error('memo_date')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="mt-3">
                        <label for="opening_paragraph" class="memo-composer-label">Paragraf pembuka <span class="text-red-600">*</span></label>
                        <textarea id="opening_paragraph" name="opening_paragraph" rows="3" required
                                  x-model="openingParagraph" class="memo-composer-input resize-y"
                                  placeholder="Sehubungan dengan ..., maka kami mengajukan ... dengan rincian sebagai berikut:">{{ old('opening_paragraph', $memo?->opening_paragraph) }}</textarea>
                        <div class="mt-1 flex justify-between text-[10px] text-slate-400">
                            @error('opening_paragraph')<span class="text-red-600">{{ $message }}</span>@else<span>Ditampilkan setelah salam pembuka.</span>@enderror
                            <span x-text="openingParagraph.length + ' karakter'"></span>
                        </div>
                    </div>
                </section>

                <section class="memo-composer-section shadow-sm">
                    <div x-show="memoKind === 'pameran'" class="mb-4 rounded-lg border border-slate-200 bg-slate-50 p-4">
                        <div class="mb-3">
                            <h2 class="text-sm font-bold text-slate-800">Informasi Pameran</h2>
                            <p class="text-[11px] text-slate-500">Lengkapi rencana kegiatan sesuai format Public Display.</p>
                        </div>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <label for="pameran_location" class="memo-composer-label">Lokasi <span class="text-red-600">*</span></label>
                                <input id="pameran_location" type="text" name="pameran[location]" x-model="pameran.location" :disabled="memoKind !== 'pameran'" required maxlength="255" class="memo-composer-input" placeholder="Contoh: Vamos Padel Palembang">
                            </div>
                            <div>
                                <label for="pameran_event_date" class="memo-composer-label">Tanggal mulai <span class="text-red-600">*</span></label>
                                <input id="pameran_event_date" type="date" name="pameran[event_date]" x-model="pameran.event_date" :disabled="memoKind !== 'pameran'" required class="memo-composer-input">
                            </div>
                            <div>
                                <label for="pameran_duration" class="memo-composer-label">Durasi <span class="text-red-600">*</span></label>
                                <input id="pameran_duration" type="text" name="pameran[duration]" x-model="pameran.duration" :disabled="memoKind !== 'pameran'" required maxlength="100" class="memo-composer-input" placeholder="Contoh: 1 Hari">
                            </div>
                            <div>
                                <label for="pameran_display_unit" class="memo-composer-label">Unit display <span class="text-red-600">*</span></label>
                                <input id="pameran_display_unit" type="text" name="pameran[display_unit]" x-model="pameran.display_unit" :disabled="memoKind !== 'pameran'" required maxlength="255" class="memo-composer-input" placeholder="Contoh: Alphard">
                            </div>
                            <div class="sm:col-span-2">
                                <label for="pameran_sp_tools" class="memo-composer-label">SP Tools <span class="text-red-600">*</span></label>
                                <textarea id="pameran_sp_tools" name="pameran[sp_tools]" x-model="pameran.sp_tools" :disabled="memoKind !== 'pameran'" required maxlength="1000" rows="2" class="memo-composer-input resize-y" placeholder="Contoh: Tripod Banner, Spanduk, Name Plat"></textarea>
                            </div>
                            <div>
                                <label for="pameran_display_pic" class="memo-composer-label">PIC unit display <span class="text-red-600">*</span></label>
                                <input id="pameran_display_pic" type="text" name="pameran[display_pic]" x-model="pameran.display_pic" :disabled="memoKind !== 'pameran'" required maxlength="255" class="memo-composer-input">
                            </div>
                            <div>
                                <label for="pameran_loading_in" class="memo-composer-label">Loading in oleh <span class="text-red-600">*</span></label>
                                <input id="pameran_loading_in" type="text" name="pameran[loading_in]" x-model="pameran.loading_in" :disabled="memoKind !== 'pameran'" required maxlength="255" class="memo-composer-input">
                            </div>
                            <div>
                                <label for="pameran_loading_in_time" class="memo-composer-label">Jadwal loading in <span class="text-red-600">*</span></label>
                                <input id="pameran_loading_in_time" type="text" name="pameran[loading_in_time]" x-model="pameran.loading_in_time" :disabled="memoKind !== 'pameran'" required maxlength="100" class="memo-composer-input" placeholder="Contoh: Minggu siang, jam 12.30">
                            </div>
                            <div>
                                <label for="pameran_loading_out" class="memo-composer-label">Loading out oleh <span class="text-red-600">*</span></label>
                                <input id="pameran_loading_out" type="text" name="pameran[loading_out]" x-model="pameran.loading_out" :disabled="memoKind !== 'pameran'" required maxlength="255" class="memo-composer-input">
                            </div>
                            <div>
                                <label for="pameran_loading_out_time" class="memo-composer-label">Jadwal loading out <span class="text-red-600">*</span></label>
                                <input id="pameran_loading_out_time" type="text" name="pameran[loading_out_time]" x-model="pameran.loading_out_time" :disabled="memoKind !== 'pameran'" required maxlength="100" class="memo-composer-input" placeholder="Contoh: Minggu sore, jam 16.30">
                            </div>
                            <div>
                                <label for="pameran_sp_tools_pic" class="memo-composer-label">PIC SP Tools <span class="text-red-600">*</span></label>
                                <input id="pameran_sp_tools_pic" type="text" name="pameran[sp_tools_pic]" x-model="pameran.sp_tools_pic" :disabled="memoKind !== 'pameran'" required maxlength="255" class="memo-composer-input">
                            </div>
                            <div>
                                <label for="pameran_cleaning_pic" class="memo-composer-label">PIC kebersihan unit <span class="text-red-600">*</span></label>
                                <input id="pameran_cleaning_pic" type="text" name="pameran[cleaning_pic]" x-model="pameran.cleaning_pic" :disabled="memoKind !== 'pameran'" required maxlength="255" class="memo-composer-input">
                            </div>
                            <div class="sm:col-span-2">
                                <label for="pameran_marketing_programs" class="memo-composer-label">Program marketing <span class="text-red-600">*</span></label>
                                <textarea id="pameran_marketing_programs" name="pameran[marketing_programs]" x-model="pameran.marketing_programs" :disabled="memoKind !== 'pameran'" required maxlength="5000" rows="4" class="memo-composer-input resize-y" placeholder="Tuliskan setiap program pada baris terpisah"></textarea>
                            </div>
                            <div>
                                <label for="pameran_budget_min" class="memo-composer-label">Estimasi biaya minimum (Rp) <span class="text-red-600">*</span></label>
                                <input id="pameran_budget_min" type="number" name="pameran[budget_min]" x-model="pameran.budget_min" :disabled="memoKind !== 'pameran'" required min="0" step="any" class="memo-composer-input" placeholder="350000">
                            </div>
                            <div>
                                <label for="pameran_budget_max" class="memo-composer-label">Estimasi biaya maksimum (Rp) <span class="text-red-600">*</span></label>
                                <input id="pameran_budget_max" type="number" name="pameran[budget_max]" x-model="pameran.budget_max" :disabled="memoKind !== 'pameran'" required min="0.01" step="any" class="memo-composer-input" placeholder="500000">
                            </div>
                            <div>
                                <label for="pameran_prospect_target" class="memo-composer-label">Target prospek (data) <span class="text-red-600">*</span></label>
                                <input id="pameran_prospect_target" type="number" name="pameran[prospect_target]" x-model="pameran.prospect_target" :disabled="memoKind !== 'pameran'" required min="0" step="1" class="memo-composer-input">
                            </div>
                            <div>
                                <label for="pameran_spk_target" class="memo-composer-label">Target SPK <span class="text-red-600">*</span></label>
                                <input id="pameran_spk_target" type="number" name="pameran[spk_target]" x-model="pameran.spk_target" :disabled="memoKind !== 'pameran'" required min="0" step="1" class="memo-composer-input">
                            </div>
                            <div class="sm:col-span-2">
                                <label for="pameran_note" class="memo-composer-label">Catatan</label>
                                <textarea id="pameran_note" name="pameran[note]" x-model="pameran.note" :disabled="memoKind !== 'pameran'" maxlength="2000" rows="2" class="memo-composer-input resize-y" placeholder="Contoh: Team Sales wajib mengikuti SOP Guidance Public Display"></textarea>
                            </div>
                        </div>
                        <p class="mt-3 text-[10px] text-slate-500">Rentang biaya tetap dicetak; batas maksimum dipakai sebagai nilai total untuk approval dan pencairan.</p>
                    </div>

                    <div x-show="memoKind === 'pengajuan'">
                    <div class="mb-3 flex items-start justify-between gap-3">
                        <div class="flex items-start gap-2.5">
                            <span class="grid h-7 w-7 shrink-0 place-items-center rounded-md bg-red-50 text-red-700" aria-hidden="true">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 4h18v16H3zM3 9h18M8 9v11m5-11v11m5-11v11" />
                                </svg>
                            </span>
                            <div>
                                <h2 class="text-sm font-bold text-slate-800">Rincian Pengajuan</h2>
                                <p class="text-[11px] text-slate-500">Tambahkan barang atau kebutuhan yang diajukan.</p>
                            </div>
                        </div>
                        <button type="button" @click="add()" class="shrink-0 rounded-md border border-red-200 px-2.5 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-50">+ item</button>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[610px] text-left text-xs">
                            <thead class="border-y border-slate-100 bg-slate-50 text-[10px] uppercase tracking-wide text-slate-500">
                                <tr>
                                    <th class="w-8 px-2 py-2">No.</th>
                                    <th class="px-2 py-2">Item</th>
                                    <th class="w-20 px-2 py-2">Jumlah</th>
                                    <th class="w-36 px-2 py-2">Harga Satuan (Rp)</th>
                                    <th class="w-36 px-2 py-2 text-right">Subtotal (Rp)</th>
                                    <th class="w-8"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(it, i) in items" :key="i">
                                    <tr class="border-b border-slate-100">
                                        <td class="px-2 py-2 text-slate-400" x-text="i + 1"></td>
                                        <td class="px-2 py-2">
                                            <input type="text" :name="`items[${i}][name]`" x-model="it.name" :disabled="memoKind !== 'pengajuan'" required maxlength="255"
                                                   class="memo-composer-input min-w-36" placeholder="Nama item">
                                        </td>
                                        <td class="px-2 py-2">
                                            <input type="number" min="1" :name="`items[${i}][qty]`" x-model="it.qty" :disabled="memoKind !== 'pengajuan'"
                                                   class="memo-composer-input" placeholder="-">
                                        </td>
                                        <td class="px-2 py-2">
                                            <input type="number" min="0" step="any" :name="`items[${i}][unit_price]`" x-model="it.unit_price" :disabled="memoKind !== 'pengajuan'" required
                                                   class="memo-composer-input" placeholder="0">
                                        </td>
                                        <td class="px-2 py-2 text-right font-medium text-slate-700" x-text="fmt(sub(it))"></td>
                                        <td class="px-2 py-2">
                                            <button type="button" @click="remove(i)" :disabled="items.length === 1"
                                                    class="text-lg leading-none text-slate-400 hover:text-red-600 disabled:cursor-not-allowed disabled:opacity-30"
                                                    aria-label="Hapus item">&times;</button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                            <tfoot>
                                <tr class="font-bold text-slate-800">
                                    <td colspan="4" class="px-2 pt-3 text-right">Total</td>
                                    <td class="px-2 pt-3 text-right" x-text="fmt(total)"></td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <p class="mt-2 text-[10px] text-slate-400">Jumlah boleh dikosongkan (dihitung 1 dan tercetak “-” pada PDF).</p>
                    @error('items')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
                    @error('items.*.name')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
                    @error('items.*.qty')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
                    @error('items.*.unit_price')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                </section>

                <section x-show="memoKind === 'pengajuan'" class="memo-composer-section shadow-sm">
                    <div class="mb-3 flex items-start gap-2.5">
                        <span class="grid h-7 w-7 shrink-0 place-items-center rounded-md bg-red-50 text-red-700" aria-hidden="true">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 7h18M5 7l1.5 13h11L19 7M9 7V5a3 3 0 0 1 6 0v2m-6 5h6" />
                            </svg>
                        </span>
                        <div>
                            <h2 class="text-sm font-bold text-slate-800">Informasi Pembayaran <span class="font-normal text-slate-400">(opsional)</span></h2>
                            <p class="text-[11px] text-slate-500">Isi rekening tujuan jika pengajuan memerlukan transfer.</p>
                        </div>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-3">
                        <div>
                            <label for="bank_name" class="memo-composer-label">Bank</label>
                            <input id="bank_name" type="text" name="bank_name" x-model="bankName" :disabled="memoKind !== 'pengajuan'" value="{{ old('bank_name', $memo?->bank_name) }}"
                                   class="memo-composer-input" placeholder="Contoh: BCA">
                            @error('bank_name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="account_number" class="memo-composer-label">No. rekening</label>
                            <input id="account_number" type="text" name="account_number" x-model="accountNumber" :disabled="memoKind !== 'pengajuan'" value="{{ old('account_number', $memo?->account_number) }}"
                                   class="memo-composer-input" placeholder="Nomor rekening">
                            @error('account_number')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="account_holder" class="memo-composer-label">Atas nama</label>
                            <input id="account_holder" type="text" name="account_holder" x-model="accountHolder" :disabled="memoKind !== 'pengajuan'" value="{{ old('account_holder', $memo?->account_holder) }}"
                                   class="memo-composer-input" placeholder="Nama pemilik rekening">
                            @error('account_holder')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </section>

                <section class="memo-composer-section shadow-sm">
                    <div class="mb-3 flex items-start justify-between gap-3">
                        <div class="flex min-w-0 items-start gap-2.5">
                            <span class="grid h-7 w-7 shrink-0 place-items-center rounded-md bg-red-50 text-red-700" aria-hidden="true">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 6h13M8 12h13M8 18h13M3.5 6h.01M3.5 12h.01M3.5 18h.01" />
                                </svg>
                            </span>
                            <div>
                                <h2 class="text-sm font-bold text-slate-800">Pemberi Persetujuan <span class="text-red-600">*</span></h2>
                                <p class="text-[11px] text-slate-500">Pilih minimal satu user aktif. Urutan pilihan menentukan urutan approval.</p>
                            </div>
                        </div>
                        @if($approvers->isNotEmpty())
                            <button type="button"
                                    @click="addApprover()"
                                    class="inline-flex shrink-0 items-center gap-1 rounded-md border border-red-200 px-2.5 py-1.5 text-[10px] font-semibold text-red-700 transition hover:bg-red-50 sm:px-3 sm:text-[11px]">
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14m-7-7h14"/>
                                </svg>
                                <span>approver</span>
                            </button>
                        @endif
                    </div>

                    <input type="hidden" name="approver_ids_present" value="1">
                    <template x-for="(row, index) in approverRows" :key="index">
                        <template x-if="row.id">
                            <input type="hidden" name="approver_ids[]" :value="row.id">
                        </template>
                    </template>

                    <div class="mb-3 rounded-lg border border-blue-100 bg-blue-50/70 px-3 py-2 text-[10px] leading-relaxed text-blue-800">
                        Approver berikutnya menerima email setelah approver sebelumnya menyetujui. Jika ada penolakan, proses memo langsung berhenti.
                    </div>

                    @if($approvers->isEmpty())
                        <p class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800">
                            Belum ada user aktif yang dapat dipilih sebagai approver.
                        </p>
                    @else
                        <div class="space-y-2.5">
                            <template x-for="(row, index) in approverRows" :key="index">
                                <div class="flex items-start gap-2">
                                    <span x-text="index + 1" class="mt-2.5 grid h-6 w-6 shrink-0 place-items-center rounded-full bg-red-50 text-[10px] font-bold text-red-700"></span>
                                    <div class="relative min-w-0 flex-1">
                                        <input type="text"
                                               :value="row.query"
                                               @input="setApproverQuery(index, $event.target.value)"
                                               @focus="row.open = true"
                                               @click.away="row.open = false"
                                               @keydown.escape="row.open = false"
                                               autocomplete="off"
                                               :aria-expanded="row.open"
                                               aria-label="Cari user pemberi persetujuan"
                                               placeholder="Ketik nama, jabatan, atau role approver..."
                                               class="memo-composer-input">
                                        <div x-cloak x-show="row.open"
                                             class="absolute z-20 mt-1 max-h-56 w-full overflow-y-auto rounded-lg border border-slate-200 bg-white py-1 shadow-lg">
                                            <template x-for="approver in filteredApprovers(index)" :key="approver.id">
                                                <button type="button"
                                                        @click="selectApprover(index, approver)"
                                                        class="flex w-full items-start justify-between gap-3 px-3 py-2 text-left transition hover:bg-red-50">
                                                    <span class="min-w-0">
                                                        <span x-text="approver.name" class="block truncate text-xs font-semibold text-slate-700"></span>
                                                        <span class="mt-0.5 block truncate text-[10px] text-slate-500" x-text="[approver.role, approver.jabatan].filter(Boolean).join(' · ')"></span>
                                                    </span>
                                                    <svg class="mt-0.5 h-4 w-4 shrink-0 text-red-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="m5 12 4 4L19 6"/>
                                                    </svg>
                                                </button>
                                            </template>
                                            <p x-show="filteredApprovers(index).length === 0" class="px-3 py-2 text-xs text-slate-400">
                                                Tidak ada user yang cocok.
                                            </p>
                                        </div>
                                    </div>
                                    <button type="button"
                                            @click="removeApprover(index)"
                                            :disabled="approverRows.length === 1"
                                            aria-label="Hapus approver"
                                            class="mt-1 flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-200 text-slate-400 transition hover:border-red-200 hover:bg-red-50 hover:text-red-600 disabled:cursor-not-allowed disabled:opacity-40">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 7h14m-12 0 1 14h8l1-14M9 7V4h6v3m-5 4v6m4-6v6"/>
                                        </svg>
                                    </button>
                                </div>
                            </template>
                        </div>
                    @endif
                    @error('approver_ids')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
                    @if($errors->has('approver_ids.*'))
                        <p class="mt-2 text-xs text-red-600">{{ $errors->first('approver_ids.*') }}</p>
                    @endif
                </section>

                <section class="memo-composer-section shadow-sm">
                    <div class="mb-3 flex items-start gap-2.5">
                        <span class="grid h-7 w-7 shrink-0 place-items-center rounded-md bg-red-50 text-red-700" aria-hidden="true">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m8 12.5 6.8-6.8a3.2 3.2 0 0 1 4.5 4.5l-9.2 9.2a5 5 0 0 1-7.1-7.1l9.2-9.2" />
                            </svg>
                        </span>
                        <div>
                            <h2 class="text-sm font-bold text-slate-800">Lampiran Pendukung</h2>
                            <p class="text-[11px] text-slate-500">PDF/JPG/PNG/Office, maksimal 5 MB per file.</p>
                        </div>
                    </div>
                    <label for="attachments" class="relative block cursor-pointer rounded-lg border border-dashed border-slate-300 bg-slate-50 px-4 py-5 text-center transition hover:border-red-300 hover:bg-red-50/40">
                        <svg class="mx-auto mb-2 h-8 w-8 text-red-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 16V4m0 0L7.5 8.5M12 4l4.5 4.5M4 15.5v3A1.5 1.5 0 0 0 5.5 20h13a1.5 1.5 0 0 0 1.5-1.5v-3" />
                        </svg>
                        <span class="mb-1 block text-sm font-semibold text-slate-700" x-text="attachmentNames.length ? 'File siap diunggah' : 'Pilih file untuk diunggah'"></span>
                        <span class="block text-[11px] text-slate-500" x-text="attachmentNames.length ? attachmentNames.join(', ') : 'Bisa memilih beberapa file sekaligus.'"></span>
                        <input id="attachments" type="file" name="attachments[]" multiple
                               accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx"
                               @change="attachmentNames = Array.from($event.target.files).map(file => file.name)"
                               class="absolute inset-0 h-full w-full cursor-pointer opacity-0">
                    </label>
                    @error('attachments')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
                    @error('attachments.*')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
                    @if($memo && $memo->attachments->where('type', 'supporting')->isNotEmpty())
                        <div class="mt-3 space-y-2 border-t border-slate-100 pt-3 text-xs">
                            @foreach($memo->attachments->where('type', 'supporting') as $attachment)
                                <label class="flex items-center gap-2 text-slate-600">
                                    <input type="checkbox" name="remove_attachments[]" value="{{ $attachment->id }}" class="rounded border-slate-300 text-red-600 focus:ring-red-500">
                                    Hapus lampiran: {{ $attachment->original_name }}
                                </label>
                            @endforeach
                        </div>
                    @endif
                </section>

                <div class="memo-composer-actions flex flex-wrap items-center justify-between gap-3 rounded-lg border border-slate-200 bg-white p-3 shadow-sm">
                    <a href="{{ $memo ? route('memos.show', $memo) : route('memos.index') }}"
                       class="rounded-md px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100">Batal</a>
                    <div class="flex flex-wrap gap-2">
                        @if($isPendingEdit)
                            <button name="action" value="draft" class="rounded-md bg-red-700 px-4 py-2 text-xs font-semibold text-white hover:bg-red-800">Simpan Perubahan</button>
                        @else
                            <button name="action" value="submit" class="rounded-md bg-red-700 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-red-800">
                                {{ $isRevision ? 'Simpan & Ajukan Ulang' : 'Simpan & Ajukan' }}
                            </button>
                        @endif
                    </div>
                </div>
            </form>

        <aside>
            <div class="memo-composer-preview-card overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-4 py-3">
                    <div class="flex items-center gap-2">
                        <span class="grid h-7 w-7 place-items-center rounded-md bg-red-50 text-red-700" aria-hidden="true">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.5 12s3.4-6 9.5-6 9.5 6 9.5 6-3.4 6-9.5 6-9.5-6-9.5-6Z" />
                                <circle cx="12" cy="12" r="2.5" />
                            </svg>
                        </span>
                        <h2 class="text-sm font-bold text-slate-800">Preview Memo</h2>
                    </div>
                    <p class="mt-0.5 text-[11px] text-slate-500">Pratinjau langsung mengikuti format utama PDF.</p>
                </div>
                <article class="memo-composer-paper">
                    <table class="memo-composer-paper-header">
                        <tr>
                            <td><img src="{{ asset('assets/images/Logo-TAG.png') }}" alt="PT Tunas Auto Graha" class="memo-composer-paper-logo"></td>
                            <td class="text-right font-bold" x-text="'Divisi ' + (divisionName || '—')"></td>
                        </tr>
                    </table>

                    <div class="memo-composer-paper-title">
                        <div><strong>INTERNAL MEMO</strong></div>
                        <div class="font-bold">No : {{ $memo?->memo_number ?? 'DRAFT' }}</div>
                    </div>

                    <p>Kepada Yth,<br>Direksi PT. Tunas Auto Graha<br>Di. Tempat</p>
                    <p class="mt-3"><strong>Hal :</strong> <span x-text="subject || 'Judul memo'"></span></p>
                    <p class="mt-3">Dengan Hormat,</p>
                    <p class="mt-1 whitespace-pre-line text-justify" x-text="openingParagraph || 'Paragraf pembuka memo akan tampil di sini.'"></p>

                    <table x-show="memoKind === 'pengajuan'" class="memo-composer-paper-items">
                        <thead>
                            <tr><th style="width: 20px">No</th><th>Item</th><th style="width: 34px">Jumlah</th><th style="width: 66px">Harga</th><th style="width: 68px">Total</th></tr>
                        </thead>
                        <tbody>
                            <template x-for="(it, i) in items" :key="i">
                                <tr>
                                    <td class="text-center" x-text="i + 1"></td>
                                    <td x-text="it.name || 'Nama item'"></td>
                                    <td class="text-center" x-text="it.qty || '-'"></td>
                                    <td class="text-right" x-text="rupiah(it.unit_price)"></td>
                                    <td class="text-right" x-text="rupiah(sub(it))"></td>
                                </tr>
                            </template>
                            <tr><td colspan="4" class="text-right"><strong>Total</strong></td><td class="text-right"><strong x-text="rupiah(total)"></strong></td></tr>
                            <tr><td colspan="5" class="text-center"><strong>Terbilang: <span x-text="terbilang(total)"></span></strong></td></tr>
                        </tbody>
                    </table>
                    <table x-show="memoKind === 'pameran'" class="memo-composer-paper-items">
                        <thead>
                            <tr><th>Item</th><th>Keterangan</th><th>Ketentuan</th></tr>
                        </thead>
                        <tbody>
                            <tr><td>Lokasi</td><td colspan="2" x-text="pameran.location || '—'"></td></tr>
                            <tr><td>Periode</td><td x-text="pameranDateLabel()"></td><td x-text="pameran.duration || '—'"></td></tr>
                            <tr><td>Unit Display</td><td colspan="2" x-text="pameran.display_unit || '—'"></td></tr>
                            <tr><td>SP Tools</td><td colspan="2" x-text="pameran.sp_tools || '—'"></td></tr>
                            <tr>
                                <td>PIC</td>
                                <td colspan="2" class="whitespace-pre-line" x-text="`PIC Unit Display: ${pameran.display_pic || '—'}\nLoading in: ${pameran.loading_in || '—'} (${pameran.loading_in_time || '—'})\nLoading out: ${pameran.loading_out || '—'} (${pameran.loading_out_time || '—'})\nPIC SP Tools: ${pameran.sp_tools_pic || '—'}\nPIC Kebersihan Unit: ${pameran.cleaning_pic || '—'}`"></td>
                            </tr>
                            <tr><td>Program Marketing</td><td colspan="2" class="whitespace-pre-line" x-text="pameran.marketing_programs || '—'"></td></tr>
                            <tr><td>Biaya</td><td colspan="2" x-text="`${rupiah(pameran.budget_min)} - ${rupiah(pameran.budget_max)}`"></td></tr>
                            <tr><td>Target</td><td colspan="2" x-text="`Prospect: ${pameran.prospect_target || 0} data\nSPK: ${pameran.spk_target || 0} SPK`"></td></tr>
                        </tbody>
                    </table>
                    <p x-show="memoKind === 'pameran' && pameran.note" class="whitespace-pre-line"><strong>Note:</strong> <span x-text="pameran.note"></span></p>
                    <p class="text-justify">Demikianlah surat pengajuan ini kami sampaikan, atas perhatian dan persetujuannya kami ucapkan terima kasih.</p>

                    <div x-show="bankName" class="mt-3">
                        <p><u><strong>Note:</strong></u><br>
                            <strong>Pembayaran dapat dilakukan dengan cara transfer ke rekening
                                <span x-text="bankName"></span>
                                <span x-show="accountNumber" x-text="accountNumber"></span>
                                an <span x-text="accountHolder || '—'"></span>
                            </strong>
                        </p>
                    </div>

                    <p class="mt-3" x-text="(branchCity ? branchCity + ', ' : '') + dateLabel()"></p>
                    <div class="memo-composer-paper-sign">
                        <p>Hormat saya,</p>
                        <div class="my-8"></div>
                        <p class="font-bold underline">{{ $requester->name }}</p>
                        <p>{{ $requester->jabatan }}</p>
                    </div>
                </article>
            </div>
            <section class="memo-composer-guidance rounded-xl border border-slate-200 bg-white shadow-sm" aria-labelledby="memo-important-title">
                <h2 id="memo-important-title" class="mb-3 flex items-center gap-2 text-sm font-bold text-slate-800">
                    <svg class="h-4 w-4 shrink-0 text-blue-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <circle cx="12" cy="12" r="9" />
                        <path stroke-linecap="round" d="M12 11v5m0-8h.01" />
                    </svg>
                    Informasi Penting
                </h2>
                <div class="rounded-lg border border-blue-100 bg-blue-50 px-3 py-2.5 text-[11px] leading-relaxed text-blue-900">
                    Pastikan semua data yang diisi sudah benar sebelum mengirim memo. Setelah dikirim, status pengajuan dapat dipantau di menu Semua Memo.
                </div>
            </section>
        </aside>
    </div>
</div>
@endsection
