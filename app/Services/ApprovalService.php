<?php

namespace App\Services;

use App\Enums\ApprovalStatus;
use App\Enums\MemoStatus;
use App\Models\Memo;
use App\Models\MemoApproval;
use App\Models\MemoAttachment;
use App\Models\User;
use App\Notifications\MemoNotification;
use DomainException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

/**
 * Seluruh aturan alur memo ada di sini:
 * submit -> approve/deny (berurutan) -> disburse -> complete.
 * Semua pelanggaran aturan dilempar sebagai DomainException (pesan siap tampil ke user).
 */
class ApprovalService
{
    public function __construct(private MemoNumberGenerator $numbers) {}

    // ------------------------------------------------------------------ SUBMIT

    /** Ajukan memo (dari Draft) atau ajukan ulang (dari Denied -> ronde baru). */
    public function submit(Memo $memo, User $actor): Memo
    {
        $memo = DB::transaction(function () use ($memo, $actor) {
            $memo = Memo::with(['items', 'submitter', 'division'])->lockForUpdate()->findOrFail($memo->id);

            if ($memo->submitter_id !== $actor->id) {
                throw new DomainException('Hanya pengaju yang dapat mengajukan memo ini.');
            }
            if (! in_array($memo->status, [MemoStatus::Draft, MemoStatus::Denied], true)) {
                throw new DomainException('Memo ini tidak dapat diajukan pada status saat ini.');
            }
            if ($memo->items->isEmpty() || (float) $memo->total_amount <= 0) {
                throw new DomainException('Rincian item belum lengkap atau total masih 0.');
            }

            $approvers = $this->selectedApprovers($memo);
            $round = $memo->status === MemoStatus::Denied ? $memo->round + 1 : $memo->round;

            // Nomor dibuat sekali saja, saat pertama kali diajukan (tidak berubah saat revisi).
            if (! $memo->memo_number) {
                $memo->memo_number = $this->numbers->generate($memo->division, $memo->memo_date);
            }

            foreach ($approvers as $index => $approver) {
                MemoApproval::create([
                    'memo_id' => $memo->id,
                    'round' => $round,
                    'step_order' => $index + 1,
                    'assigned_approver_id' => $approver->id,
                    'approver_role_id' => $approver->role_id,
                    'status' => $index === 0 ? ApprovalStatus::Pending : ApprovalStatus::Waiting,
                ]);
            }

            $memo->update([
                'memo_number' => $memo->memo_number,
                'status' => MemoStatus::Pending,
                'current_step' => 1,
                'round' => $round,
                'submitted_at' => now(),
                'approved_at' => null,
            ]);

            return $memo;
        });

        $this->notifyCurrentApprovers($memo);

        return $memo;
    }

    /**
     * Rebuild an unstarted pending sequence when the submitter changes approvers.
     */
    public function syncPendingApprovers(Memo $memo): bool
    {
        return DB::transaction(function () use ($memo) {
            $memo = Memo::lockForUpdate()->findOrFail($memo->id);

            if ($memo->status !== MemoStatus::Pending) {
                throw new DomainException('Approver hanya dapat diubah sebelum persetujuan pertama.');
            }

            if ($memo->currentApprovals()->whereNotIn('status', [
                ApprovalStatus::Pending->value,
                ApprovalStatus::Waiting->value,
            ])->exists()) {
                throw new DomainException('Urutan approver tidak dapat diubah setelah approval dimulai.');
            }

            $approvers = $this->selectedApprovers($memo);
            $currentApproverIds = $memo->currentApprovals()
                ->orderBy('step_order')
                ->pluck('assigned_approver_id')
                ->map(fn ($id) => (int) $id)
                ->all();
            $selectedApproverIds = $approvers->pluck('id')->map(fn ($id) => (int) $id)->all();

            if ($currentApproverIds === $selectedApproverIds) {
                return false;
            }

            $memo->currentApprovals()->delete();

            foreach ($approvers as $index => $approver) {
                MemoApproval::create([
                    'memo_id' => $memo->id,
                    'round' => $memo->round,
                    'step_order' => $index + 1,
                    'assigned_approver_id' => $approver->id,
                    'approver_role_id' => $approver->role_id,
                    'status' => $index === 0 ? ApprovalStatus::Pending : ApprovalStatus::Waiting,
                ]);
            }

            $memo->update(['current_step' => 1]);

            return true;
        });
    }

    // ------------------------------------------------------- APPROVE / DENY

