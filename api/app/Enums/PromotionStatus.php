<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/** Pausa en placering utan att skriva om datumen. */
enum PromotionStatus: string implements HasLabel
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
}
