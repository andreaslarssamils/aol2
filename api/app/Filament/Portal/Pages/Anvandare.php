<?php

namespace App\Filament\Portal\Pages;

use App\Actions\Businesses\InviteBusinessUser;
use App\Enums\BusinessUserRole;
use App\Models\Business;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Gate;

/**
 * Vem som får hantera verksamheten (business_user). Alla medlemmar ser listan; bara owner
 * får bjuda in och ta bort (BusinessPolicy::inviteUser/removeUser).
 */
class Anvandare extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $slug = 'anvandare';

    protected static ?string $title = 'Användare';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?int $navigationSort = 60;

    public static function canAccess(): bool
    {
        $tenant = Filament::getTenant();

        return $tenant instanceof Business && (Filament::auth()->user()?->isMemberOf($tenant) ?? false);
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                EmbeddedTable::make(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->relationship(fn (): BelongsToMany => Filament::getTenant()->users())
            ->inverseRelationship('businesses')
            ->columns([
                TextColumn::make('name')
                    ->label('Namn'),
                TextColumn::make('email')
                    ->label('E-post'),
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
                $this->inviteAction(),
            ])
            ->recordActions([
                $this->removeAction(),
            ])
            ->defaultSort('name')
            ->paginated(false);
    }

    protected function inviteAction(): Action
    {
        return Action::make('invite')
            ->label('Bjud in')
            ->icon(Heroicon::OutlinedUserPlus)
            ->authorize(fn (): bool => Gate::allows('inviteUser', Filament::getTenant()))
            ->modalHeading('Bjud in en användare')
            ->modalDescription('Finns inget konto för adressen skapas ett, och personen får en länk för att välja lösenord.')
            ->schema([
                TextInput::make('email')
                    ->label('E-post')
                    ->email()
                    ->required()
                    ->maxLength(255),
                TextInput::make('name')
                    ->label('Namn')
                    ->maxLength(255)
                    ->helperText('Används bara om kontot är nytt.'),
                Select::make('role')
                    ->label('Roll')
                    ->options(BusinessUserRole::class)
                    ->default(BusinessUserRole::Manager->value)
                    ->required(),
            ])
            ->modalSubmitActionLabel('Skicka inbjudan')
            ->successNotificationTitle('Inbjudan är skickad')
            ->action(function (array $data, Action $action, InviteBusinessUser $invite): void {
                $invite->handle(
                    Filament::getTenant(),
                    Filament::auth()->user(),
                    $data['email'],
                    $data['name'] ?? null,
                    $data['role'] instanceof BusinessUserRole ? $data['role'] : BusinessUserRole::from($data['role']),
                );

                $action->success();
            });
    }

    protected function removeAction(): Action
    {
        return Action::make('remove')
            ->label('Ta bort')
            ->icon(Heroicon::OutlinedUserMinus)
            ->color('danger')
            ->authorize(fn (): bool => Gate::allows('removeUser', Filament::getTenant()))
            ->hidden(fn (User $record): bool => $record->is(Filament::auth()->user()))
            ->requiresConfirmation()
            ->modalHeading(fn (User $record): string => "Ta bort {$record->name}?")
            ->modalDescription('Personen förlorar tillgången till verksamheten i portalen. Kontot finns kvar.')
            ->successNotificationTitle('Användaren är borttagen')
            ->action(function (User $record, Action $action): void {
                Filament::getTenant()->users()->detach($record);

                $action->success();
            });
    }
}
