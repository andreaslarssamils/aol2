<?php

namespace App\Http\Resources\Api\V1;

use App\Models\BusinessLocation;
use App\Models\Event;
use Illuminate\Http\Request;

/**
 * Evenemang. Platsen länkas bara om den finns i katalogen (publicerad, publicerat företag).
 * Förutsätter att kommun, categories, businessLocation.business, slugs och media är laddade.
 *
 * @mixin Event
 */
class EventResource extends ApiResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $locale = $this->locale($request);

        return [
            'id' => $this->id,
            'slug' => $this->localizedSlug($locale),
            'namn' => $this->namn,
            'beskrivning' => $this->translate($this->beskrivning, $request),
            'all_day' => $this->all_day,
            'starts_at' => $this->dateTime($this->starts_at),
            'ends_at' => $this->dateTime($this->ends_at),
            'venue_name' => $this->venue_name,
            'adress' => $this->adress,
            'lat' => $this->coordinate($this->lat),
            'lng' => $this->coordinate($this->lng),
            'pris' => $this->pris,
            'ticket_url' => $this->ticket_url,
            'published_at' => $this->dateTime($this->published_at),
            'kommun' => KommunResource::make($this->whenLoaded('kommun')),
            'categories' => CategoryResource::collection($this->whenLoaded('categories')),
            'location' => $this->whenLoaded('businessLocation', fn (BusinessLocation $location): ?array => $location->isInCatalog() ? [
                'slug' => $location->localizedSlug($locale),
                'namn' => $location->display_name,
            ] : null),
            'images' => $this->images($this->resource),
        ];
    }
}
