<?php

namespace App\Models;

use App\Observers\ApiCacheObserver;
use Database\Factories\SlugFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * URL per språk och ort för innehåll. En kanonisk rad per locale; gamla slugs
 * behålls med is_canonical = false så att frontend kan svara 301.
 */
#[Fillable(['slugable_type', 'slugable_id', 'kommun_id', 'locale', 'slug', 'is_canonical'])]
#[ObservedBy([ApiCacheObserver::class])]
class Slug extends Model
{
    /** @use HasFactory<SlugFactory> */
    use HasFactory;

    const UPDATED_AT = null;

    protected $attributes = [
        'is_canonical' => true,
    ];

    protected function casts(): array
    {
        return [
            'is_canonical' => 'boolean',
        ];
    }

    #[Scope]
    protected function canonical(Builder $query): void
    {
        $query->where('is_canonical', true);
    }

    /**
     * Slå upp en inkommande URL, kanonisk eller inte. kommun null = nationell.
     * Returnerar raden så att anroparen kan 301:a om is_canonical är false.
     */
    #[Scope]
    protected function lookup(Builder $query, string $slug, string $locale, Kommun|int|null $kommun = null, ?string $slugableClass = null): void
    {
        $query->where('locale', $locale)
            ->where('slug', $slug)
            ->where('kommun_id', $kommun instanceof Kommun ? $kommun->id : $kommun);

        if ($slugableClass !== null) {
            $query->where('slugable_type', (new $slugableClass)->getMorphClass());
        }
    }

    public function slugable(): MorphTo
    {
        return $this->morphTo();
    }

    public function kommun(): BelongsTo
    {
        return $this->belongsTo(Kommun::class);
    }
}
