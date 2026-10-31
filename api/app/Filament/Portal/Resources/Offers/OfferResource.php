<?php

namespace App\Filament\Portal\Resources\Offers;

use App\Filament\Portal\Resources\Offers\Pages\CreateOffer;
use App\Filament\Portal\Resources\Offers\Pages\EditOffer;
use App\Filament\Portal\Resources\Offers\Pages\ListOffers;
use App\Filament\Portal\Resources\Offers\Schemas\OfferForm;
use App\Filament\Portal\Resources\Offers\Tables\OffersTable;
use App\Models\Offer;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/** Verksamhetens erbjudanden. Scopas på tenant via relationen business; allt går via revision. */
class OfferResource extends Resource
{
    protected static ?string $model = Offer::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTicket;

    protected static ?int $navigationSort = 40;

    protected static ?string $modelLabel = 'erbjudande';

    protected static ?string $pluralModelLabel = 'erbjudanden';

    public static function form(Schema $schema): Schema
    {
        return OfferForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OffersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOffers::route('/'),
            'create' => CreateOffer::route('/create'),
            'edit' => EditOffer::route('/{record}/edit'),
        ];
    }
}
