<?php

namespace App\Models\Concerns;

use App\Models\Promotion;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/** Innehåll som kan lyftas fram på andra orters ytor via promotions. */
trait HasPromotions
{
    public function promotions(): MorphMany
    {
        return $this->morphMany(Promotion::class, 'promotable');
    }
}
