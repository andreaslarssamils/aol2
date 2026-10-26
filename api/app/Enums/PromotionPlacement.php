<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum PromotionPlacement: string implements HasLabel
{
    case Startsida = 'startsida';
    case Kategorisida = 'kategorisida';
    case Evenemangskalender = 'evenemangskalender';
    case Nyhetsbrev = 'nyhetsbrev';

    public function getLabel(): string
    {
        return match ($this) {
            self::Startsida => 'Startsida',
            self::Kategorisida => 'Kategorisida',
            self::Evenemangskalender => 'Evenemangskalender',
            self::Nyhetsbrev => 'Nyhetsbrev',
        };
    }
}
