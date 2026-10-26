<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum DiscountType: string implements HasLabel
{
    case Percentage = 'percentage';
    case Fixed = 'fixed';
    case Freebie = 'freebie';

    public function getLabel(): string
    {
        return match ($this) {
            self::Percentage => 'Procent',
            self::Fixed => 'Fast belopp',
            self::Freebie => 'På köpet',
        };
    }
}
