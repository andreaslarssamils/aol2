<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Event;
use Illuminate\Http\Request;

/**
 * Detaljsidan: listraden plus kanonisk slug per språk.
 *
 * @mixin Event
 */
class EventDetailResource extends EventResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            ...parent::toArray($request),
            'slugs' => $this->slugs(),
        ];
    }
}
