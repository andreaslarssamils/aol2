<?php

namespace App\Filament\Portal\Schemas;

use App\Enums\CategoryType;
use App\Filament\Resources\Categories\Schemas\CategoryForm;
use App\Filament\Schemas\Components\LocaleTabs;
use App\Models\BusinessLocation;
use App\Models\Category;
use App\Models\Kommun;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Infolists\Components\TextEntry;
use Illuminate\Database\Eloquent\Model;

/**
 * Fält som portalens formulär delar. Inga ->relationship(): Filament skulle då spara relationen
 * direkt på raden, förbi revisionen. Värdena går i stället med i payloaden till SubmitRevision.
 */
class PortalFields
{
    public static function kommun(string $name = 'kommun_id'): Select
    {
        return Select::make($name)
            ->label('Kommun')
            ->options(fn (): array => Kommun::query()->orderBy('namn')->pluck('namn', 'id')->all())
            ->searchable()
            ->preload();
    }

    public static function categories(CategoryType $type, string $name = 'categories'): Select
    {
        return Select::make($name)
            ->label('Kategorier')
            ->multiple()
            ->options(fn (): array => Category::query()
                ->where('type', $type)
                ->orderBy('sort_order')
                ->get()
                ->mapWithKeys(fn (Category $category): array => [$category->id => CategoryForm::nameOf($category)])
                ->all())
            ->preload()
            ->columnSpanFull();
    }

    /** Aktuell verksamhets lokaliseringar – det enda en ägare får hänga innehåll på. */
    public static function tenantLocation(string $name = 'business_location_id'): Select
    {
        return Select::make($name)
            ->label('Lokalisering')
            ->options(fn (): array => static::tenantLocations())
            ->in(fn (): array => array_keys(static::tenantLocations()))
            ->required();
    }

    /** @return array<int, string> id => "Namn – Kommun" */
    public static function tenantLocations(): array
    {
        return Filament::getTenant()
            ?->locations()
            ->with(['business', 'kommun'])
            ->orderByDesc('is_primary')
            ->get()
            ->mapWithKeys(fn (BusinessLocation $location): array => [
                $location->id => "{$location->display_name} – {$location->kommun?->namn}",
            ])
            ->all() ?? [];
    }

    /** Sluggen visas men sätts bara av redaktionen. */
    public static function slug(): TextEntry
    {
        return TextEntry::make('slug')
            ->label('Adress på webben (slug)')
            ->state(fn (?Model $record): ?string => $record?->exists ? $record->slugFor(LocaleTabs::defaultLocale()) : null)
            ->placeholder('Sätts av redaktionen när lokaliseringen publiceras')
            ->helperText('Kontakta redaktionen om adressen behöver ändras.');
    }
}
