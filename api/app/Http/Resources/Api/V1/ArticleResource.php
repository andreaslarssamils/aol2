<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Article;
use Illuminate\Http\Request;

/**
 * Listrad för ett reportage – utan brödtext, se ArticleDetailResource.
 *
 * @mixin Article
 */
class ArticleResource extends ApiResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->localizedSlug($this->locale($request)),
            'titel' => $this->translate($this->titel, $request),
            'ingress' => $this->translate($this->ingress, $request),
            'author_name' => $this->author_name,
            'featured' => $this->featured,
            'published_at' => $this->published_at,
            'kommun' => KommunResource::make($this->whenLoaded('kommun')),
            'images' => $this->images($this->resource),
        ];
    }
}
