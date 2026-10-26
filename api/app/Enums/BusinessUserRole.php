<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/** Roll i pivoten business_user – styr t.ex. vem som får bjuda in fler. */
enum BusinessUserRole: string implements HasLabel
{
    case Owner = 'owner';
    case Manager = 'manager';

    public function getLabel(): string
    {
        return match ($this) {
            self::Owner => 'Ägare',
            self::Manager => 'Administratör',
        };
    }
}
