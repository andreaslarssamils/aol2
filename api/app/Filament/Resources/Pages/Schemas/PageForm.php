<?php

namespace App\Filament\Resources\Pages\Schemas;

use App\Filament\Forms\Components\ContentStatusSelect;
use App\Filament\Forms\Components\ImagesUpload;
use App\Filament\Forms\Components\KommunSelect;
use App\Filament\Forms\Components\RichText;
use App\Filament\Forms\Components\SlugInput;
use App\Filament\Infolists\Components\SlugHistoryEntry;
use App\Filament\Schemas\Components\LocaleTabs;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Sida')
                    ->schema([
                        KommunSelect::make()
                            ->placeholder('Nationell – visas på alla orter')
                            ->helperText('Tomt = nationell sida. En ortspecifik sida med samma slug går före den nationella.'),
                    ])
                    ->columns(2),
                LocaleTabs::make()
                    ->localeSchema(fn (string $locale, bool $isDefault): array => [
                        TextInput::make("titel.{$locale}")
                            ->label('Titel')
                            ->required($isDefault)
                            ->maxLength(255),
                        SlugInput::forLocale($locale)
                            ->fallbackTo("titel.{$locale}"),
                        SlugHistoryEntry::forLocale($locale),
                        RichText::make("body.{$locale}")
                            ->label('Innehåll'),
                    ]),
                Section::make('Publicering')
                    ->schema([
                        ContentStatusSelect::make(),
                        DateTimePicker::make('published_at')
                            ->label('Publicerad')
                            ->seconds(false),
                    ])
                    ->columns(2),
                Section::make('Bilder')
                    ->schema([
                        ImagesUpload::make(),
                    ]),
            ]);
    }
}
