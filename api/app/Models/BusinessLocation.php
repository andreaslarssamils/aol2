<?php

namespace App\Models;

use App\Models\Concerns\HasPromotions;
use App\Models\Concerns\HasRevisions;
use App\Models\Concerns\HasSlugs;
use Carbon\CarbonInterface;
use Database\Factories\BusinessLocationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Var ett företag finns – en rad per adress. Flera rader per ort är tillåtet,
 * men bara en kan vara primär. URL:en bor i slugs-tabellen. Öppettider och lunch hänger här.
 */
#[Fillable([
    'business_id', 'kommun_id', 'namn', 'telefon', 'adress',
    'postnummer', 'ort', 'lat', 'lng', 'is_primary',
])]
class BusinessLocation extends Model
{
    /** @use HasFactory<BusinessLocationFactory> */
    use HasFactory, HasPromotions, HasRevisions, HasSlugs, SoftDeletes;

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

    /** Lokalt namn om satt ("Acme Centrum"), annars företagsnamnet. */
    protected function displayName(): Attribute
    {
        return Attribute::get(fn () => $this->namn ?? $this->business?->namn);
    }

    /** Öppettider ett visst datum. En avvikelse vinner alltid över ordinarie tider. */
    public function hoursOn(CarbonInterface $date): Collection
    {
        $exceptions = $this->openingHourExceptions()->whereDate('date', $date)->orderBy('opens')->get();

        if ($exceptions->isNotEmpty()) {
            return $exceptions;
        }

        return $this->openingHours()->where('weekday', $date->dayOfWeekIso - 1)->get();
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
        return $this->hasMany(OpeningHour::class)->orderBy('weekday')->orderBy('opens');
    }

    public function openingHourExceptions(): HasMany
    {
        return $this->hasMany(OpeningHourException::class)->orderBy('date');
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
