<?php

namespace App\Models;

use App\Enums\ContentStatus;
use App\Enums\DiscountType;
use App\Http\Requests\OfferRequest;
use App\Models\Concerns\HasContentStatus;
use App\Models\Concerns\HasPromotions;
use App\Models\Concerns\HasRevisions;
use Database\Factories\OfferFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Http\FormRequest;

/** Erbjudande från ett företag. Går genom revisions-kön och syns via promotions. */
#[Fillable([
    'business_id', 'titel', 'beskrivning', 'discount_type', 'discount_value',
    'code', 'starts_at', 'ends_at', 'status',
])]
class Offer extends Model
{
    /** @use HasFactory<OfferFactory> */
    use HasContentStatus, HasFactory, HasPromotions, HasRevisions, SoftDeletes;

    protected $attributes = [
        'status' => ContentStatus::Draft->value,
    ];

    protected function casts(): array
    {
        return [
            'titel' => 'array',
            'beskrivning' => 'array',
            'discount_type' => DiscountType::class,
            'discount_value' => 'decimal:2',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'status' => ContentStatus::class,
        ];
    }

    public static function revisionRequest(): FormRequest
    {
        return new OfferRequest;
    }

    public function revisionBusiness(): ?Business
    {
        return $this->business;
    }

    public function revisionKommun(): ?Kommun
    {
        return $this->business?->revisionKommun();
    }

    #[Scope]
    protected function inKommun(Builder $query, Kommun|int $kommun): void
    {
        $query->whereHas('business', fn (Builder $business) => $business->inKommun($kommun));
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }
}
