<?php

namespace App\Policies;

use App\Models\Business;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;

/**
 * Gemensam bas för innehåll som ägs av en verksamhet. Redaktionen får allt (adminpanelen har
 * ingen egen behörighetsmodell). Alla andra kräver en business_user-rad på innehållets
 * verksamhet – owner och manager får samma: se, skapa och föreslå ändringar. Radera, återställa
 * och flytta görs aldrig i portalen; innehållet ändras bara via revisioner.
 */
abstract class BusinessContentPolicy
{
    /** Verksamheten innehållet hör till. */
    abstract protected function businessOf(Model $record): ?Business;

    public function before(User $user, string $ability): ?bool
    {
        return $user->isEditorial() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $this->isMemberOfCurrentBusiness($user);
    }

    public function create(User $user): bool
    {
        return $this->isMemberOfCurrentBusiness($user);
    }

    public function view(User $user, Model $record): bool
    {
        return $user->isMemberOf($this->businessOf($record));
    }

    public function update(User $user, Model $record): bool
    {
        return $user->isMemberOf($this->businessOf($record));
    }

    public function delete(User $user, Model $record): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    public function restore(User $user, Model $record): bool
    {
        return false;
    }

    public function restoreAny(User $user): bool
    {
        return false;
    }

    public function forceDelete(User $user, Model $record): bool
    {
        return false;
    }

    public function forceDeleteAny(User $user): bool
    {
        return false;
    }

    public function replicate(User $user, Model $record): bool
    {
        return false;
    }

    public function reorder(User $user): bool
    {
        return false;
    }

    /** I portalen är det aktuell tenant som gäller; utanför räcker det med någon business_user-rad. */
    protected function isMemberOfCurrentBusiness(User $user): bool
    {
        $tenant = Filament::getTenant();

        return $tenant instanceof Business
            ? $user->isMemberOf($tenant)
            : $user->businesses()->exists();
    }
}
