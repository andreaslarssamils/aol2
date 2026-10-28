<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Offer;
use Illuminate\Http\Request;

/** @mixin Offer */
class OfferResource extends ApiResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'titel' => $this->translate($this->titel, $request),
            'beskrivning' => $this->translate($this->beskrivning, $request),
            'discount_type' => $this->discount_type?->value,
            'discount_value' => $this->discount_value === null ? null : (float) $this->discount_value,
            'code' => $this->code,
            'starts_at' => $this->starts_at,
            'ends_at' => $this->ends_at,
            'business' => BusinessResource::make($this->whenLoaded('business')),
        ];
    }
}
