<?php

namespace App\Http\Requests;

use App\Enums\MemoStatus;
use App\Models\Role;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MemoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // otorisasi ditangani Gate di controller
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('memo_kind')) {
            $this->merge(['memo_kind' => 'pengajuan']);
        }
    }

    public function rules(): array
    {
        $memo = $this->route('memo');

        return [
            'division_id' => [
                Rule::requiredIf(fn (): bool => $this->user()?->hasRole('administrator') && $this->isMethod('post')),
                'nullable',
                'exists:divisions,id',
            ],
            'branch_id' => [
                Rule::requiredIf(fn (): bool => $this->user()?->hasRole('administrator') && $this->isMethod('post')),
                'nullable',
                'exists:branches,id',
            ],
            'memo_kind' => ['required', 'string', 'in:pengajuan,pameran'],
            'subject' => ['required', 'string', 'max:255'],
            'opening_paragraph' => ['required', 'string'],
            'memo_date' => ['required', 'date'],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'account_number' => ['nullable', 'string', 'max:100'],
            'account_holder' => ['nullable', 'string', 'max:150'],
            'items' => ['exclude_if:memo_kind,pameran', 'required', 'array', 'min:1'],
            'items.*.name' => ['required', 'string', 'max:255'],
            'items.*.qty' => ['nullable', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'pameran' => ['exclude_unless:memo_kind,pameran', 'required', 'array'],
            'pameran.location' => ['exclude_unless:memo_kind,pameran', 'required', 'string', 'max:255'],
            'pameran.event_date' => ['exclude_unless:memo_kind,pameran', 'required', 'date'],
            'pameran.duration' => ['exclude_unless:memo_kind,pameran', 'required', 'string', 'max:100'],
            'pameran.display_unit' => ['exclude_unless:memo_kind,pameran', 'required', 'string', 'max:255'],
            'pameran.sp_tools' => ['exclude_unless:memo_kind,pameran', 'required', 'string', 'max:1000'],
            'pameran.display_pic' => ['exclude_unless:memo_kind,pameran', 'required', 'string', 'max:255'],
            'pameran.loading_in' => ['exclude_unless:memo_kind,pameran', 'required', 'string', 'max:255'],
            'pameran.loading_in_time' => ['exclude_unless:memo_kind,pameran', 'required', 'string', 'max:100'],
            'pameran.loading_out' => ['exclude_unless:memo_kind,pameran', 'required', 'string', 'max:255'],
            'pameran.loading_out_time' => ['exclude_unless:memo_kind,pameran', 'required', 'string', 'max:100'],
            'pameran.sp_tools_pic' => ['exclude_unless:memo_kind,pameran', 'required', 'string', 'max:255'],
            'pameran.cleaning_pic' => ['exclude_unless:memo_kind,pameran', 'required', 'string', 'max:255'],
            'pameran.marketing_programs' => ['exclude_unless:memo_kind,pameran', 'required', 'string', 'max:5000'],
            'pameran.budget_min' => ['exclude_unless:memo_kind,pameran', 'required', 'numeric', 'min:0', 'max:9999999999999.99'],
            'pameran.budget_max' => ['exclude_unless:memo_kind,pameran', 'required', 'numeric', 'min:0.01', 'gte:pameran.budget_min', 'max:9999999999999.99'],
            'pameran.prospect_target' => ['exclude_unless:memo_kind,pameran', 'required', 'integer', 'min:0', 'max:999999'],
            'pameran.spk_target' => ['exclude_unless:memo_kind,pameran', 'required', 'integer', 'min:0', 'max:999999'],
            'pameran.note' => ['exclude_unless:memo_kind,pameran', 'nullable', 'string', 'max:2000'],
            'attachments' => ['nullable', 'array', 'max:10'],
            'attachments.*' => ['file', 'mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx', 'max:5120'],
            'approver_ids' => [
                Rule::requiredIf(fn (): bool => $this->input('action') === 'submit'
                    || ($memo && $memo->status === MemoStatus::Pending)),
                'array',
                'min:1',
            ],
            'approver_ids.*' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('users', 'id')->where(fn (Builder $query) => $query
                    ->where('is_active', true)
                    ->whereNotNull('role_id')
                    ->where('id', '!=', $this->user()?->id)
                    ->whereNotIn('role_id', Role::query()->select('id')->where('slug', 'staff'))),
            ],
            'remove_attachments' => ['nullable', 'array'],
            'remove_attachments.*' => ['integer'],
            'action' => ['nullable', 'in:draft,submit'],
        ];
    }

    public function attributes(): array
    {
        return [
            'subject' => 'Hal',
            'opening_paragraph' => 'Paragraf pembuka',
            'memo_date' => 'Tanggal memo',
            'items.*.name' => 'Nama item',
            'items.*.qty' => 'Jumlah',
            'items.*.unit_price' => 'Harga',
            'pameran.location' => 'Lokasi pameran',
            'pameran.event_date' => 'Tanggal pameran',
            'pameran.duration' => 'Durasi pameran',
            'pameran.display_unit' => 'Unit display',
            'pameran.sp_tools' => 'SP Tools',
            'pameran.display_pic' => 'PIC unit display',
            'pameran.loading_in' => 'PIC loading in',
            'pameran.loading_in_time' => 'Jadwal loading in',
            'pameran.loading_out' => 'PIC loading out',
            'pameran.loading_out_time' => 'Jadwal loading out',
            'pameran.sp_tools_pic' => 'PIC SP Tools',
            'pameran.cleaning_pic' => 'PIC kebersihan unit',
            'pameran.marketing_programs' => 'Program marketing',
            'pameran.budget_min' => 'Batas bawah biaya',
            'pameran.budget_max' => 'Batas atas biaya',
            'pameran.prospect_target' => 'Target prospek',
            'pameran.spk_target' => 'Target SPK',
            'pameran.note' => 'Catatan',
            'attachments.*' => 'Lampiran',
            'approver_ids' => 'Pemberi Persetujuan',
            'approver_ids.*' => 'Pemberi Persetujuan',
        ];
    }

    public function messages(): array
    {
        return [
            'approver_ids.required' => 'Pilih minimal satu Pemberi Persetujuan.',
            'approver_ids.min' => 'Pilih minimal satu Pemberi Persetujuan.',
            'approver_ids.*.exists' => 'Pemberi Persetujuan harus user aktif, bukan Staff, dan bukan pengaju.',
            'approver_ids.*.distinct' => 'Pemberi Persetujuan tidak boleh dipilih lebih dari sekali.',
        ];
    }
}
