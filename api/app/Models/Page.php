<?php

namespace App\Models;

use App\Enums\ContentStatus;
use App\Http\Requests\PageRequest;
use App\Models\Concerns\HasContentStatus;
use App\Models\Concerns\HasRevisions;
use App\Models\Concerns\HasSlugs;
use Database\Factories\PageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Http\FormRequest;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * Statiska sidor: Om oss, Kontakt, Integritetspolicy m.fl. URL:en bor i slugs-tabellen.
 * kommun_id null = nationell sida; en ortspecifik sida med samma slug överskrider den.
 */
#[Fillable(['kommun_id', 'titel', 'body', 'status', 'published_at'])]
class Page extends Model implements HasMedia
{
    /** @use HasFactory<PageFactory> */
    use HasContentStatus, HasFactory, HasRevisions, HasSlugs, InteractsWithMedia, SoftDeletes;

    protected $attributes = [
        'status' => ContentStatus::Draft->value,
    ];

    protected function casts(): array
    {
        return [
            'titel' => 'array',
            'body' => 'array',
            'status' => ContentStatus::class,
            'published_at' => 'datetime',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(Business::MEDIA_PENDING);
        $this->addMediaCollection(Business::MEDIA_IMAGES);
    }

    public static function revisionRequest(): FormRequest
    {
        return new PageRequest;
    }

    #[Scope]
    protected function national(Builder $query): void
    {
        $query->whereNull('kommun_id');
    }

    /** Ortens egen sida om den finns, annars den nationella – via kanonisk slug för locale. */
    #[Scope]
    protected function forKommun(Builder $query, Kommun|int $kommun, string $slug, string $locale = 'sv'): void
    {
        $kommunId = $kommun instanceof Kommun ? $kommun->id : $kommun;

        $query->whereHas('slugs', fn (Builder $slugs) => $slugs
            ->where('locale', $locale)
            ->where('slug', $slug)
            ->where('is_canonical', true)
            ->where(fn (Builder $q) => $q->where('kommun_id', $kommunId)->orWhereNull('kommun_id')))
            ->orderByRaw('pages.kommun_id IS NULL'); // ortspecifik först
    }

    public function kommun(): BelongsTo
    {
        return $this->belongsTo(Kommun::class);
    }
}
