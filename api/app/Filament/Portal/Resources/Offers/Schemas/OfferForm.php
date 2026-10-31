<?php

namespace App\Filament\Portal\Resources\Offers\Schemas;

use App\Enums\DiscountType;
use App\Filament\Forms\Components\RichText;
use App\Filament\Portal\Schemas\Components\PendingRevisionBanner;
use App\Filament\Schemas\Components\LocaleTabs;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OfferForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                PendingRevisionBanner::make(),
                LocaleTabs::make()
                    ->localeSchema(fn (string $locale, bool $isDefault): array => [
                        TextInput::make("titel.{$locale}")
                            ->label('Titel')
                            ->required($isDefault)
                            ->maxLength(255),
                        RichText::make("beskrivning.{$locale}")
                            ->label('Beskrivning'),
                    ]),
                Section::make('Villkor')
                    ->schema([
                        Select::make('discount_type')
                            ->label('Typ av rabatt')
                            ->options(DiscountType::class),
                        TextInput::make('discount_value')
                            ->label('Värde')
                            ->numeric()
                            ->minValue(0)
                            ->helperText('Procent eller kronor beroende på typ.'),
                        TextInput::make('code')
                            ->label('Kod')
                            ->maxLength(255),
                        DateTimePicker::make('starts_at')
                            ->label('Gäller från')
                            ->seconds(false),
                        DateTimePicker::make('ends_at')
                            ->label('Gäller till')
                            ->seconds(false)
                            ->afterOrEqual('starts_at'),
                    ])
                    ->columns(2),
            ]);
    }
}
