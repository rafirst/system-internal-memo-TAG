<?php

namespace App\Enums;

enum MemoStatus: string
{
    case Draft = 'draft';
    case Pending = 'pending';
    case Denied = 'denied';
    case Approved = 'approved';
    case Disbursed = 'disbursed';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Pending => 'Pending',
            self::Denied => 'Denied',
            self::Approved => 'Approved',
            self::Disbursed => 'Dicairkan',
            self::Completed => 'Completed',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'bg-gray-100 text-gray-700',
            self::Pending => 'bg-yellow-100 text-yellow-800',
            self::Denied => 'bg-red-100 text-red-700',
            self::Approved => 'bg-green-100 text-green-700',
            self::Disbursed => 'bg-blue-100 text-blue-700',
            self::Completed => 'bg-emerald-100 text-emerald-800',
        };
    }
}
