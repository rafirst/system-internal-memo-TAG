<?php

namespace App\Services;

use App\Enums\ApprovalStatus;
use App\Models\Memo;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Umpan aktivitas memo untuk kartu "Aktivitas" di dashboard.
 *
 * Kejadian diturunkan dari data yang sudah tersimpan — kapan memo dibuat,
 * diajukan, disetujui, ditolak, dicairkan, dan direalisasikan — sehingga riwayat
 * lama tetap tampil tanpa perlu tabel log tersendiri. Memo yang dipindai dan
 * jenis kejadian yang ditampilkan disesuaikan dengan role pengguna:
 *
 * - Administrator melihat seluruh kejadian pada sistem, termasuk draft.
 * - Supervisor melihat aktivitas divisinya.
 * - Role lain melihat memo sendiri dan memo yang menunggu approval-nya.
 */
class MemoActivityFeed
{
    public const DRAFTED = 'drafted';

    public const SUBMITTED = 'submitted';

    public const APPROVED = 'approved';

    public const DENIED = 'denied';

    public const DISBURSED = 'disbursed';

    public const COMPLETED = 'completed';

    /**
     * Batas memo yang dipindai untuk kartu aktivitas dashboard.
     *
     * Setiap kejadian selalu terjadi tidak lebih lama dari `updated_at` memo-nya
     * (pengajuan, approval, pencairan, dan realisasi semuanya menulis ulang memo
     * pada transaksi yang sama), jadi beberapa memo teratas sudah memuat seluruh
     * kejadian terbaru yang dibutuhkan kartu dashboard.
     */
    private const SCAN_LIMIT = 60;

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function forUser(User $user, int $limit = 4): Collection
    {
        if ($limit < 1) {
            return collect();
        }

        return $this->activitiesFor($user, $limit);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function allForUser(User $user): Collection
    {
        return $this->activitiesFor($user, null);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function activitiesFor(User $user, ?int $limit): Collection
    {
        $types = $this->eventTypesFor($user);

        $activities = $this->memosFor($user, $limit === null)
            ->flatMap(fn (Memo $memo) => $this->eventsFor($memo, $types))
            ->sortByDesc('at');

        if ($limit !== null) {
            $activities = $activities->take($limit);
        }

        return $activities->values();
    }

    /** Judul kartu menyesuaikan cakupan role. */
    public function headingFor(User $user): string
    {
        return match ($user->role?->slug) {
            'administrator' => 'Aktivitas Sistem',
            'spv' => 'Aktivitas Divisi',
            'admin_keuangan' => 'Aktivitas Keuangan',
            'admin_finance', 'adh', 'direksi' => 'Aktivitas Persetujuan',
            default => 'Aktivitas Memo',
        };
    }

    /**
     * Jenis kejadian yang relevan untuk tiap role.
     *
     * @return array<int, string>
     */
    private function eventTypesFor(User $user): array
    {
        return match ($user->role?->slug) {
            // Administrator: seluruh kejadian, termasuk draft yang belum diajukan.
            'administrator' => [
                self::DRAFTED, self::SUBMITTED, self::APPROVED,
                self::DENIED, self::DISBURSED, self::COMPLETED,
            ],
            // Admin Keuangan: fokus pada hasil approval dan pencairan dana.
            'admin_keuangan' => [self::APPROVED, self::DISBURSED, self::COMPLETED],
            // Pemberi approval: yang diajukan, disetujui, dan ditolak.
            'admin_finance', 'adh' => [self::SUBMITTED, self::APPROVED, self::DENIED],
            // Direksi: memo masuk beserta keputusan, pencairan, dan realisasinya.
            'direksi' => [self::SUBMITTED, self::APPROVED, self::DENIED, self::DISBURSED, self::COMPLETED],
            // Staff, Supervisor, dan role lain: siklus hidup memo secara utuh.
            default => [
                self::SUBMITTED, self::APPROVED,
                self::DENIED, self::DISBURSED, self::COMPLETED,
            ],
        };
    }

    /**
     * Memo yang dipindai untuk role ini.
     *
     * `scopeVisibleTo` sudah menyesuaikan cakupannya per role: Administrator
     * mendapat semua memo (termasuk draft), Supervisor mendapat divisinya, dan
     * role lain mendapat memo sendiri plus memo yang menunggu approval-nya.
     *
     * @return Collection<int, Memo>
     */
    private function memosFor(User $user, bool $includeAll = false): Collection
    {
        $query = Memo::query()
            ->visibleTo($user)
            ->with(['submitter:id,name', 'disbursedBy:id,name'])
            ->with(['approvals' => fn ($approvals) => $approvals
                ->whereIn('status', [ApprovalStatus::Approved->value, ApprovalStatus::Denied->value])
                ->whereNotNull('acted_at')
                ->with('actor:id,name')])
            ->orderByDesc('updated_at');

        if (! $includeAll) {
            $query->limit(self::SCAN_LIMIT);
        }

        return $query->get();
    }

    /**
     * @param  array<int, string>  $types
     * @return array<int, array<string, mixed>>
     */
    private function eventsFor(Memo $memo, array $types): array
    {
        $events = [];

        // "Memo dibuat" hanya muncul selagi memo masih draft; setelah diajukan,
        // kejadian pengajuan yang mewakilinya agar feed tidak tampil ganda.
        if (in_array(self::DRAFTED, $types, true) && ! $memo->submitted_at) {
            $events[] = $this->event(self::DRAFTED, $memo->created_at, 'Memo dibuat', sprintf(
                '%s membuat draft memo "%s".',
                $this->actorName($memo->submitter),
                $memo->subject,
            ), $memo);
        }

        if (in_array(self::SUBMITTED, $types, true) && $memo->submitted_at) {
            $events[] = $this->event(self::SUBMITTED, $memo->submitted_at, 'Memo diajukan', sprintf(
                '%s mengajukan %s.',
                $this->actorName($memo->submitter),
                $this->memoLabel($memo),
            ), $memo);
        }

        foreach ($memo->approvals as $approval) {
            $denied = $approval->status === ApprovalStatus::Denied;
            $type = $denied ? self::DENIED : self::APPROVED;

            if (! in_array($type, $types, true)) {
                continue;
            }

            $message = sprintf(
                '%s %s %s.',
                $this->actorName($approval->actor),
                $denied ? 'menolak' : 'menyetujui',
                $this->memoLabel($memo),
            );

            if ($denied && filled($approval->comment)) {
                $message .= " Alasan: {$approval->comment}";
            }

            $events[] = $this->event($type, $approval->acted_at, $denied ? 'Memo ditolak' : 'Memo disetujui', $message, $memo);
        }

        if (in_array(self::DISBURSED, $types, true) && $memo->disbursed_at) {
            $events[] = $this->event(self::DISBURSED, $memo->disbursed_at, 'Dana dicairkan', sprintf(
                '%s mencairkan dana %s.',
                $this->actorName($memo->disbursedBy),
                $this->memoLabel($memo),
            ), $memo);
        }

        if (in_array(self::COMPLETED, $types, true) && $memo->completed_at) {
            $events[] = $this->event(self::COMPLETED, $memo->completed_at, 'Bukti realisasi diunggah', sprintf(
                '%s mengunggah bukti realisasi %s.',
                $this->actorName($memo->submitter),
                $this->memoLabel($memo),
            ), $memo);
        }

        return $events;
    }

    /**
     * @return array<string, mixed>
     */
    private function event(string $type, ?Carbon $at, string $title, string $message, Memo $memo): array
    {
        return [
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'at' => $at,
            'memo_number' => $memo->memo_number,
            'url' => route('memos.show', $memo),
        ];
    }

    /** "memo 004/MD-TAG/X/2026 — \"Beli cimol bojot\"" */
    private function memoLabel(Memo $memo): string
    {
        return sprintf('memo %s — "%s"', $memo->memo_number ?: '(draft)', $memo->subject);
    }

    private function actorName(?User $user): string
    {
        return $user?->name ?? 'Sistem';
    }
}
