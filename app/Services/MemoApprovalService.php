<?php

namespace App\Services;

use App\Models\MemoApproval;
use App\Models\MemoRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MemoApprovalService
{
    /**
     * Ajukan memo (submit) -> generate baris memo_approvals untuk SEMUA step
     * di flow terkait, semua berstatus 'pending', dan set current_step = 1.
     */
    public function submit(MemoRequest $memo): MemoRequest
    {
        return DB::transaction(function () use ($memo) {
            $steps = $memo->flow()->first()->steps; // sudah urut step_order asc

            if ($steps->isEmpty()) {
                throw ValidationException::withMessages([
                    'flow' => 'Alur approval untuk jenis memo ini belum diatur. Hubungi admin.',
                ]);
            }

            foreach ($steps as $step) {
                MemoApproval::create([
                    'memo_request_id' => $memo->id,
                    'approval_step_id' => $step->id,
                    'step_order' => $step->step_order,
                    'role_id' => $step->role_id,
                    'status' => 'pending',
                ]);
            }

            $memo->update([
                'current_step' => 1,
                'status' => 'submitted',
                'submitted_at' => now(),
            ]);

            return $memo->fresh();
        });
    }

    /**
     * Approve step yang sedang berjalan.
     * KUNCI ATURAN: user hanya boleh approve jika
     *  1. step_order yang di-approve == memo->current_step (tidak boleh lompat)
     *  2. role user cocok dengan role yang ditugaskan pada step tsb
     */
    public function approve(MemoRequest $memo, User $user, ?string $notes = null): MemoRequest
    {
        return DB::transaction(function () use ($memo, $user, $notes) {
            $approval = $this->validateActionable($memo, $user);

            $approval->update([
                'status' => 'approved',
                'approver_id' => $user->id,
                'notes' => $notes,
                'acted_at' => now(),
            ]);

            $totalSteps = $memo->flow()->first()->steps()->count();
            $nextStep = $memo->current_step + 1;

            if ($nextStep > $totalSteps) {
                // semua step sudah approve -> memo selesai disetujui
                $memo->update([
                    'status' => 'approved',
                    'completed_at' => now(),
                ]);
            } else {
                // maju ke step berikutnya, TIDAK bisa skip
                $memo->update([
                    'current_step' => $nextStep,
                    'status' => 'in_progress',
                ]);
            }

            return $memo->fresh();
        });
    }

    /**
     * Reject step yang sedang berjalan -> proses berhenti total.
     */
    public function reject(MemoRequest $memo, User $user, string $notes): MemoRequest
    {
        return DB::transaction(function () use ($memo, $user, $notes) {
            $approval = $this->validateActionable($memo, $user);

            $approval->update([
                'status' => 'rejected',
                'approver_id' => $user->id,
                'notes' => $notes,
                'acted_at' => now(),
            ]);

            $memo->update([
                'status' => 'rejected',
                'completed_at' => now(),
            ]);

            return $memo->fresh();
        });
    }

    /**
     * Minta revisi ke pengaju -> memo balik ke status 'revision',
     * approval history step ini ditandai revision, step lain di-reset
     * supaya saat diajukan ulang mulai dari step 1 lagi.
     */
    public function requestRevision(MemoRequest $memo, User $user, string $notes): MemoRequest
    {
        return DB::transaction(function () use ($memo, $user, $notes) {
            $approval = $this->validateActionable($memo, $user);

            $approval->update([
                'status' => 'revision',
                'approver_id' => $user->id,
                'notes' => $notes,
                'acted_at' => now(),
            ]);

            $memo->update(['status' => 'revision']);

            return $memo->fresh();
        });
    }

    /**
     * Pengaju submit ulang memo yang statusnya 'revision'.
     * Reset semua approval jadi pending lagi dan mulai dari step 1.
     */
    public function resubmit(MemoRequest $memo): MemoRequest
    {
        return DB::transaction(function () use ($memo) {
            $memo->approvals()->update([
                'status' => 'pending',
                'approver_id' => null,
                'notes' => null,
                'acted_at' => null,
            ]);

            $memo->update([
                'current_step' => 1,
                'status' => 'submitted',
                'submitted_at' => now(),
            ]);

            return $memo->fresh();
        });
    }

    /**
     * Validasi inti: pastikan step yang mau di-action itu memang
     * step aktif (current_step) dan role user memang berwenang.
     * Ini mencegah "approve step 3 sebelum 1 & 2".
     */
    private function validateActionable(MemoRequest $memo, User $user): MemoApproval
    {
        if (in_array($memo->status, ['approved', 'rejected'])) {
            throw ValidationException::withMessages([
                'status' => 'Memo ini sudah selesai diproses dan tidak bisa diubah lagi.',
            ]);
        }

        $approval = $memo->approvals()
            ->where('step_order', $memo->current_step) // HANYA step aktif
            ->first();

        if (! $approval) {
            throw ValidationException::withMessages([
                'step' => 'Step approval aktif tidak ditemukan.',
            ]);
        }

        if (! $user->role || $user->role_id !== $approval->role_id) {
            throw ValidationException::withMessages([
                'role' => 'Anda tidak berwenang melakukan approval pada step ini.',
            ]);
        }

        return $approval;
    }
}
