<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum SiteStatus: string implements HasColor, HasLabel
{
    case Active = 'active';
    case ComingSoon = 'coming_soon';
    case Hidden = 'hidden';

    public function getLabel(): string
    {
        return match ($this) {
            self::Active => 'Aktiv',
            self::ComingSoon => 'Kommer snart',
            self::Hidden => 'Dold',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::ComingSoon => 'info',
            self::Hidden => 'gray',
        };
    }
}
