<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Business;
use Illuminate\Http\Request;

/** @mixin Business */
class BusinessResource extends ApiResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'namn' => $this->namn,
            'kind' => $this->kind->value,
            'beskrivning' => $this->translate($this->beskrivning, $request),
            'epost' => $this->epost,
            'webbplats' => $this->webbplats,
            'categories' => CategoryResource::collection($this->whenLoaded('categories')),
        ];
    }
}
