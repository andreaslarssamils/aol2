<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\CategoryType;
use App\Http\Resources\Api\V1\CategoryResource;
use App\Models\Category;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

/**
 * Kategorier som har publicerat innehåll på sajten: verksamhetskategorier med minst en
 * lokalisering i katalogen, evenemangskategorier med minst ett kommande publicerat evenemang.
 * ?type= avgränsar till en typ. Sport har ingen innehållskoppling ännu och ingår inte.
 */
class CategoryController extends ApiController
{
    /** @var list<CategoryType> */
    public const array TYPES = [CategoryType::Business, CategoryType::Event];

    public function __invoke(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'type' => ['sometimes', Rule::in(array_column(self::TYPES, 'value'))],
        ]);

        $kommunIds = $this->kommunIds($request);
        $types = isset($validated['type']) ? [CategoryType::from($validated['type'])] : self::TYPES;

        $categories = Category::query()
            ->where(function (Builder $query) use ($types, $kommunIds): void {
                foreach ($types as $type) {
                    $query->orWhere(fn (Builder $ofType) => $this->withContent($ofType, $type, $kommunIds));
                }
            })
            ->orderBy('type')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return CategoryResource::collection($categories);
    }

    /**
     * @param  Builder<Category>  $query
     * @param  list<int>  $kommunIds
     */
    protected function withContent(Builder $query, CategoryType $type, array $kommunIds): void
    {
        $query->where('type', $type);

        match ($type) {
            CategoryType::Business => $query->whereHas('businesses', fn (Builder $businesses) => $businesses
                ->published()
                ->whereHas('locations', fn (Builder $locations) => $locations
                    ->published()
                    ->whereIn('kommun_id', $kommunIds))),
            CategoryType::Event => $query->whereHas('events', fn (Builder $events) => $events
                ->published()
                ->withoutRecurrenceTemplates()
                ->whereIn('kommun_id', $kommunIds)
                ->upcoming(now())),
            CategoryType::Sport => $query->whereRaw('1 = 0'),
        };
    }
}
