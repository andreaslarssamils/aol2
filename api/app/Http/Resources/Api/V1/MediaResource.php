<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Bild i collection "images". Mått sätts av StoreImageDimensions vid uppladdning;
 * alt-text läses från custom property "alt" när redaktionen satt en.
 *
 * @mixin Media
 */
class MediaResource extends ApiResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'url' => $this->getUrl(),
            'alt' => $this->getCustomProperty('alt'),
            'width' => $this->getCustomProperty('width'),
            'height' => $this->getCustomProperty('height'),
            'mime_type' => $this->mime_type,
        ];
    }
}
