<?php

namespace App\Models;

use Database\Factories\NewsletterSubscriberFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/** Prenumerant per ort. Double opt-in: confirmed_at sätts via token-länken. */
#[Fillable(['kommun_id', 'email', 'consented_at', 'confirmed_at', 'unsubscribed_at'])]
class NewsletterSubscriber extends Model
{
    /** @use HasFactory<NewsletterSubscriberFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(function (NewsletterSubscriber $subscriber) {
            $subscriber->token ??= (string) Str::uuid();
        });
    }

    protected function casts(): array
    {
        return [
            'consented_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'unsubscribed_at' => 'datetime',
        ];
    }

    /** Bekräftade och inte avprenumererade. */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->whereNotNull('confirmed_at')->whereNull('unsubscribed_at');
    }

    public function kommun(): BelongsTo
    {
        return $this->belongsTo(Kommun::class);
    }
}
