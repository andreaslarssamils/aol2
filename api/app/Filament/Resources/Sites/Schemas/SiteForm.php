<?php

namespace App\Filament\Resources\Sites\Schemas;

use App\Enums\SiteStatus;
use App\Filament\Forms\Components\KommunSelect;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SiteForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Sajt')
                    ->schema([
                        TextInput::make('namn')
                            ->label('Namn')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->alphaDash()
                            ->unique(ignoreRecord: true)
                            ->helperText('Används i sökvägen när sajten inte har egen domän.'),
                        TextInput::make('domain')
                            ->label('Domän')
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->placeholder('Tomt = nås via sökväg'),
                        KommunSelect::make('primary_kommun_id')
                            ->label('Primär kommun')
                            ->relationship('primaryKommun', 'namn')
                            ->helperText('Ort för väder, startsidans fokus och kanonisk URL. Läggs automatiskt till bland sajtens kommuner.'),
                        Select::make('status')
                            ->label('Status')
                            ->options(SiteStatus::class)
                            ->default(SiteStatus::ComingSoon->value)
                            ->required(),
                    ])
                    ->columns(2),
                Section::make('Inställningar')
                    ->schema([
                        KeyValue::make('settings')
                            ->hiddenLabel()
                            ->keyLabel('Nyckel')
                            ->valueLabel('Värde'),
                    ])
                    ->collapsed(),
            ]);
    }
}
