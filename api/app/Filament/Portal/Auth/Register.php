<?php

namespace App\Filament\Portal\Auth;

use App\Models\User;
use Filament\Auth\Pages\Register as BaseRegister;
use Illuminate\Database\Eloquent\Model;
use SensitiveParameter;
use Spatie\Permission\Models\Role;

/** Registrering i portalen. Nya användare får rollen verksamhetsägare och landar på "Ta över din verksamhet". */
class Register extends BaseRegister
{
    protected function handleRegistration(#[SensitiveParameter] array $data): Model
    {
        /** @var User $user */
        $user = parent::handleRegistration($data);

        $user->assignRole(Role::findOrCreate(User::ROLE_VERKSAMHETSAGARE, 'web'));

        return $user;
    }
}
