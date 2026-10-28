<?php

namespace App\Http\Resources\Api\V1;

use App\Models\BusinessLocation;
use Illuminate\Http\Request;

/**
 * Listrad för en lokalisering: visningsnamn, kanonisk slug, kategorier, adress och "öppet just nu".
 * Förutsätter att business.categories, business.media, kommun, slugs och öppettiderna är laddade.
 *
 * @mixin BusinessLocation
 */
class BusinessLocationResource extends ApiResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->localizedSlug($this->locale($request)),
            'namn' => $this->display_name,
            'business' => [
                'id' => $this->business->id,
                'namn' => $this->business->namn,
                'kind' => $this->business->kind->value,
            ],
            'categories' => CategoryResource::collection($this->business->categories),
            'adress' => $this->adress,
            'postnummer' => $this->postnummer,
            'ort' => $this->ort,
            'telefon' => $this->telefon,
            'lat' => $this->coordinate($this->lat),
            'lng' => $this->coordinate($this->lng),
            'is_primary' => $this->is_primary,
            'kommun' => KommunResource::make($this->whenLoaded('kommun')),
            'open_now' => $this->isOpenAt(now()),
            'images' => $this->images($this->business),
        ];
    }
}
