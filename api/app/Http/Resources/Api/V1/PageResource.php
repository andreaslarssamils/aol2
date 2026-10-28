<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Page;
use Illuminate\Http\Request;

/**
 * Statisk sida. kommun är null för nationella sidor.
 *
 * @mixin Page
 */
class PageResource extends ApiResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->localizedSlug($this->locale($request)),
            'titel' => $this->translate($this->titel, $request),
            'body' => $this->translate($this->body, $request),
            'published_at' => $this->published_at,
            'kommun' => $this->kommun === null ? null : KommunResource::make($this->kommun),
            'images' => $this->images($this->resource),
        ];
    }
}
