<?php

namespace App\Filament\Resources\Promotions\Schemas;

use App\Enums\PromotionPlacement;
use App\Enums\PromotionStatus;
use App\Filament\Forms\Components\KommunSelect;
use App\Filament\Schemas\Components\LocaleTabs;
use App\Models\Article;
use App\Models\BusinessLocation;
use App\Models\Event;
use App\Models\Offer;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\MorphToSelect;
use Filament\Forms\Components\MorphToSelect\Type;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class PromotionForm
{
    public static function configure(Schema $schema): Schema
    {
        $locale = LocaleTabs::defaultLocale();

        return $schema
            ->components([
                Section::make('Innehåll')
                    ->schema([
                        MorphToSelect::make('promotable')
                            ->label('Innehåll som lyfts fram')
                            ->required()
                            ->types([
                                Type::make(Event::class)
                                    ->label('Evenemang')
                                    ->titleAttribute('namn'),
                                Type::make(Article::class)
                                    ->label('Artikel')
                                    ->titleAttribute('id')
                                    ->getOptionLabelFromRecordUsing(fn (Article $record): string => $record->titel[$locale] ?? "#{$record->id}")
                                    ->searchColumns(["titel->{$locale}"]),
                                Type::make(BusinessLocation::class)
                                    ->label('Verksamhet')
                                    ->titleAttribute('id')
                                    ->getOptionLabelFromRecordUsing(fn (BusinessLocation $record): string => "{$record->display_name} – {$record->kommun?->namn}")
                                    ->searchColumns(['namn', 'ort', 'adress'])
                                    ->modifyOptionsQueryUsing(fn (Builder $query): Builder => $query->with(['business', 'kommun'])),
                                Type::make(Offer::class)
                                    ->label('Erbjudande')
                                    ->titleAttribute('id')
                                    ->getOptionLabelFromRecordUsing(fn (Offer $record): string => $record->titel[$locale] ?? "#{$record->id}")
                                    ->searchColumns(["titel->{$locale}"]),
                            ])
                            ->modifyKeySelectUsing(fn (Select $select): Select => $select->searchable())
                            ->columnSpanFull(),
                    ]),
                Section::make('Placering')
                    ->schema([
                        KommunSelect::make()
                            ->required()
                            ->helperText('Orten där innehållet visas, utöver hemkommunen.'),
                        Select::make('placement')
                            ->label('Yta')
                            ->options(PromotionPlacement::class)
                            ->required(),
                        Select::make('status')
                            ->label('Status')
                            ->options(PromotionStatus::class)
                            ->default(PromotionStatus::Draft->value)
                            ->required(),
                        TextInput::make('priority')
                            ->label('Prioritet')
                            ->integer()
                            ->default(0)
                            ->required()
                            ->helperText('Högre visas först.'),
                    ])
                    ->columns(2),
                Section::make('Period')
                    ->schema([
                        DateTimePicker::make('starts_at')
                            ->label('Från')
                            ->required()
                            ->seconds(false),
                        DateTimePicker::make('ends_at')
                            ->label('Till')
                            ->seconds(false)
                            ->afterOrEqual('starts_at')
                            ->placeholder('Tomt = tills vidare'),
                    ])
                    ->columns(2),
                Section::make('Betalning')
                    ->schema([
                        Toggle::make('is_paid')
                            ->label('Betald placering (visas med "Annons")')
                            ->live()
                            ->inline(false),
                        TextInput::make('price')
                            ->label('Pris')
                            ->numeric()
                            ->suffix('kr')
                            ->visible(fn (Get $get): bool => (bool) $get('is_paid')),
                        DateTimePicker::make('invoiced_at')
                            ->label('Fakturerad')
                            ->seconds(false)
                            ->visible(fn (Get $get): bool => (bool) $get('is_paid')),
                    ])
                    ->columns(3),
            ]);
    }
}
