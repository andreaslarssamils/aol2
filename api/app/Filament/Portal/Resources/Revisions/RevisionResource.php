<?php

namespace App\Filament\Portal\Resources\Revisions;

use App\Filament\Portal\Resources\Revisions\Pages\ListRevisions;
use App\Filament\Portal\Resources\Revisions\Tables\RevisionsTable;
use App\Models\Business;
use App\Models\BusinessLocation;
use App\Models\Event;
use App\Models\LunchMenu;
use App\Models\Offer;
use App\Models\Revision;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * "Mina ändringar": skrivskyddad lista över de revisioner användaren själv skickat in för
 * aktuell verksamhet, med status, granskarens kommentar och diff. Revisioner har ingen
 * egen ägarlänk, så scopingen går via innehållet.
 */
class RevisionResource extends Resource
{
    protected static ?string $model = Revision::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static ?string $navigationLabel = 'Mina ändringar';

    protected static ?int $navigationSort = 50;

    protected static ?string $modelLabel = 'ändring';

    protected static ?string $pluralModelLabel = 'mina ändringar';

    protected static bool $isScopedToTenant = false;

    public static function getEloquentQuery(): Builder
    {
        $tenantKey = Filament::getTenant()?->getKey();

        return parent::getEloquentQuery()
            ->where('submitted_by', Filament::auth()->id())
            ->where(fn (Builder $query): Builder => $query
                ->whereHasMorph('revisionable', [Business::class], fn (Builder $business): Builder => $business->whereKey($tenantKey))
                ->orWhereHasMorph('revisionable', [BusinessLocation::class, Offer::class], fn (Builder $content): Builder => $content->where('business_id', $tenantKey))
                ->orWhereHasMorph('revisionable', [Event::class, LunchMenu::class], fn (Builder $content): Builder => $content->whereRelation('businessLocation', 'business_id', $tenantKey)))
            ->with(['revisionable', 'reviewer']);
    }

    public static function table(Table $table): Table
    {
        return RevisionsTable::configure($table);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRevisions::route('/'),
        ];
    }
}
