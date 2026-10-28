<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Article;
use Illuminate\Http\Request;

/** @mixin Article */
class ArticleDetailResource extends ArticleResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            ...parent::toArray($request),
            'body' => $this->translate($this->body, $request),
        ];
    }
}
