<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Article;
use App\Models\BusinessLocation;
use App\Models\Event;
use App\Models\Offer;
use App\Models\Promotion;
use App\Support\ContentUrl;
use Illuminate\Http\Request;

/**
 * Placering med innehållet i samma form som respektive listrad och en länk till innehållets
 * kanoniska URL på dess hemsajt. is_paid = visa "Annons"-etikett.
 *
 * @mixin Promotion
 */
class PromotionResource extends ApiResource
{
    /**
     * Eager loads per innehållstyp, för with(['promotable' => fn (MorphTo $m) => $m->morphWith(...)]).
     *
     * @var array<class-string, list<string>>
     */
    public const array PROMOTABLE_LOADS = [
        Event::class => ['kommun', 'categories', 'businessLocation.business', 'slugs', 'media'],
        Article::class => ['kommun', 'slugs', 'media'],
        BusinessLocation::class => ['business.categories', 'business.media', 'kommun', 'slugs', 'openingHours', 'openingHourExceptions'],
        Offer::class => ['business.categories', 'business.primaryLocation.business', 'business.primaryLocation.kommun', 'business.primaryLocation.slugs'],
    ];

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $promotable = $this->promotable;

        return [
            'id' => $this->id,
            'placement' => $this->placement->value,
            'priority' => $this->priority,
            'is_paid' => $this->is_paid,
            'starts_at' => $this->dateTime($this->starts_at),
            'ends_at' => $this->dateTime($this->ends_at),
            'type' => $this->promotable_type,
            'content' => match (true) {
                $promotable instanceof Event => EventResource::make($promotable),
                $promotable instanceof Article => ArticleResource::make($promotable),
                $promotable instanceof BusinessLocation => BusinessLocationResource::make($promotable),
                $promotable instanceof Offer => OfferResource::make($promotable),
                default => null,
            },
            'link' => app(ContentUrl::class)->for($promotable, $this->locale($request)),
        ];
    }
}
