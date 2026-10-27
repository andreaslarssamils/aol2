<?php

namespace App\Models;

use App\Enums\ContentStatus;
use App\Http\Requests\LunchMenuRequest;
use App\Models\Concerns\HasContentStatus;
use App\Models\Concerns\HasRevisions;
use Database\Factories\LunchMenuFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Http\FormRequest;

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
