<?php

namespace App\Support;

class Terbilang
{
    private const WORDS = ['', 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan', 'sepuluh', 'sebelas'];

    /** 3000000 -> "Tiga Juta Rupiah" */
    public static function rupiah(float|int|string $amount): string
    {
        $n = (int) round((float) $amount);

        return $n === 0 ? 'Nol Rupiah' : ucwords(trim(self::make($n))) . ' Rupiah';
    }

    public static function make(int $n): string
    {
        return match (true) {
            $n < 12 => self::WORDS[$n],
            $n < 20 => self::make($n - 10) . ' belas',
            $n < 100 => self::make(intdiv($n, 10)) . ' puluh' . self::rest($n % 10),
            $n < 200 => 'seratus' . self::rest($n - 100),
            $n < 1000 => self::make(intdiv($n, 100)) . ' ratus' . self::rest($n % 100),
            $n < 2000 => 'seribu' . self::rest($n - 1000),
            $n < 1_000_000 => self::make(intdiv($n, 1000)) . ' ribu' . self::rest($n % 1000),
            $n < 1_000_000_000 => self::make(intdiv($n, 1_000_000)) . ' juta' . self::rest($n % 1_000_000),
            $n < 1_000_000_000_000 => self::make(intdiv($n, 1_000_000_000)) . ' miliar' . self::rest($n % 1_000_000_000),
            default => self::make(intdiv($n, 1_000_000_000_000)) . ' triliun' . self::rest($n % 1_000_000_000_000),
        };
    }

    private static function rest(int $n): string
    {
        return $n > 0 ? ' ' . self::make($n) : '';
    }
}
