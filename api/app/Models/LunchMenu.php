<?php

namespace App\Models;

use App\Casts\PlainText;
use App\Enums\ContentStatus;
use App\Http\Requests\LunchMenuRequest;
use App\Models\Concerns\HasContentStatus;
use App\Models\Concerns\HasRevisions;
use App\Observers\ApiCacheObserver;
use Carbon\CarbonInterface;
use Database\Factories\LunchMenuFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Http\FormRequest;

/** Veckans lunch per lokalisering. menu: {"mon": [...], "tue": [...]}. */
#[Fillable(['business_location_id', 'iso_year', 'week', 'menu', 'status', 'published_at'])]
#[ObservedBy([ApiCacheObserver::class])]
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
            'menu' => PlainText::class,
            'status' => ContentStatus::class,
            'published_at' => 'datetime',
        ];
    }

    public static function revisionRequest(): FormRequest
    {
        return new LunchMenuRequest;
    }

    public function revisionBusiness(): ?Business
    {
        return $this->businessLocation?->business;
    }

    public function revisionTitle(): string
    {
        return "Lunch v. {$this->week} {$this->iso_year} – {$this->businessLocation?->display_name}";
    }

    public function revisionKommun(): ?Kommun
    {
        return $this->businessLocation?->kommun;
    }

    /** Menyn för datumets ISO-vecka i visningstidszonen – vecka 1 kan börja i december, därför iso_year. */
    #[Scope]
    protected function forIsoWeek(Builder $query, CarbonInterface $date): void
    {
        $date = $date->copy()->setTimezone(config('app.display_timezone'));

        $query->where('iso_year', $date->isoWeekYear)->where('week', $date->isoWeek);
    }

    #[Scope]
    protected function inKommun(Builder $query, Kommun|int $kommun): void
    {
        $query->whereHas('businessLocation', fn (Builder $location) => $location->inKommun($kommun));
    }

    public function businessLocation(): BelongsTo
    {
        return $this->belongsTo(BusinessLocation::class);
    }
}
