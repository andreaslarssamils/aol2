<?php

namespace App\Models;

use App\Enums\BusinessKind;
use App\Enums\BusinessUserRole;
use App\Enums\ContentStatus;
use App\Enums\ManagedBy;
use App\Models\Concerns\HasContentStatus;
use App\Models\Concerns\HasRevisions;
use Database\Factories\BusinessFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * Företaget eller föreningen – vem man är. Var man finns bor i
 * BusinessLocation, vem som får hantera den i pivoten business_user.
 */
#[Fillable([
    'kind', 'managed_by', 'auto_approve', 'namn', 'beskrivning',
    'epost', 'webbplats', 'status', 'published_at',
])]
class Business extends Model implements HasMedia
{
    /** @use HasFactory<BusinessFactory> */
    use HasContentStatus, HasFactory, HasRevisions, InteractsWithMedia, SoftDeletes;

    /** Ägaruppladdningar hamnar här och flyttas till "images" vid godkännande. */
    public const string MEDIA_PENDING = 'pending';

    public const string MEDIA_IMAGES = 'images';

    protected $attributes = [
        'kind' => BusinessKind::Company->value,
        'managed_by' => ManagedBy::Editorial->value,
        'auto_approve' => false,
        'status' => ContentStatus::Draft->value,
    ];

    protected function casts(): array
    {
        return [
            'kind' => BusinessKind::class,
            'managed_by' => ManagedBy::class,
            'auto_approve' => 'boolean',
            'beskrivning' => 'array',
            'status' => ContentStatus::class,
            'published_at' => 'datetime',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::MEDIA_PENDING);
        $this->addMediaCollection(self::MEDIA_IMAGES);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withPivot('role', 'created_at');
    }

    public function addUser(User $user, BusinessUserRole $role = BusinessUserRole::Owner): void
    {
        $this->users()->attach($user, ['role' => $role->value, 'created_at' => now()]);
    }

    public function locations(): HasMany
    {
        return $this->hasMany(BusinessLocation::class);
    }

    public function primaryLocation(): HasOne
    {
        return $this->hasOne(BusinessLocation::class)->where('is_primary', true);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    public function offers(): HasMany
    {
        return $this->hasMany(Offer::class);
    }
}
