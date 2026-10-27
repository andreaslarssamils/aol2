<?php

namespace App\Models;

use App\Enums\ContentStatus;
use App\Http\Requests\ArticleRequest;
use App\Models\Concerns\HasContentStatus;
use App\Models\Concerns\HasPromotions;
use App\Models\Concerns\HasRevisions;
use App\Models\Concerns\HasSlugs;
use Database\Factories\ArticleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Http\FormRequest;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/** Reportage. URL:en bor i slugs-tabellen. Bylinen i author_name överlever GDPR-radering av kontot. */
#[Fillable([
    'kommun_id', 'author_id', 'author_name', 'titel', 'ingress', 'body',
    'featured', 'status', 'published_at',
])]
class Article extends Model implements HasMedia
{
    /** @use HasFactory<ArticleFactory> */
    use HasContentStatus, HasFactory, HasPromotions, HasRevisions, HasSlugs, InteractsWithMedia, SoftDeletes;

    protected $attributes = [
        'featured' => false,
        'status' => ContentStatus::Draft->value,
    ];

    protected function casts(): array
    {
        return [
            'titel' => 'array',
            'ingress' => 'array',
            'body' => 'array',
            'featured' => 'boolean',
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
        return new ArticleRequest;
    }

    public function kommun(): BelongsTo
    {
        return $this->belongsTo(Kommun::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
