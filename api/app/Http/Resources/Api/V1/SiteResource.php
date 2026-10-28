<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Site;
use Illuminate\Http\Request;

/** @mixin Site */
class SiteResource extends ApiResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'slug' => $this->slug,
            'namn' => $this->namn,
            'domain' => $this->domain,
            'primary_kommun' => $this->primaryKommun === null ? null : KommunResource::make($this->primaryKommun),
            'kommuner' => KommunResource::collection($this->kommuner),
            'settings' => (object) ($this->settings ?? []),
        ];
    }
}
