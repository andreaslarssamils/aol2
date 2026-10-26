<?php

namespace App\Models;

use Database\Factories\OpeningHourFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** En rad per veckodag och lokalisering. weekday: 0 = måndag ... 6 = söndag. */
#[Fillable(['business_location_id', 'weekday', 'opens', 'closes', 'closed'])]
#[WithoutTimestamps]
class OpeningHour extends Model
{
    /** @use HasFactory<OpeningHourFactory> */
    use HasFactory;

    protected $attributes = [
        'closed' => false,
    ];

    protected function casts(): array
    {
        return [
            'weekday' => 'integer',
            'closed' => 'boolean',
        ];
    }

    public function businessLocation(): BelongsTo
    {
        return $this->belongsTo(BusinessLocation::class);
    }
}
