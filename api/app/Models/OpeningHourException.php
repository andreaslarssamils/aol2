<?php

namespace App\Models;

use Database\Factories\OpeningHourExceptionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Röda dagar och semesterstängt. Vinner alltid över OpeningHour för sitt datum. */
#[Fillable(['business_location_id', 'date', 'opens', 'closes', 'closed', 'note'])]
#[WithoutTimestamps]
class OpeningHourException extends Model
{
    /** @use HasFactory<OpeningHourExceptionFactory> */
    use HasFactory;

    protected $attributes = [
        'closed' => false,
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'closed' => 'boolean',
        ];
    }

    public function businessLocation(): BelongsTo
    {
        return $this->belongsTo(BusinessLocation::class);
    }
}
