<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum RevisionStatus: string implements HasLabel
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => 'Väntar',
            self::Approved => 'Godkänd',
            self::Rejected => 'Avvisad',
        };
    }
}
