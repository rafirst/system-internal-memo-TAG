@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto py-8 px-4">
    <h1 class="text-2xl font-semibold text-gray-800 mb-6">Ajukan Internal Memo Baru</h1>

    @if ($errors->any())
        <div class="mb-4 rounded-lg bg-red-50 border border-red-200 p-4 text-sm text-red-700">
            <ul class="list-disc pl-5 space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('memos.store') }}" method="POST" enctype="multipart/form-data" class="space-y-5 bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
        @csrf

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Jenis Memo</label>
            <select name="memo_type_id" required
                class="w-full rounded-lg border-gray-300 focus:ring-red-500 focus:border-red-500 text-sm">
                <option value="">-- Pilih jenis pengajuan --</option>
                @foreach ($memoTypes as $type)
                    <option value="{{ $type->id }}" @selected(old('memo_type_id') == $type->id)>
                        {{ $type->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Judul</label>
            <input type="text" name="title" required value="{{ old('title') }}"
                placeholder="Contoh: Permintaan Laptop untuk Staff Baru Divisi Finance"
                class="w-full rounded-lg border-gray-300 focus:ring-red-500 focus:border-red-500 text-sm">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Deskripsi / Detail Kebutuhan</label>
            <textarea name="description" rows="5" required
                class="w-full rounded-lg border-gray-300 focus:ring-red-500 focus:border-red-500 text-sm">{{ old('description') }}</textarea>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Estimasi Biaya (opsional)</label>
            <input type="number" step="0.01" min="0" name="estimated_amount" value="{{ old('estimated_amount') }}"
                placeholder="0"
                class="w-full rounded-lg border-gray-300 focus:ring-red-500 focus:border-red-500 text-sm">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Lampiran (nota, gambar, dll)</label>
            <input type="file" name="attachments[]" multiple
                class="w-full text-sm text-gray-600 file:mr-4 file:rounded-lg file:border-0 file:bg-red-50 file:px-4 file:py-2 file:text-red-700 hover:file:bg-red-100">
            <p class="mt-1 text-xs text-gray-400">Maks 10MB per file. Bisa upload lebih dari satu.</p>
        </div>

        <div class="pt-2 flex justify-end gap-3">
            <a href="{{ route('memos.index') }}" class="px-4 py-2 text-sm rounded-lg text-gray-600 hover:bg-gray-100">Batal</a>
            <button type="submit" class="px-5 py-2 text-sm rounded-lg bg-red-600 text-white font-medium hover:bg-red-700">
                Ajukan Memo
            </button>
        </div>
    </form>
</div>
@endsection
