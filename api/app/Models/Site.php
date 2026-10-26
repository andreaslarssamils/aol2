<?php

namespace App\Models;

use App\Enums\SiteStatus;
use Database\Factories\SiteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * En sajt, t.ex. "Allt om Ludvika" eller "Allt om Västerbergslagen" (Ludvika + Smedjebacken).
 * Innehåll hör fortfarande hemma i EN kommun – sajten bestämmer bara vilka kommuner den visar.
 */
#[Fillable(['namn', 'slug', 'domain', 'primary_kommun_id', 'status', 'settings'])]
#[RouteKey('slug')]
class Site extends Model
{
    /** @use HasFactory<SiteFactory> */
    use HasFactory;

    protected $attributes = [
        'status' => SiteStatus::ComingSoon->value,
    ];

    protected function casts(): array
    {
        return [
            'status' => SiteStatus::class,
            'settings' => 'array',
        ];
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('status', SiteStatus::Active);
    }

    /** Ort för väder, startsidans fokus och kanonisk URL när sajten täcker flera. */
    public function primaryKommun(): BelongsTo
    {
        return $this->belongsTo(Kommun::class, 'primary_kommun_id');
    }

    public function kommuner(): BelongsToMany
    {
        return $this->belongsToMany(Kommun::class, 'site_kommun')
            ->withPivot('sort_order')
            ->orderByPivot('sort_order');
    }

    /** För listfrågor: kommun_id IN (...). */
    public function kommunIds(): array
    {
        return $this->kommuner()->pluck('kommuner.id')->all();
    }
}
