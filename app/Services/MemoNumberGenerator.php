<?php

namespace App\Services;

use App\Models\Division;
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
}
