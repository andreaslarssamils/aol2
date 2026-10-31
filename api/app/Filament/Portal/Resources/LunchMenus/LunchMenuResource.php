<?php

namespace App\Filament\Portal\Resources\LunchMenus;

use App\Filament\Portal\Resources\LunchMenus\Pages\CreateLunchMenu;
use App\Filament\Portal\Resources\LunchMenus\Pages\EditLunchMenu;
use App\Filament\Portal\Resources\LunchMenus\Pages\ListLunchMenus;
use App\Filament\Portal\Resources\LunchMenus\Schemas\LunchMenuForm;
use App\Filament\Portal\Resources\LunchMenus\Tables\LunchMenusTable;
use App\Models\LunchMenu;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Veckans lunch per lokalisering. Går via revision som allt annat, utom när verksamheten har
 * auto_approve – då sparas menyn direkt (se sidorna). Lunchmenyn hör till verksamheten via
 * lokaliseringen, så tenant-scopingen görs här i stället för via ägarrelationen.
 */
class LunchMenuResource extends Resource
{
    protected static ?string $model = LunchMenu::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCake;

    protected static ?int $navigationSort = 20;

    protected static ?string $modelLabel = 'lunchmeny';

    protected static ?string $pluralModelLabel = 'lunchmenyer';

    protected static ?string $navigationLabel = 'Lunchmeny';

    protected static bool $isScopedToTenant = false;

    /** @var list<string> Dagarna i formuläret, i ordning. menu-kolumnen tillåter även sat/sun. */
    public const array WEEKDAYS = ['mon', 'tue', 'wed', 'thu', 'fri'];

    /** @var array<string, string> */
    public const array WEEKDAY_LABELS = [
        'mon' => 'Måndag',
        'tue' => 'Tisdag',
        'wed' => 'Onsdag',
        'thu' => 'Torsdag',
        'fri' => 'Fredag',
    ];

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereRelation('businessLocation', 'business_id', Filament::getTenant()?->getKey());
    }

    public static function form(Schema $schema): Schema
    {
        return LunchMenuForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LunchMenusTable::configure($table);
    }

    /** Lunchmenyn skrivs direkt, utan revision, när verksamheten godkänns automatiskt. */
    public static function savesDirectly(): bool
    {
        return (bool) Filament::getTenant()?->auto_approve;
    }

    /**
     * Formulärets rätter som {"mon": ["Rätt", ...]}: trimmade, tomma rader bortfiltrerade,
     * alla formulärets dagar med (tom lista = ingen lunch den dagen).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function normalizeMenu(array $data): array
    {
        $menu = [];

        foreach (array_unique([...self::WEEKDAYS, ...array_keys($data['menu'] ?? [])]) as $day) {
            $menu[$day] = collect($data['menu'][$day] ?? [])
                ->map(fn (mixed $dish): string => trim((string) $dish))
                ->filter(fn (string $dish): bool => $dish !== '')
                ->values()
                ->all();
        }

        return [...$data, 'menu' => $menu];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLunchMenus::route('/'),
            'create' => CreateLunchMenu::route('/create'),
            'edit' => EditLunchMenu::route('/{record}/edit'),
        ];
    }
}
