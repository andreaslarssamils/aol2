<?php

namespace App\Http\Resources\Api\V1;

use App\Models\BusinessLocation;
use App\Models\LunchMenu;
use Illuminate\Http\Request;

/**
 * Veckans lunch: hela menyn ({"mon": [...], ...}) plus dagens rätter (appens tidszon).
 *
 * @mixin LunchMenu
 */
class LunchMenuResource extends ApiResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $today = self::todayKey();

        return [
            'iso_year' => $this->iso_year,
            'week' => $this->week,
            'menu' => (object) ($this->menu ?? []),
            'today' => array_values((array) ($this->menu[$today] ?? [])),
            'location' => $this->whenLoaded('businessLocation', fn (BusinessLocation $location): array => [
                'slug' => $location->localizedSlug($this->locale($request)),
                'namn' => $location->display_name,
            ]),
        ];
    }

    /** Nyckeln för dagens rätter: mon, tue ... sun. */
    public static function todayKey(): string
    {
        return strtolower(now()->format('D'));
    }
}
