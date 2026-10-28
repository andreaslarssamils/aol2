<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Kommun;
use Illuminate\Http\Request;

/** @mixin Kommun */
class KommunResource extends ApiResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'namn' => $this->namn,
            'slug' => $this->slug,
            'kod' => $this->kod,
            'lat' => $this->coordinate($this->lat),
            'lng' => $this->coordinate($this->lng),
        ];
    }
}
