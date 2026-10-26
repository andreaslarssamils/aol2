<?php

namespace App\Models;

use App\Enums\BusinessUserRole;
use App\Enums\ClaimStatus;
use App\Enums\ManagedBy;
use Database\Factories\BusinessClaimFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

/**
 * Anspråk: en ägare vill ta över en post som redaktionen lagt in.
 * Godkänt anspråk skapar business_user-rad och sätter businesses.managed_by = self.
 */
#[Fillable(['business_id', 'user_id', 'status', 'verified_via', 'message', 'handled_by', 'handled_at'])]
class BusinessClaim extends Model
{
    /** @use HasFactory<BusinessClaimFactory> */
    use HasFactory;

    const UPDATED_AT = null;

    protected $attributes = [
        'status' => ClaimStatus::Pending->value,
    ];

    protected function casts(): array
    {
        return [
            'status' => ClaimStatus::class,
            'handled_at' => 'datetime',
        ];
    }

    #[Scope]
    protected function pending(Builder $query): void
    {
        $query->where('status', ClaimStatus::Pending);
    }

    public function approve(User $handler, BusinessUserRole $role = BusinessUserRole::Owner): void
    {
        DB::transaction(function () use ($handler, $role) {
            $this->update([
                'status' => ClaimStatus::Approved,
                'handled_by' => $handler->id,
                'handled_at' => now(),
            ]);

            if (! $this->business->users()->whereKey($this->user_id)->exists()) {
                $this->business->addUser($this->user, $role);
            }

            $this->business->update(['managed_by' => ManagedBy::Self]);
        });
    }

    public function reject(User $handler): void
    {
        $this->update([
            'status' => ClaimStatus::Rejected,
            'handled_by' => $handler->id,
            'handled_at' => now(),
        ]);
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }
}
