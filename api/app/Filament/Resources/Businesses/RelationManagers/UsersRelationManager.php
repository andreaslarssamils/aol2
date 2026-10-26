<?php

namespace App\Filament\Resources\Businesses\RelationManagers;

use App\Enums\BusinessUserRole;
use App\Models\User;
use Filament\Actions\AttachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/** Vem som får hantera verksamheten (business_user). Rollen styr t.ex. vem som får bjuda in fler. */
class UsersRelationManager extends RelationManager
{
    protected static string $relationship = 'users';

    protected static ?string $title = 'Användare';

    protected static ?string $modelLabel = 'användare';

    protected static ?string $pluralModelLabel = 'användare';

    protected static ?string $recordTitleAttribute = 'name';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                static::roleSelect(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Namn')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('E-post')
                    ->searchable(),
                TextColumn::make('role')
                    ->label('Roll')
                    ->badge()
                    ->state(fn (User $record): ?string => BusinessUserRole::tryFrom((string) $record->pivot->role)?->getLabel()),
                TextColumn::make('since')
                    ->label('Sedan')
                    ->state(fn (User $record): ?string => $record->pivot->created_at)
                    ->date('Y-m-d'),
            ])
            ->headerActions([
                AttachAction::make()
                    ->label('Koppla användare')
                    ->recordSelectSearchColumns(['name', 'email'])
                    ->schema(fn (AttachAction $action): array => [
                        $action->getRecordSelect(),
                        static::roleSelect(),
                    ])
                    ->mutateFormDataUsing(fn (array $data): array => [...$data, 'created_at' => now()]),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Ändra roll'),
                DetachAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DetachBulkAction::make(),
                ]),
            ]);
    }

    protected static function roleSelect(): Select
    {
        return Select::make('role')
            ->label('Roll')
            ->options(BusinessUserRole::class)
            ->default(BusinessUserRole::Owner->value)
            ->required();
    }
}
