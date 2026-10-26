<?php

namespace App\Models;

use App\Enums\ContentStatus;
use App\Models\Concerns\HasContentStatus;
use App\Models\Concerns\HasPromotions;
use App\Models\Concerns\HasRevisions;
use App\Models\Concerns\HasSlugs;
use App\Observers\EventObserver;
use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * Evenemang. URL:en bor i slugs-tabellen. Återkommande event: recurrence_rule (RRULE) på
 * moderposten, förekomsterna materialiseras som barnrader med parent_event_id.
 */
#[Fillable([
    'kommun_id', 'business_location_id', 'parent_event_id', 'recurrence_rule', 'created_by',
    'namn', 'beskrivning', 'all_day', 'starts_at', 'ends_at', 'venue_name', 'adress',
    'lat', 'lng', 'pris', 'ticket_url', 'status', 'published_at',
])]
#[ObservedBy([EventObserver::class])]
class Event extends Model implements HasMedia
{
    /** @use HasFactory<EventFactory> */
    use HasContentStatus, HasFactory, HasPromotions, HasRevisions, HasSlugs, InteractsWithMedia, SoftDeletes;

    protected $attributes = [
        'all_day' => false,
        'status' => ContentStatus::Draft->value,
    ];

    protected function casts(): array
    {
        return [
            'beskrivning' => 'array',
            'all_day' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'lat' => 'decimal:7',
            'lng' => 'decimal:7',
            'status' => ContentStatus::class,
            'published_at' => 'datetime',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(Business::MEDIA_PENDING);
        $this->addMediaCollection(Business::MEDIA_IMAGES);
    }

    public function kommun(): BelongsTo
    {
        return $this->belongsTo(Kommun::class);
    }

    /** Arrangör/plats om den finns i katalogen, annars används venue_name. */
    public function businessLocation(): BelongsTo
    {
        return $this->belongsTo(BusinessLocation::class);
    }

    /** Moderposten för en förekomst av ett återkommande event. */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'parent_event_id');
    }

    public function occurrences(): HasMany
    {
        return $this->hasMany(Event::class, 'parent_event_id')->orderBy('starts_at');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'event_category');
    }
}
