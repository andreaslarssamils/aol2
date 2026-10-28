<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Category;
use Illuminate\Http\Request;

/** @mixin Category */
class CategoryResource extends ApiResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'slug' => $this->slug,
            'namn' => $this->translate($this->namn, $request),
            'type' => $this->type->value,
            'icon' => $this->icon,
        ];
    }
}
