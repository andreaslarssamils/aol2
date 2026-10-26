<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/** Pausa en placering utan att skriva om datumen. */
enum PromotionStatus: string implements HasColor, HasLabel
{
    case Draft = 'draft';
    case Active = 'active';
    case Paused = 'paused';
    case Ended = 'ended';

    public function getLabel(): string
    {
        return match ($this) {
            self::Draft => 'Utkast',
            self::Active => 'Aktiv',
            self::Paused => 'Pausad',
            self::Ended => 'Avslutad',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Active => 'success',
            self::Paused => 'warning',
            self::Ended => 'gray',
        };
    }
}
