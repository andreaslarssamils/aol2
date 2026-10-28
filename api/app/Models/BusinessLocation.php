<?php

namespace App\Models;

use App\Enums\ContentStatus;
use App\Http\Requests\BusinessLocationRequest;
use App\Models\Concerns\HasContentStatus;
use App\Models\Concerns\HasPromotions;
use App\Models\Concerns\HasRevisions;
use App\Models\Concerns\HasSlugs;
use App\Observers\ApiCacheObserver;
use Carbon\CarbonInterface;
use Database\Factories\BusinessLocationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Var ett företag finns – en rad per adress. Flera rader per ort är tillåtet, men bara en kan
 * vara primär. URL:en bor i slugs-tabellen. Öppettider och lunch hänger här. Har egen status:
 * en lokalisering som skapas via revision är draft tills den godkänns, precis som annat innehåll.
 */
#[Fillable([
    'business_id', 'kommun_id', 'namn', 'telefon', 'adress',
    'postnummer', 'ort', 'lat', 'lng', 'is_primary', 'status', 'published_at',
])]
#[ObservedBy([ApiCacheObserver::class])]
class BusinessLocation extends Model
{
    /** @use HasFactory<BusinessLocationFactory> */
    use HasContentStatus, HasFactory, HasPromotions, HasRevisions, HasSlugs, SoftDeletes;

    protected $attributes = [
        'is_primary' => true,
        'status' => ContentStatus::Draft->value,
    ];

    protected function casts(): array
    {
        return [
            'lat' => 'decimal:7',
            'lng' => 'decimal:7',
            'is_primary' => 'boolean',
            'status' => ContentStatus::class,
            'published_at' => 'datetime',
        ];
    }

    /** Det katalogen visar: publicerad lokalisering vars företag också är publicerat (och inget av dem raderat). */
    #[Scope]
    protected function inCatalog(Builder $query): void
    {
        $query->published()->whereHas('business', fn (Builder $business) => $business->published());
    }

    /** Samma regel som inCatalog(), för en laddad rad. */
    public function isInCatalog(): bool
    {
        return $this->isPublished() && ($this->business?->isPublished() ?? false);
    }

    /** Lokalt namn om satt ("Acme Centrum"), annars företagsnamnet. */
    protected function displayName(): Attribute
    {
        return Attribute::get(fn () => $this->namn ?? $this->business?->namn);
    }

    /** Adressuppgifter och slug. Öppettider är egna rader och ingår inte. */
    public static function revisionRequest(): FormRequest
    {
        return new BusinessLocationRequest;
    }

    public function revisionBusiness(): ?Business
    {
        return $this->business;
    }

    public function revisionTitle(): string
    {
        return $this->display_name;
    }

    /**
     * Öppettider ett visst datum. En avvikelse vinner alltid över ordinarie tider. Är relationerna
     * laddade används de (ladda då rader som täcker datumet), annars frågas databasen.
     */
    public function hoursOn(CarbonInterface $date): Collection
    {
        $exceptions = $this->relationLoaded('openingHourExceptions')
            ? $this->openingHourExceptions->filter(fn (OpeningHourException $exception): bool => $exception->date->isSameDay($date))->sortBy('opens')->values()
            : $this->openingHourExceptions()->whereDate('date', $date)->orderBy('opens')->get();

        if ($exceptions->isNotEmpty()) {
            return $exceptions;
        }

        $weekday = $date->dayOfWeekIso - 1;

        return $this->relationLoaded('openingHours')
            ? $this->openingHours->where('weekday', $weekday)->sortBy('opens')->values()
            : $this->openingHours()->where('weekday', $weekday)->get();
    }

    /** Öppet vid tidpunkten? Intervall över midnatt (22–02) räknas även in på efternatten. */
    public function isOpenAt(CarbonInterface $at): bool
    {
        $time = $at->format('H:i');

        foreach ($this->hoursOn($at) as $hours) {
            [$opens, $closes] = self::interval($hours);

            if ($opens === null) {
                continue;
            }

            if ($closes <= $opens ? $time >= $opens : ($time >= $opens && $time < $closes)) {
                return true;
            }
        }

        foreach ($this->hoursOn($at->copy()->subDay()) as $hours) {
            [$opens, $closes] = self::interval($hours);

            if ($opens !== null && $closes <= $opens && $time < $closes) {
                return true;
            }
        }

        return false;
    }

    /** @return array{?string, ?string} Öppnar/stänger som "HH:MM", eller [null, null] när raden betyder stängt. */
    protected static function interval(OpeningHour|OpeningHourException $hours): array
    {
        if ($hours->closed || $hours->opens === null || $hours->closes === null) {
            return [null, null];
        }

        return [substr($hours->opens, 0, 5), substr($hours->closes, 0, 5)];
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

    /** Publicerad lunchmeny för innevarande ISO-vecka (appens tidszon). */
    public function currentLunchMenu(): HasOne
    {
        return $this->hasOne(LunchMenu::class)->published()->forIsoWeek(now());
    }

    /** Publicerade kommande (eller pågående) evenemang på platsen, närmast först. Mallposter ingår inte. */
    public function upcomingEvents(): HasMany
    {
        return $this->hasMany(Event::class)
            ->published()
            ->withoutRecurrenceTemplates()
            ->upcoming(now())
            ->orderBy('starts_at')
            ->orderBy('id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }
}