    public function approve(Memo $memo, User $actor, ?string $comment = null): Memo
    {
        $memo = DB::transaction(function () use ($memo, $actor, $comment) {
            $memo = Memo::lockForUpdate()->findOrFail($memo->id);
            $approval = $this->pendingApprovalFor($memo, $actor);

            $approval->update([
                'status' => ApprovalStatus::Approved,
                'acted_by' => $actor->id,
                'comment' => $comment,
                'acted_at' => now(),
            ]);

            $next = MemoApproval::where('memo_id', $memo->id)
                ->where('round', $memo->round)
                ->where('step_order', '>', $approval->step_order)
                ->orderBy('step_order')
                ->first();

            if ($next) {
                $next->update(['status' => ApprovalStatus::Pending]);
                $memo->update(['current_step' => $next->step_order]);
            } else {
                $memo->update([
                    'status' => MemoStatus::Approved,
                    'current_step' => null,
                    'approved_at' => now(),
                ]);
            }

            return $memo;
        });

        if ($memo->status === MemoStatus::Approved) {
            $this->send(
                $this->recipients($memo),
                $memo,
                'Memo disetujui seluruhnya',
                "Memo {$memo->memo_number} telah disetujui seluruhnya dan menunggu pencairan dana."
            );
            $this->send(
                $this->usersWithRole('admin_keuangan'),
                $memo,
                'Memo siap dicairkan',
                "Memo {$memo->memo_number} sudah disetujui seluruhnya dan siap untuk pencairan dana."
            );
        } else {
            $this->sendInApp(
                $this->recipients($memo)->reject(fn (User $recipient) => $recipient->is($actor)),
                $memo,
                'Persetujuan memo diperbarui',
                "Memo {$memo->memo_number} telah disetujui oleh {$actor->name} dan diteruskan ke tahap persetujuan berikutnya."
            );
            $this->notifyCurrentApprovers($memo);
        }

        return $memo;
    }

    public function deny(Memo $memo, User $actor, ?string $comment): Memo
    {
        if (blank($comment)) {
            throw new DomainException('Alasan penolakan wajib diisi.');
        }

        $memo = DB::transaction(function () use ($memo, $actor, $comment) {
            $memo = Memo::lockForUpdate()->findOrFail($memo->id);
            $approval = $this->pendingApprovalFor($memo, $actor);

            $approval->update([
                'status' => ApprovalStatus::Denied,
                'acted_by' => $actor->id,
                'comment' => $comment,
                'acted_at' => now(),
            ]);

            // Langkah setelahnya tetap 'waiting' di ronde ini sebagai riwayat.
            $memo->update(['status' => MemoStatus::Denied, 'current_step' => null]);

            return $memo;
        });

        $this->send(
            $this->recipients($memo),
            $memo,
            'Memo ditolak',
            "Memo {$memo->memo_number} ditolak oleh {$actor->name}. Alasan: {$comment}. Silakan revisi lalu ajukan ulang.",
            "Memo {$memo->memo_number} ditolak oleh {$actor->name}. Silakan masuk ke sistem untuk melihat alasan dan merevisi memo."
        );

        return $memo;
    }

    private function pendingApprovalFor(Memo $memo, User $actor): MemoApproval
    {
        if ($memo->status !== MemoStatus::Pending) {
            throw new DomainException('Memo ini tidak sedang menunggu persetujuan.');
        }

        $approval = $memo->currentApprovals()
            ->where('step_order', $memo->current_step)
            ->where('status', ApprovalStatus::Pending->value)
            ->where(fn ($query) => $query
                ->where('assigned_approver_id', $actor->id)
                ->orWhere(fn ($legacyQuery) => $legacyQuery
                    ->whereNull('assigned_approver_id')
                    ->where('approver_role_id', $actor->role_id)))
            ->first();

        if (! $approval) {
            throw new DomainException('Saat ini bukan giliran Anda untuk memproses memo ini.');
        }
        if (
            ! $approval->assigned_approver_id
            && $approval->approverRole->slug === 'spv'
            && $memo->on_behalf_of_id !== $actor->id
        ) {
            throw new DomainException('Persetujuan supervisor hanya dapat dilakukan oleh supervisor yang tercantum pada memo.');
        }

        return $approval;
    }

    // ------------------------------------------------- PENCAIRAN & REALISASI

    public function disburse(Memo $memo, User $actor): Memo
    {
        $memo = DB::transaction(function () use ($memo, $actor) {
            $memo = Memo::lockForUpdate()->findOrFail($memo->id);

            if (! $actor->hasRole('admin_keuangan')) {
                throw new DomainException('Hanya Admin Keuangan yang dapat mencairkan dana.');
            }
            if ($memo->status !== MemoStatus::Approved) {
                throw new DomainException('Hanya memo berstatus Approved yang dapat dicairkan.');
            }

            $memo->update([
                'status' => MemoStatus::Disbursed,
                'disbursed_at' => now(),
                'disbursed_by' => $actor->id,
            ]);

            return $memo;
        });

        $this->send(
            $this->recipients($memo),
            $memo,
            'Dana telah dicairkan',
            "Dana untuk memo {$memo->memo_number} sudah dicairkan. Silakan unggah bukti realisasi (invoice/foto)."
        );

        return $memo;
    }

