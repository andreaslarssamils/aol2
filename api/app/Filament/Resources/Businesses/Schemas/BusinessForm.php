<?php

namespace App\Filament\Resources\Businesses\Schemas;

use App\Enums\BusinessKind;
use App\Enums\CategoryType;
use App\Enums\ManagedBy;
use App\Filament\Forms\Components\ContentStatusSelect;
use App\Filament\Forms\Components\ImagesUpload;
use App\Filament\Forms\Components\RichText;
use App\Filament\Resources\Categories\Schemas\CategoryForm;
use App\Filament\Schemas\Components\LocaleTabs;
use App\Models\Category;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class BusinessForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Verksamhet')
                    ->schema([
                        TextInput::make('namn')
                            ->label('Namn')
                            ->required()
                            ->maxLength(255),
                        Select::make('kind')
                            ->label('Typ')
                            ->options(BusinessKind::class)
                            ->default(BusinessKind::Company->value)
                            ->required(),
                        Select::make('managed_by')
                            ->label('Hanteras av')
                            ->options(ManagedBy::class)
                            ->default(ManagedBy::Editorial->value)
                            ->required(),
                        Toggle::make('auto_approve')
                            ->label('Godkänn ägarens ändringar automatiskt')
                            ->inline(false),
                        TextInput::make('epost')
                            ->label('E-post')
                            ->email()
                            ->maxLength(255),
                        TextInput::make('webbplats')
                            ->label('Webbplats')
                            ->url()
                            ->maxLength(255),
                        Select::make('categories')
                            ->label('Kategorier')
                            ->multiple()
                            ->relationship(
                                name: 'categories',
                                titleAttribute: 'slug',
                                modifyQueryUsing: fn (Builder $query): Builder => $query
                                    ->where('type', CategoryType::Business)
                                    ->orderBy('sort_order'),
                            )
                            ->getOptionLabelFromRecordUsing(fn (Category $record): string => CategoryForm::nameOf($record))
                            ->preload()
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                LocaleTabs::make()
                    ->localeSchema(fn (string $locale): array => [
                        RichText::make("beskrivning.{$locale}")
                            ->label('Beskrivning'),
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
