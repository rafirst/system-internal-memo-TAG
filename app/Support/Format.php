<?php

namespace App\Support;

use Carbon\CarbonInterface;

class Format
{
    private const MONTHS = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
        7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    /** Rp 3.000.000,- */
    public static function rupiah(float|int|string|null $n): string
    {
        return 'Rp ' . number_format((float) $n, 0, ',', '.') . ',-';
    }

    /** 09 Januari 2026 */
    public static function tanggal(?CarbonInterface $d): string
    {
        return $d ? $d->format('d') . ' ' . self::MONTHS[$d->month] . ' ' . $d->year : '-';
    }
}
