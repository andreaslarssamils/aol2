<?php

namespace App\Policies;

use App\Models\Business;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Verksamheten själv. Medlemmar (owner och manager) får se och föreslå ändringar; bara owner
 * får bjuda in och ta bort användare. Nya verksamheter skapas av redaktionen eller via anspråk.
 */
class BusinessPolicy extends BusinessContentPolicy
{
    protected function businessOf(Model $record): ?Business
    {
        return $record instanceof Business ? $record : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->businesses()->exists();
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function inviteUser(User $user, Business $business): bool
    {
        return $user->isOwnerOf($business);
    }

    public function removeUser(User $user, Business $business): bool
    {
        return $user->isOwnerOf($business);
    }
}
