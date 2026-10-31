<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\BusinessUserRole;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'phone', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser, HasTenants
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable, SoftDeletes;

    /** Plattformsroller (spatie/laravel-permission). */
    public const string ROLE_ADMIN = 'admin';

    public const string ROLE_REDAKTOR = 'redaktor';

    public const string ROLE_VERKSAMHETSAGARE = 'verksamhetsagare';

    /** Panel-id för verksamhetsägarnas portal (App\Providers\Filament\PortalPanelProvider). */
    public const string PANEL_PORTAL = 'portal';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /** Redaktionen (admin och redaktör) publicerar direkt och får flytta innehåll mellan verksamheter. */
    public function isEditorial(): bool
    {
        return $this->hasAnyRole([self::ROLE_ADMIN, self::ROLE_REDAKTOR]);
    }

    /**
     * Adminpanelen är för redaktionen; alla andra hör hemma i portalen. En portalanvändare utan
     * business_user-rad släpps in och landar på sidan "Ta över din verksamhet".
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return match ($panel->getId()) {
            self::PANEL_PORTAL => ! $this->isEditorial(),
            default => $this->isEditorial(),
        };
    }

    /** Portalens tenants: verksamheterna användaren har en business_user-rad på. */
    public function getTenants(Panel $panel): Collection
    {
        return $this->businesses;
    }

    public function canAccessTenant(Model $tenant): bool
    {
        return $this->isMemberOf($tenant);
    }

    /** Rollen i business_user för verksamheten, eller null utan rad. */
    public function businessRole(Business|int|null $business): ?BusinessUserRole
    {
        if ($business === null) {
            return null;
        }

        $role = $this->businesses()->whereKey($business)->first()?->pivot->role;

        return $role === null ? null : BusinessUserRole::from($role);
    }

    /** Har en business_user-rad på verksamheten, oavsett roll. */
    public function isMemberOf(Business|int|null $business): bool
    {
        return $this->businessRole($business) !== null;
    }

    /** Ägarrollen i business_user – den enda som får bjuda in och ta bort användare. */
    public function isOwnerOf(Business|int|null $business): bool
    {
        return $this->businessRole($business) === BusinessUserRole::Owner;
    }

    public function businesses(): BelongsToMany
    {
        return $this->belongsToMany(Business::class)->withPivot('role', 'created_at');
    }

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class, 'author_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class, 'created_by');
    }

    public function claims(): HasMany
    {
        return $this->hasMany(BusinessClaim::class);
    }
}
