<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\PromotionPlacement;
use App\Http\Resources\Api\V1\PromotionResource;
use App\Models\Promotion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

/** Aktiva placeringar riktade mot sajtens kommuner, högst prioritet först. ?placement= avgränsar till en yta. */
class PromotionController extends ApiController
{
    public function __invoke(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'placement' => ['sometimes', Rule::enum(PromotionPlacement::class)],
        ]);

        $placement = isset($validated['placement']) ? PromotionPlacement::from($validated['placement']) : null;

        return PromotionResource::collection(
            self::activeFor($this->kommunIds($request), $placement)->get(),
        );
    }

    /**
     * Aktiva placeringar med publicerat innehåll, sorterade på prioritet. Delas med startsidan.
     *
     * @param  list<int>  $kommunIds
     * @return Builder<Promotion>
     */
    public static function activeFor(array $kommunIds, ?PromotionPlacement $placement): Builder
    {
        return Promotion::query()
            ->active($placement)
            ->whereIn('kommun_id', $kommunIds)
            ->withPublishedPromotable()
            ->with(['promotable' => fn (MorphTo $promotable) => $promotable->morphWith(PromotionResource::PROMOTABLE_LOADS)])
            ->orderByDesc('starts_at')
            ->orderBy('id');
    }
}
