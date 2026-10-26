<?php

namespace App\Models;

use App\Models\Concerns\HasPromotions;
use App\Models\Concerns\HasRevisions;
use Database\Factories\BusinessLocationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Var ett företag finns – en rad per ort. Sidan /verksamheter/{slug}
 * på ortens sajt. Öppettider och lunch hänger här.
 */
#[Fillable([
    'business_id', 'kommun_id', 'slug', 'namn', 'telefon', 'adress',
    'postnummer', 'ort', 'lat', 'lng', 'is_primary',
])]
class BusinessLocation extends Model
{
    /** @use HasFactory<BusinessLocationFactory> */
    use HasFactory, HasPromotions, HasRevisions, SoftDeletes;

    protected $attributes = [
        'is_primary' => true,
    ];

    protected function casts(): array
    {
        return [
            'lat' => 'decimal:7',
            'lng' => 'decimal:7',
            'is_primary' => 'boolean',
        ];
    }

    /** Lokalt namn om satt ("Acme Ludvika"), annars företagsnamnet. */
    protected function displayName(): Attribute
    {
        return Attribute::get(fn () => $this->namn ?? $this->business?->namn);
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function kommun(): BelongsTo
    {
        return $this->belongsTo(Kommun::class);
    }

    public function openingHours(): HasMany
    {
        return $this->hasMany(OpeningHour::class)->orderBy('weekday');
    }

    public function lunchMenus(): HasMany
    {
        return $this->hasMany(LunchMenu::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }
}
