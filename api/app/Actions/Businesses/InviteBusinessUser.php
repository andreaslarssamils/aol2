<?php

namespace App\Actions\Businesses;

use App\Enums\BusinessUserRole;
use App\Models\Business;
use App\Models\User;
use App\Notifications\BusinessInvitation;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/**
 * Bjuder in en e-postadress till en verksamhet i portalen. Finns ingen användare skapas en
 * med slumpat lösenord och rollen verksamhetsägare, och länken i mejlet leder till
 * "välj lösenord" (lösenordsåterställningen). En befintlig användare får en länk in i
 * verksamheten. Finns business_user-raden redan behålls dess roll.
 */
class InviteBusinessUser
{
    public function handle(Business $business, User $inviter, string $email, ?string $name, BusinessUserRole $role): User
    {
        $email = Str::lower(trim($email));

        [$user, $isNewUser] = DB::transaction(function () use ($business, $email, $name, $role): array {
            $user = User::query()->firstWhere('email', $email);
            $isNewUser = $user === null;

            if ($isNewUser) {
                $user = User::create([
                    'name' => filled($name) ? trim($name) : Str::before($email, '@'),
                    'email' => $email,
                    'password' => Str::password(),
                ]);
            }

            if (! $user->isEditorial()) {
                $user->assignRole(Role::findOrCreate(User::ROLE_VERKSAMHETSAGARE, 'web'));
            }

            if (! $business->users()->whereKey($user->id)->exists()) {
                $business->addUser($user, $role);
            }

            return [$user, $isNewUser];
        });

        $user->notify((new BusinessInvitation($business, $inviter, $this->urlFor($user, $business, $isNewUser), $isNewUser))->afterCommit());

        return $user;
    }

    protected function urlFor(User $user, Business $business, bool $isNewUser): string
    {
        $panel = Filament::getPanel(User::PANEL_PORTAL);

        if ($isNewUser) {
            return $panel->getResetPasswordUrl(Password::broker()->createToken($user), $user);
        }

        return $panel->getUrl($business);
    }
}
