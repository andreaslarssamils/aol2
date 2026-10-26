<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ClaimStatus: string implements HasLabel
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => 'Väntar',
            self::Approved => 'Godkänt',
            self::Rejected => 'Avvisat',
        };
    }
}
