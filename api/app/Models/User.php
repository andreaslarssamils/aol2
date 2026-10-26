<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'phone', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /** Plattformsroller (spatie/laravel-permission). */
    public const string ROLE_ADMIN = 'admin';

    public const string ROLE_REDAKTOR = 'redaktor';

    public const string ROLE_VERKSAMHETSAGARE = 'verksamhetsagare';

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

    /** Adminpanelen är för redaktionen; verksamhetsägare får en egen portal. */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->hasAnyRole([self::ROLE_ADMIN, self::ROLE_REDAKTOR]);
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
}
