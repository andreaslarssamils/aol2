<?php

namespace App\Models;

use App\Enums\RevisionStatus;
use Database\Factories\RevisionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Godkännandeflödet. Publicerat innehåll rörs inte förrän revisionen
 * godkänns. Godkända rader raderas aldrig – de är versionshistorik.
 */
#[Fillable([
    'revisionable_type', 'revisionable_id', 'submitted_by', 'payload',
    'status', 'reviewed_by', 'reviewed_at', 'review_comment',
])]
class Revision extends Model
{
    /** @use HasFactory<RevisionFactory> */
    use HasFactory;

    protected $attributes = [
        'status' => RevisionStatus::Pending->value,
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'status' => RevisionStatus::class,
            'reviewed_at' => 'datetime',
        ];
    }

    #[Scope]
    protected function pending(Builder $query): void
    {
        $query->where('status', RevisionStatus::Pending);
    }

    public function revisionable(): MorphTo
    {
        return $this->morphTo();
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
