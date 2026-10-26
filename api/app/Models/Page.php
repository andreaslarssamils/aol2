<?php

namespace App\Models;

use App\Enums\ContentStatus;
use App\Models\Concerns\HasContentStatus;
use App\Models\Concerns\HasRevisions;
use Database\Factories\PageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * Statiska sidor: Om oss, Kontakt, Integritetspolicy m.fl.
 * kommun_id null = nationell sida; en ortspecifik sida med samma slug överskrider den.
 */
#[Fillable(['kommun_id', 'slug', 'titel', 'body', 'status', 'published_at'])]
class Page extends Model implements HasMedia
{
    /** @use HasFactory<PageFactory> */
    use HasContentStatus, HasFactory, HasRevisions, InteractsWithMedia, SoftDeletes;

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

    #[Scope]
    protected function national(Builder $query): void
    {
        $query->whereNull('kommun_id');
    }

    /** Ortens egen sida om den finns, annars den nationella. */
    #[Scope]
    protected function forKommun(Builder $query, Kommun $kommun, string $slug): void
    {
        $query->where('slug', $slug)
            ->where(fn (Builder $q) => $q->where('kommun_id', $kommun->id)->orWhereNull('kommun_id'))
            ->orderByRaw('kommun_id IS NULL'); // ortspecifik först
    }

    public function kommun(): BelongsTo
    {
        return $this->belongsTo(Kommun::class);
    }
}
