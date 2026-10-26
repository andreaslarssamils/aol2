<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum CategoryType: string implements HasLabel
{
    case Business = 'business';
    case Event = 'event';
    /** Fotboll, ishockey, innebandy ... – används av den framtida sportmodulen. */
    case Sport = 'sport';

    public function getLabel(): string
    {
        return match ($this) {
            self::Business => 'Verksamhet',
            self::Event => 'Evenemang',
            self::Sport => 'Sport',
        };
    }
}
