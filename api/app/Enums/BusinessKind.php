<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Föreningar delar katalog, lokaliseringar, portal och godkännandeflöde
 * med företag – skillnaden är bara den här etiketten.
 */
enum BusinessKind: string implements HasLabel
{
    case Company = 'company';
    case Association = 'association';

    public function getLabel(): string
    {
        return match ($this) {
            self::Company => 'Företag',
            self::Association => 'Förening',
        };
    }
}