    /** @param  array<int, UploadedFile>  $files */
    public function complete(Memo $memo, User $actor, array $files): Memo
    {
        $memo = DB::transaction(function () use ($memo, $actor, $files) {
            $memo = Memo::lockForUpdate()->findOrFail($memo->id);

            if ($memo->submitter_id !== $actor->id) {
                throw new DomainException('Hanya pengaju yang dapat mengunggah bukti realisasi.');
            }
            if ($memo->status !== MemoStatus::Disbursed) {
                throw new DomainException('Bukti realisasi hanya dapat diunggah setelah dana dicairkan.');
            }
            if (count($files) === 0) {
                throw new DomainException('Minimal satu file bukti realisasi harus diunggah.');
            }

            $this->attach($memo, $files, 'realization', $actor);
            $memo->update(['status' => MemoStatus::Completed, 'completed_at' => now()]);

            return $memo;
        });

        $memo->loadMissing(['submitter', 'onBehalfOf']);
        if ($memo->disbursed_by) {
            $this->send(
                User::whereKey($memo->disbursed_by)->get(),
                $memo,
                'Bukti realisasi diunggah',
                "{$memo->submitter->name} telah mengunggah bukti realisasi untuk memo {$memo->memo_number}."
            );
        }

        return $memo;
    }

    // ------------------------------------------------------------ LAMPIRAN

    /** @param  array<int, UploadedFile>  $files */
    public function attach(Memo $memo, array $files, string $type, User $actor): void
    {
        foreach ($files as $file) {
            MemoAttachment::create([
                'memo_id' => $memo->id,
                'type' => $type,
                'original_name' => $file->getClientOriginalName(),
                'path' => $file->store("memos/{$memo->id}/{$type}", 'local'),
                'uploaded_by' => $actor->id,
            ]);
        }
    }

    public function deleteAttachment(MemoAttachment $attachment): void
    {
        Storage::disk('local')->delete($attachment->path);
        $attachment->delete();
    }

    // ---------------------------------------------------------- NOTIFIKASI

    public function notifyCurrentApprovers(Memo $memo): void
    {
        $pending = $memo->currentApprovals()->where('status', ApprovalStatus::Pending->value)->first();
        if (! $pending) {
            return;
        }

        $memo->loadMissing(['submitter', 'onBehalfOf']);
        $pending->loadMissing(['approverRole', 'assignedApprover']);
        $recipients = $pending->assignedApprover
            ? collect([$pending->assignedApprover])
            : $this->usersWithRole($pending->approverRole->slug);

        $this->send(
            $recipients,
            $memo,
            'Memo menunggu persetujuan Anda',
            "Memo {$memo->memo_number} \"{$memo->subject}\" dari {$memo->submitter->name} menunggu approval Anda.",
            emailAdministrator: true,
        );
    }

    /** Pengaju + Spv yang namanya tercetak di memo. */
    private function recipients(Memo $memo): Collection
    {
        $memo->loadMissing(['submitter', 'onBehalfOf']);

        return collect([$memo->submitter, $memo->onBehalfOf])->filter()->unique('id');
    }

    private function usersWithRole(string $slug): Collection
    {
        return User::whereHas('role', fn ($q) => $q->where('slug', $slug))->where('is_active', true)->get();
    }

    /**
     * @return Collection<int, User>
     */
    private function selectedApprovers(Memo $memo): Collection
    {
        $approverIds = array_map('intval', $memo->approver_ids ?? []);
        $usersById = User::with('role')
            ->whereIn('id', $approverIds)
            ->where('is_active', true)
            ->get()
            ->keyBy('id');

        if ($approverIds === [] || count(array_unique($approverIds)) !== count($approverIds) || $usersById->count() !== count($approverIds)) {
            throw new DomainException('Pilih minimal satu approver aktif yang valid sebelum mengajukan memo.');
        }

        $approvers = collect();
        foreach ($approverIds as $approverId) {
            $approver = $usersById->get($approverId);
            if (! $approver || ! $approver->role || $approver->hasRole('staff') || $approver->id === $memo->submitter_id) {
                throw new DomainException('Daftar approver tidak valid. Pilih user aktif selain Staff dan pengaju.');
            }

            $approvers->push($approver);
        }

        return $approvers;
    }

    /** Gagal kirim notifikasi/email tidak boleh menggagalkan proses approval. */
    private function send(
        iterable $users,
        Memo $memo,
        string $title,
        string $message,
        ?string $mailMessage = null,
        bool $emailAdministrator = false,
    ): void {
        foreach ($users as $user) {
            try {
                Notification::send($user, new MemoNotification(
                    $memo,
                    $title,
                    $message,
                    $mailMessage,
                    emailAdministrator: $emailAdministrator,
                ));
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }

    private function sendInApp(iterable $users, Memo $memo, string $title, string $message): void
    {
        foreach ($users as $user) {
            try {
                Notification::send($user, new MemoNotification($memo, $title, $message, null, true));
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }
}
