<?php

namespace App\Models\Concerns;

use App\Models\Kommun;
use App\Models\Slug;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Innehåll med URL i slugs-tabellen: en kanonisk slug per locale, gamla slugs
 * behålls som icke-kanoniska för 301. Sluggens kommun följer modellens kommun_id.
 */
trait HasSlugs
{
    public static function bootHasSlugs(): void
    {
        // Slugs lever kvar vid soft delete (historik/301) men städas vid hård radering.
        static::deleting(function (Model $model) {
            if (! method_exists($model, 'isForceDeleting') || $model->isForceDeleting()) {
                $model->slugs()->delete();
            }
        });
    }

    public function slugs(): MorphMany
    {
        return $this->morphMany(Slug::class, 'slugable');
    }

    public function canonicalSlug(string $locale): ?Slug
    {
        return $this->slugs()->where('locale', $locale)->where('is_canonical', true)->first();
    }

    public function slugFor(string $locale): ?string
    {
        return $this->canonicalSlug($locale)?->slug;
    }

    /**
     * Kanonisk slug för locale med fallback till standardspråket – det API:et alltid svarar med.
     * Går via slugs-relationen så att listor som laddat den inte ger en fråga per rad.
     */
    public function localizedSlug(string $locale): ?string
    {
        $canonical = $this->slugs->where('is_canonical', true);

        return $canonical->firstWhere('locale', $locale)?->slug
            ?? $canonical->firstWhere('locale', array_key_first(config('app.locales')))?->slug;
    }

    /**
     * Kanonisk slug per språk i config('app.locales')-ordning – bara språk som har en. Det
     * detaljsvaren ger för hreflang och språkväxling. Går via slugs-relationen (ladda den).
     *
     * @return array<string, string>
     */
    public function canonicalSlugs(): array
    {
        $canonical = $this->slugs->where('is_canonical', true)->pluck('slug', 'locale');
        $slugs = [];

        foreach (array_keys(config('app.locales')) as $locale) {
            if ($canonical->has($locale)) {
                $slugs[$locale] = $canonical[$locale];
            }
        }

        return $slugs;
    }

    /**
     * Gör $slug (eller "$slug-2", "$slug-3" ... om den är upptagen av annat innehåll)
     * till kanonisk för $locale. Tidigare kanonisk slug blir icke-kanonisk (301), och en
     * egen gammal slug som återanvänds befordras i stället för att dubbleras.
     */
    public function setSlug(string $locale, string $slug, Kommun|int|null $kommun = null): Slug
    {
        $kommunId = $kommun instanceof Kommun ? $kommun->id : ($kommun ?? $this->slugKommunId());

        // Två samtidiga sparningar kan välja samma suffix – då räknar vi om och försöker igen.
        for ($attempt = 1; ; $attempt++) {
            try {
                return DB::transaction(fn () => $this->storeSlug($locale, $this->freeSlug($locale, $slug, $kommunId), $kommunId));
            } catch (UniqueConstraintViolationException $e) {
                if ($attempt >= 3) {
                    throw $e;
                }
            }
        }
    }

    protected function storeSlug(string $locale, string $slug, ?int $kommunId): Slug
    {
        $existing = $this->slugs()
            ->where('locale', $locale)
            ->where('slug', $slug)
            ->where('kommun_id', $kommunId)
            ->first();

        if ($existing?->is_canonical) {
            return $existing;
        }

        $this->slugs()->where('locale', $locale)->where('is_canonical', true)->update(['is_canonical' => false]);

        if ($existing) {
            $existing->update(['is_canonical' => true]);

            return $existing;
        }

        return $this->slugs()->create([
            'kommun_id' => $kommunId,
            'locale' => $locale,
            'slug' => $slug,
            'is_canonical' => true,
        ]);
    }

    /** Första lediga av "$slug", "$slug-2", "$slug-3" ... Egna rader räknas inte som upptagna. */
    protected function freeSlug(string $locale, string $slug, ?int $kommunId): string
    {
        $taken = Slug::query()
            ->where('locale', $locale)
            ->where('kommun_id', $kommunId)
            ->where('slugable_type', $this->getMorphClass())
            ->where('slugable_id', '!=', $this->getKey())
            ->where(fn (Builder $q) => $q->where('slug', $slug)->orWhere('slug', 'like', $slug.'-%'))
            ->pluck('slug');

        if (! $taken->contains($slug)) {
            return $slug;
        }

        for ($i = 2; ; $i++) {
            if (! $taken->contains("{$slug}-{$i}")) {
                return "{$slug}-{$i}";
            }
        }
    }

    /** Vilken kommun sluggen hör till. null = nationell. */
    protected function slugKommunId(): ?int
    {
        return $this->kommun_id;
    }

    /** Hitta innehållet via kanonisk slug för locale och kommun (null = nationell). */
    #[Scope]
    protected function whereCanonicalSlug(Builder $query, string $slug, string $locale, Kommun|int|null $kommun = null): void
    {
        $kommunId = $kommun instanceof Kommun ? $kommun->id : $kommun;

        $query->whereHas('slugs', fn (Builder $slugs) => $slugs
            ->where('locale', $locale)
            ->where('slug', $slug)
            ->where('is_canonical', true)
            ->where('kommun_id', $kommunId));
    }
}
