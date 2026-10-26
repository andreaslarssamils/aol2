<?php

namespace App\Models;

use App\Enums\PromotionPlacement;
use App\Enums\PromotionStatus;
use Database\Factories\PromotionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Cross-marketing: innehåll som visas på en annan orts yta under en period.
 * Länkar pekar alltid till innehållets kanoniska URL.
 */
#[Fillable([
    'promotable_type', 'promotable_id', 'kommun_id', 'placement', 'status', 'starts_at', 'ends_at',
    'priority', 'is_paid', 'price', 'invoiced_at', 'created_by',
])]
class Promotion extends Model
{
    /** @use HasFactory<PromotionFactory> */
    use HasFactory;

    protected $attributes = [
        'status' => PromotionStatus::Draft->value,
        'priority' => 0,
        'is_paid' => false,
    ];

    protected function casts(): array
    {
        return [
            'placement' => PromotionPlacement::class,
            'status' => PromotionStatus::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'priority' => 'integer',
            'is_paid' => 'boolean',
            'price' => 'decimal:2',
            'invoiced_at' => 'datetime',
        ];
    }

    /** Aktiva placeringar just nu: status active och inom datumfönstret. */
    #[Scope]
    protected function active(Builder $query, ?PromotionPlacement $placement = null): void
    {
        $query->where('status', PromotionStatus::Active)
            ->where('starts_at', '<=', now())
            ->where(fn (Builder $q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
            ->when($placement, fn (Builder $q) => $q->where('placement', $placement))
            ->orderByDesc('priority');
    }

    public function promotable(): MorphTo
    {
        return $this->morphTo();
    }

    public function kommun(): BelongsTo
    {
        return $this->belongsTo(Kommun::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
