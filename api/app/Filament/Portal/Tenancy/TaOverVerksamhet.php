<?php

namespace App\Filament\Portal\Tenancy;

use App\Enums\ManagedBy;
use App\Models\Business;
use App\Models\BusinessClaim;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Tenancy\RegisterTenant;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

/**
 * "Ta över din verksamhet": dit portalen skickar den som saknar business_user-rad. Sökningen
 * täcker katalogen – publicerade verksamheter som redaktionen lagt in och som ingen ägare har
 * ännu. Inskicket blir ett BusinessClaim som redaktionen avgör i /admin; beslutet mejlas.
 * Ärver RegisterTenant för routingen (/portal/ta-over) men skapar aldrig någon tenant.
 */
class TaOverVerksamhet extends RegisterTenant
{
    protected static ?string $slug = 'ta-over';

    public static function getLabel(): string
    {
        return 'Ta över din verksamhet';
    }

    /** Inloggning räcker – sidan finns just för den som inte har någon verksamhet än. */
    public static function canView(): bool
    {
        return Filament::auth()->check();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('business_id')
                    ->label('Verksamhet')
                    ->searchable()
                    ->required()
                    ->getSearchResultsUsing(fn (string $search): array => static::claimableQuery()
                        ->where('namn', 'ilike', '%'.trim($search).'%')
                        ->orderBy('namn')
                        ->limit(25)
                        ->pluck('namn', 'id')
                        ->all())
                    ->getOptionLabelUsing(fn (mixed $value): ?string => Business::query()->whereKey($value)->value('namn'))
                    ->rule(fn (): Exists => Rule::exists('businesses', 'id')->where(fn ($query) => $query->whereIn('id', static::claimableQuery()->select('id'))))
                    ->validationMessages(['exists' => 'Verksamheten finns inte i katalogen eller har redan en ägare.'])
                    ->helperText('Sök bland publicerade verksamheter som ännu inte har någon ägare i portalen.'),
                Textarea::make('message')
                    ->label('Meddelande till redaktionen')
                    ->rows(4)
                    ->maxLength(2000)
                    ->helperText('Berätta kort hur du är kopplad till verksamheten, så att redaktionen kan bekräfta det.'),
            ]);
    }

    public function register(): void
    {
        abort_unless(static::canView(), 404);

        $data = $this->form->getState();

        /** @var User $user */
        $user = Filament::auth()->user();
        $business = static::claimableQuery()->findOrFail($data['business_id']);

        if ($user->claims()->pending()->where('business_id', $business->id)->exists()) {
            Notification::make()
                ->warning()
                ->title("Du har redan ett väntande anspråk på {$business->namn}")
                ->body('Redaktionen hör av sig så snart det är avgjort.')
                ->send();

            return;
        }

        BusinessClaim::create([
            'business_id' => $business->id,
            'user_id' => $user->id,
            'message' => filled($data['message'] ?? null) ? trim($data['message']) : null,
        ]);

        Notification::make()
            ->success()
            ->title('Anspråket är skickat')
            ->body("Redaktionen granskar att du företräder {$business->namn}. Du får ett mejl när det är avgjort.")
            ->send();

        $this->form->fill();
    }

    /** Publicerade verksamheter som redaktionen hanterar och som ingen ägare är kopplad till. */
    public static function claimableQuery(): Builder
    {
        return Business::query()
            ->published()
            ->where('managed_by', ManagedBy::Editorial)
            ->whereDoesntHave('users');
    }

    public function getSubheading(): string|Htmlable|null
    {
        $pending = Filament::auth()->user()?->claims()->pending()->with('business')->get() ?? collect();

        if ($pending->isEmpty()) {
            return 'Sök upp din verksamhet i katalogen. Redaktionen bekräftar att du företräder den innan du får tillgång.';
        }

        return 'Väntar på redaktionen: '.$pending->map(fn (BusinessClaim $claim): string => $claim->business->namn)->join(', ').'.';
    }

    public function getRegisterFormAction(): Action
    {
        return Action::make('register')
            ->label('Skicka anspråk')
            ->submit('register');
    }

    protected function getRedirectUrl(): ?string
    {
        return null;
    }
}
