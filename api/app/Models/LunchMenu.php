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

/** Veckans lunch per lokalisering. menu: {"mon": [...], "tue": [...]}. */
#[Fillable(['business_location_id', 'year', 'week', 'menu', 'status'])]
class LunchMenu extends Model
{
    /** @use HasFactory<LunchMenuFactory> */
    use HasContentStatus, HasFactory, HasRevisions;

    protected $attributes = [
        'status' => ContentStatus::Pending->value,
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'week' => 'integer',
            'menu' => 'array',
            'status' => ContentStatus::class,
        ];
    }

    public function businessLocation(): BelongsTo
    {
        return $this->belongsTo(BusinessLocation::class);
    }
}
