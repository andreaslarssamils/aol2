<?php

namespace App\Models;

use App\Enums\SiteStatus;
use Database\Factories\SiteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** En sajt per kommun, t.ex. "Allt om Ludvika". */
#[Fillable(['kommun_id', 'namn', 'domain', 'status', 'settings'])]
class Site extends Model
{
    /** @use HasFactory<SiteFactory> */
    use HasFactory;

    protected $attributes = [
        'status' => SiteStatus::ComingSoon->value,
    ];

    protected function casts(): array
    {
        return [
            'status' => SiteStatus::class,
            'settings' => 'array',
        ];
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('status', SiteStatus::Active);
    }

    public function kommun(): BelongsTo
    {
        return $this->belongsTo(Kommun::class);
    }
}
