<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ContentStatus: string implements HasLabel
{
    case Draft = 'draft';
    case Pending = 'pending';
    case Published = 'published';
    case Rejected = 'rejected';
    case Archived = 'archived';

    public function getLabel(): string
    {
        return match ($this) {
            self::Draft => 'Utkast',
            self::Pending => 'Väntar på granskning',
            self::Published => 'Publicerad',
            self::Rejected => 'Avvisad',
            self::Archived => 'Arkiverad',
        };
    }
}
