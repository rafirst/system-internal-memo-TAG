<div class="overflow-x-auto">
    <table class="memo-index-table w-full min-w-[900px] text-left text-xs">
        <thead>
            <tr>
                <th class="px-4 py-3">No.</th>
                <th class="px-3 py-3">No. Memo</th>
                <th class="px-3 py-3">Judul Memo</th>
                <th class="px-3 py-3">Divisi</th>
                <th class="px-3 py-3">Pengaju</th>
                <th class="px-3 py-3">Tanggal</th>
                <th class="px-3 py-3">Status</th>
                <th class="px-3 py-3 text-center">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($memos as $memo)
                <tr class="transition hover:bg-slate-50">
                    <td class="px-4 py-3 text-slate-500">{{ $memos->firstItem() + $loop->index }}</td>
                    <td class="whitespace-nowrap px-3 py-3 font-medium text-slate-600">{{ $memo->memo_number ?? 'Draft' }}</td>
                    <td class="max-w-[260px] px-3 py-3">
                        <a href="{{ route('memos.show', $memo) }}" class="block truncate font-semibold text-slate-700 hover:text-rose-700" title="{{ $memo->subject }}">
                            {{ \Illuminate\Support\Str::limit($memo->subject, 60) }}
                        </a>
                        <span class="mt-1 inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-[9px] font-semibold text-slate-600">{{ $memo->memo_kind === 'pameran' ? 'Pameran' : 'Pengajuan' }}</span>
                        <span class="mt-1 block text-[10px] text-slate-400">{{ \App\Support\Format::rupiah($memo->total_amount) }}</span>
                    </td>
                    <td class="whitespace-nowrap px-3 py-3 text-slate-600">{{ $memo->division?->name ?? '—' }}</td>
                    <td class="whitespace-nowrap px-3 py-3 text-slate-600">
                        {{ $memo->submitter?->name ?? '—' }}
                        @if($memo->onBehalfOf)
                            <span class="mt-1 block text-[10px] text-slate-400">a.n. {{ $memo->onBehalfOf->name }}</span>
                        @endif
                    </td>
                    <td class="whitespace-nowrap px-3 py-3 text-slate-500">{{ $memo->memo_date?->format('d M Y') ?? '—' }}</td>
                    <td class="px-3 py-3"><x-status-badge :status="$memo->status" /></td>
                    <td class="px-3 py-3 text-center">
                        <a href="{{ route('memos.show', $memo) }}" aria-label="Buka memo {{ $memo->memo_number ?? $memo->subject }}" title="Buka memo" class="inline-flex h-7 w-7 items-center justify-center rounded-md text-slate-500 transition hover:bg-rose-50 hover:text-rose-700">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="5" cy="12" r="1.5"/><circle cx="12" cy="12" r="1.5"/><circle cx="19" cy="12" r="1.5"/></svg>
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="px-4 py-12 text-center">
                        <span class="mx-auto mb-2 flex h-10 w-10 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M7 3h7l5 5v13H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M14 3v5h5"/></svg>
                        </span>
                        <span class="block font-semibold text-slate-600">{{ $empty ?? 'Belum ada memo yang sesuai.' }}</span>
                        <span class="mt-1 block text-[11px] text-slate-400">Coba ubah kata kunci atau filter yang digunakan.</span>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
