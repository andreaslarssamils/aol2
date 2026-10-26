<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ManagedBy: string implements HasLabel
{
    case Self = 'self';
    case Editorial = 'editorial';

    public function getLabel(): string
    {
        return match ($this) {
            self::Self => 'Verksamheten själv',
            self::Editorial => 'Redaktionen',
        };
    }
}
