<?php

namespace App\Models;

use Database\Factories\LanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('lan')]
#[Fillable(['namn', 'kod'])]
#[WithoutTimestamps]
class Lan extends Model
{
    /** @use HasFactory<LanFactory> */
    use HasFactory;

    public function kommuner(): HasMany
    {
        return $this->hasMany(Kommun::class);
    }
}
