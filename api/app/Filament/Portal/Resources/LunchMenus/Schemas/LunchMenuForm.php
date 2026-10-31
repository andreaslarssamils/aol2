<?php

namespace App\Filament\Portal\Resources\LunchMenus\Schemas;

use App\Filament\Portal\Resources\LunchMenus\LunchMenuResource;
use App\Filament\Portal\Schemas\Components\PendingRevisionBanner;
use App\Filament\Portal\Schemas\PortalFields;
use App\Models\LunchMenu;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

class LunchMenuForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                PendingRevisionBanner::make(),
                Section::make('Vecka')
                    ->description(fn (): string => LunchMenuResource::savesDirectly()
                        ? 'Lunchmenyn publiceras direkt när du sparar.'
                        : 'Lunchmenyn granskas av redaktionen innan den publiceras.')
                    ->schema([
                        PortalFields::tenantLocation()
                            ->disabledOn('edit')
                            ->dehydrated(fn (string $operation): bool => $operation === 'create'),
                        TextInput::make('iso_year')
                            ->label('År')
                            ->numeric()
                            ->integer()
                            ->minValue(2000)
                            ->maxValue(2100)
                            ->required()
                            ->default(fn (): int => now(config('app.display_timezone'))->isoWeekYear),
                        TextInput::make('week')
                            ->label('Vecka')
                            ->numeric()
                            ->integer()
                            ->minValue(1)
                            ->maxValue(53)
                            ->required()
                            ->default(fn (): int => now(config('app.display_timezone'))->isoWeek)
                            ->rule(fn (Get $get, ?LunchMenu $record): Unique => Rule::unique('lunch_menus', 'week')
                                ->where('business_location_id', $get('business_location_id') ?? $record?->business_location_id)
                                ->where('iso_year', (int) $get('iso_year'))
                                ->whereNull('deleted_at')
                                ->ignore($record))
                            ->validationMessages(['unique' => 'Det finns redan en lunchmeny för den här veckan.']),
                    ])
                    ->columns(3),
                Tabs::make('Rätter')
                    ->tabs(collect(LunchMenuResource::WEEKDAYS)
                        ->map(fn (string $day): Tab => Tab::make($day)
                            ->label(LunchMenuResource::WEEKDAY_LABELS[$day])
                            ->badge(fn (Get $get): ?string => ($count = count(array_filter((array) $get("menu.{$day}"), fn (mixed $dish): bool => filled($dish)))) > 0 ? (string) $count : null)
                            ->schema([
                                Repeater::make("menu.{$day}")
                                    ->hiddenLabel()
                                    ->simple(
                                        TextInput::make('dish')
                                            ->label('Rätt')
                                            ->required()
                                            ->maxLength(255),
                                    )
                                    ->defaultItems(0)
                                    ->reorderable()
                                    ->addActionLabel('Lägg till rätt'),
                            ]))
                        ->values()
                        ->all())
                    ->persistTabInQueryString()
                    ->columnSpanFull(),
            ]);
    }
}
