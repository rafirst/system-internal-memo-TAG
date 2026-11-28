<?php

namespace App\Services;

use App\Models\Division;
use App\Models\Memo;
use App\Models\MemoSequence;
use Illuminate\Support\Facades\DB;

class MemoNumberGenerator
{
    private const ROMAN = [
        1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI',
        7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII',
    ];

    /**
     * Format: urutan/KODE-TAG/bulan-romawi/tahun  -> 001/MD-TAG/I/2026
     * Urutan per divisi, reset tiap tahun. Panggil di dalam DB::transaction saat submit pertama.
     */
    public function generate(Division $division, ?\DateTimeInterface $date = null): string
    {
        $date ??= now();
        $year = (int) $date->format('Y');
        $month = (int) $date->format('n');

        return DB::transaction(function () use ($division, $year, $month) {
            $seq = MemoSequence::where('division_id', $division->id)
                ->where('year', $year)
                ->lockForUpdate()
                ->first();

            if (! $seq) {
                $seq = MemoSequence::create([
                    'division_id' => $division->id,
                    'year' => $year,
                    'last_number' => 0,
                ]);
            }

            $seq->increment('last_number');

            return sprintf(
                '%03d/%s-TAG/%s/%d',
                $seq->last_number,
                $division->code,
                self::ROMAN[$month],
                $year
            );
        });
    }

    /**
     * Reclaim a memo number when the deleted memo was the latest in its series.
     * Call inside the same transaction that deletes the memo.
     */
    public function release(Memo $memo): void
    {
        if (! $memo->memo_number) {
            return;
        }

        $memo->loadMissing('division');
        $division = $memo->division;
        if (! $division) {
            return;
        }

        $parts = [];
        $pattern = '/^(\d+)\/'.preg_quote($division->code, '/').'-TAG\/[IVX]+\/(\d{4})$/D';
        if (! preg_match($pattern, $memo->memo_number, $parts)) {
            return;
        }

        $deletedNumber = (int) $parts[1];
        $year = (int) $parts[2];
        $sequence = MemoSequence::query()
            ->where('division_id', $division->id)
            ->where('year', $year)
            ->lockForUpdate()
            ->first();

        $numbers = Memo::query()
            ->where('division_id', $division->id)
            ->whereNotNull('memo_number')
            ->pluck('memo_number')
            ->map(fn (string $memoNumber): ?int => $this->numberFromMemoNumber($memoNumber, $division->code, $year))
            ->filter(fn (?int $number): bool => $number !== null)
            ->values();

        if (($numbers->max() ?? 0) >= $deletedNumber) {
            return;
        }

        $remainingNumber = $numbers->max() ?? 0;

        if ($sequence) {
            $sequence->update(['last_number' => $remainingNumber]);

            return;
        }

        MemoSequence::create([
            'division_id' => $division->id,
            'year' => $year,
            'last_number' => $remainingNumber,
        ]);
    }

    private function numberFromMemoNumber(string $memoNumber, string $divisionCode, int $year): ?int
    {
        $pattern = '/^(\d+)\/'.preg_quote($divisionCode, '/').'-TAG\/[IVX]+\/'.preg_quote((string) $year, '/').'$/D';
        if (! preg_match($pattern, $memoNumber, $parts)) {
            return null;
        }

        return (int) $parts[1];
    }
}
