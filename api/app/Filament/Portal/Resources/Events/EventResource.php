<?php

namespace App\Filament\Portal\Resources\Events;

use App\Filament\Portal\Resources\Events\Pages\CreateEvent;
use App\Filament\Portal\Resources\Events\Pages\EditEvent;
use App\Filament\Portal\Resources\Events\Pages\ListEvents;
use App\Filament\Portal\Resources\Events\Schemas\EventForm;
use App\Filament\Portal\Resources\Events\Tables\EventsTable;
use App\Models\Event;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Verksamhetens egna evenemang: de som hänger på någon av dess lokaliseringar. Evenemanget hör
 * till verksamheten via lokaliseringen, så tenant-scopingen görs här. Bilder hamnar i "pending".
 */
class EventResource extends Resource
{
    protected static ?string $model = Event::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?int $navigationSort = 30;

    protected static ?string $modelLabel = 'evenemang';

    protected static ?string $pluralModelLabel = 'evenemang';

    protected static ?string $recordTitleAttribute = 'namn';

    protected static bool $isScopedToTenant = false;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereRelation('businessLocation', 'business_id', Filament::getTenant()?->getKey());
    }

    public static function form(Schema $schema): Schema
    {
        return EventForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EventsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEvents::route('/'),
            'create' => CreateEvent::route('/create'),
            'edit' => EditEvent::route('/{record}/edit'),
        ];
    }
}
