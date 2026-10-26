<?php

namespace App\Filament\Resources\Sites\RelationManagers;

use App\Models\Kommun;
use Filament\Actions\AttachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/** Vilka kommuner sajten visar (site_kommun). Primärkommunen kan inte kopplas bort. */
class KommunerRelationManager extends RelationManager
{
    protected static string $relationship = 'kommuner';

    protected static ?string $title = 'Kommuner';

    protected static ?string $modelLabel = 'kommun';

    protected static ?string $pluralModelLabel = 'kommuner';

    protected static ?string $recordTitleAttribute = 'namn';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                static::sortOrderInput(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('namn')
                    ->label('Namn')
                    ->searchable(),
                TextColumn::make('kod')
                    ->label('SCB-kod'),
                TextColumn::make('lan.namn')
                    ->label('Län'),
                TextColumn::make('sort_order')
                    ->label('Sortering')
                    ->state(fn (Kommun $record): int => (int) $record->pivot->sort_order),
                IconColumn::make('is_primary')
                    ->label('Primär')
                    ->boolean()
                    ->state(fn (Kommun $record): bool => $this->isPrimary($record)),
            ])
            ->headerActions([
                AttachAction::make()
                    ->label('Lägg till kommun')
                    ->preloadRecordSelect()
                    ->recordSelectSearchColumns(['namn', 'kod'])
                    ->schema(fn (AttachAction $action): array => [
                        $action->getRecordSelect(),
                        static::sortOrderInput(),
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
                DetachAction::make()
                    ->hidden(fn (Kommun $record): bool => $this->isPrimary($record)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DetachBulkAction::make(),
                ]),
            ]);
    }

    protected function isPrimary(Kommun $kommun): bool
    {
        return $kommun->id === $this->getOwnerRecord()->primary_kommun_id;
    }

    protected static function sortOrderInput(): TextInput
    {
        return TextInput::make('sort_order')
            ->label('Sortering')
            ->integer()
            ->default(0)
            ->required();
    }
}
