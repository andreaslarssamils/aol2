<?php

namespace App\Filament\Resources\Claims;

use App\Filament\Resources\Claims\Pages\ListClaims;
use App\Filament\Resources\Claims\Tables\ClaimsTable;
use App\Models\BusinessClaim;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/** Anspråk från portalen: ägare som vill ta över en post redaktionen lagt in. Godkänn eller avslå; den sökande mejlas. */
class ClaimResource extends Resource
{
    protected static ?string $model = BusinessClaim::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHandRaised;

    protected static string|UnitEnum|null $navigationGroup = 'Katalog';

    protected static ?int $navigationSort = 15;

    protected static ?string $modelLabel = 'anspråk';

    protected static ?string $pluralModelLabel = 'anspråk';

    public static function getNavigationBadge(): ?string
    {
        $pending = BusinessClaim::query()->pending()->count();

        return $pending > 0 ? (string) $pending : null;
    }

    public static function table(Table $table): Table
    {
        return ClaimsTable::configure($table);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListClaims::route('/'),
        ];
    }
}
