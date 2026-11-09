<?php

namespace App\Enums;

enum ApprovalStatus: string
{
    case Waiting = 'waiting';   // belum giliran
    case Pending = 'pending';   // sedang giliran
    case Approved = 'approved';
    case Denied = 'denied';

    public function label(): string
    {
        return match ($this) {
            self::Waiting => 'Menunggu giliran',
            self::Pending => 'Menunggu approval',
            self::Approved => 'Approved',
            self::Denied => 'Denied',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Waiting => 'bg-gray-100 text-gray-500',
            self::Pending => 'bg-yellow-100 text-yellow-800',
            self::Approved => 'bg-green-100 text-green-700',
            self::Denied => 'bg-red-100 text-red-700',
        };
    }
}
