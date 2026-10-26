<?php

namespace App\Filament\Resources\Categories\Schemas;

use App\Enums\CategoryType;
use App\Filament\Schemas\Components\LocaleTabs;
use App\Models\Category;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Unique;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Kategori')
                    ->schema([
                        Select::make('type')
                            ->label('Typ')
                            ->options(CategoryType::class)
                            ->default(CategoryType::Business->value)
                            ->required()
                            ->live(),
                        Select::make('parent_id')
                            ->label('Överordnad kategori')
                            ->relationship(
                                name: 'parent',
                                titleAttribute: 'slug',
                                modifyQueryUsing: fn (Builder $query, Get $get): Builder => $query
                                    ->where('type', $get('type'))
                                    ->orderBy('sort_order'),
                                ignoreRecord: true,
                            )
                            ->getOptionLabelFromRecordUsing(fn (Category $record): string => static::nameOf($record))
                            ->searchable()
                            ->preload(),
                        TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->alphaDash()
                            ->unique(
                                ignoreRecord: true,
                                modifyRuleUsing: fn (Unique $rule, Get $get): Unique => $rule->where('type', $get('type')),
                            )
                            ->helperText('Ett slug för alla språk – kategorier är få och byter sällan namn.'),
                        TextInput::make('icon')
                            ->label('Ikon')
                            ->maxLength(255),
                        TextInput::make('sort_order')
                            ->label('Sortering')
                            ->integer()
                            ->default(0)
                            ->required(),
                    ])
                    ->columns(2),
                LocaleTabs::make()
                    ->localeSchema(fn (string $locale, bool $isDefault): array => [
                        TextInput::make("namn.{$locale}")
                            ->label('Namn')
                            ->required($isDefault)
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Set $set, Get $get, ?string $state, string $operation) use ($isDefault): void {
                                // Föreslå slug från standardspråkets namn så länge inget slug skrivits in.
                                if ($isDefault && $operation === 'create' && blank($get('slug'))) {
                                    $set('slug', Str::slug($state ?? ''));
                                }
                            }),
                    ]),
            ]);
    }

    public static function nameOf(Category $category): string
    {
        return $category->namn[LocaleTabs::defaultLocale()] ?? $category->slug;
    }
}
