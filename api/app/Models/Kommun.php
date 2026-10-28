<?php

namespace App\Models;

use App\Observers\ApiCacheObserver;
use Database\Factories\KommunFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('kommuner')]
#[Fillable(['lan_id', 'namn', 'kod', 'slug', 'lat', 'lng'])]
#[RouteKey('slug')]
#[WithoutTimestamps]
#[ObservedBy([ApiCacheObserver::class])]
class Kommun extends Model
{
    /** @use HasFactory<KommunFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'lat' => 'decimal:7',
            'lng' => 'decimal:7',
        ];
    }

    public function lan(): BelongsTo
    {
        return $this->belongsTo(Lan::class);
    }

    /** Vilka sajter visar den här kommunen? */
    public function sites(): BelongsToMany
    {
        return $this->belongsToMany(Site::class, 'site_kommun')->withPivot('sort_order');
    }

    public function businessLocations(): HasMany
    {
        return $this->hasMany(BusinessLocation::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }

    public function pages(): HasMany
    {
        return $this->hasMany(Page::class);
    }

    public function newsletterSubscribers(): HasMany
    {
        return $this->hasMany(NewsletterSubscriber::class);
    }

    public function promotions(): HasMany
    {
        return $this->hasMany(Promotion::class);
    }
}
