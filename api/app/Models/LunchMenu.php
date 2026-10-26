<?php

namespace App\Models;

use App\Enums\ContentStatus;
use App\Models\Concerns\HasContentStatus;
use App\Models\Concerns\HasRevisions;
use Database\Factories\LunchMenuFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/** Veckans lunch per lokalisering. menu: {"mon": [...], "tue": [...]}. */
#[Fillable(['business_location_id', 'iso_year', 'week', 'menu', 'status', 'published_at'])]
class LunchMenu extends Model
{
    /** @use HasFactory<LunchMenuFactory> */
    use HasContentStatus, HasFactory, HasRevisions, SoftDeletes;

    protected $attributes = [
        'status' => ContentStatus::Draft->value,
    ];

    protected function casts(): array
    {
        return [
            'iso_year' => 'integer',
            'week' => 'integer',
            'menu' => 'array',
            'status' => ContentStatus::class,
            'published_at' => 'datetime',
        ];
    }

    public function businessLocation(): BelongsTo
    {
        return $this->belongsTo(BusinessLocation::class);
    }
}
