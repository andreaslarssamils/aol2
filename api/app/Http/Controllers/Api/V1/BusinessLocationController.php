<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\CategoryType;
use App\Enums\ContentStatus;
use App\Http\Resources\Api\V1\BusinessLocationDetailResource;
use App\Http\Resources\Api\V1\BusinessLocationResource;
use App\Models\BusinessLocation;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/** Katalogen: lokaliseringar vars företag är publicerat, i sajtens kommuner. */
class BusinessLocationController extends ApiController
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            ...$this->paginationRules(),
            'category' => ['sometimes', 'string', 'max:255'],
            'q' => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);

        $today = now();

        $locations = $this->publishedLocations($request)
            ->when($validated['category'] ?? null, fn (Builder $query, string $category) => $query
                ->whereHas('business.categories', fn (Builder $categories) => $categories
                    ->where('categories.slug', $category)
                    ->where('categories.type', CategoryType::Business)))
            ->when(filled($validated['q'] ?? null), fn (Builder $query) => $query->where(function (Builder $query) use ($validated): void {
                $term = $this->likeTerm($validated['q']);

                $query->where('business_locations.namn', 'ilike', $term)
                    ->orWhere('business_locations.ort', 'ilike', $term)
                    ->orWhere('businesses.namn', 'ilike', $term);
            }))
            ->with([
                'business.categories', 'business.media', 'kommun', 'slugs', 'openingHours',
                // "Öppet just nu" behöver dagens avvikelser och gårdagens (intervall över midnatt).
                'openingHourExceptions' => fn (HasMany $exceptions) => $exceptions
                    ->whereBetween('date', [$today->copy()->subDay()->toDateString(), $today->toDateString()]),
            ])
            ->orderByRaw('COALESCE(business_locations.namn, businesses.namn)')
            ->orderBy('business_locations.id')
            ->paginate($this->perPage($request))
            ->withQueryString();

        return BusinessLocationResource::collection($locations);
    }

    public function show(Request $request, string $site, string $slug): BusinessLocationDetailResource|JsonResponse
    {
        $location = $this->findBySlug($this->publishedLocations($request), $slug, $request);

        if ($location instanceof JsonResponse) {
            return $location;
        }

        $monday = now()->startOfWeek(CarbonInterface::MONDAY);

        $location->load([
            'business.categories', 'business.media', 'kommun', 'slugs', 'openingHours',
            'openingHourExceptions' => fn (HasMany $exceptions) => $exceptions
                ->whereBetween('date', [$monday->copy()->subDay()->toDateString(), $monday->copy()->addDays(6)->toDateString()]),
            'currentLunchMenu',
            'upcomingEvents' => fn (HasMany $events) => $events
                ->with(['kommun', 'categories', 'businessLocation.business', 'slugs', 'media'])
                ->limit(10),
        ]);

        return BusinessLocationDetailResource::make($location);
    }

    /**
     * Katalogen för sajten: publicerade lokaliseringar i sajtens kommuner vars företag är publicerat
     * och inte raderat – samma regel som BusinessLocation::inCatalog(). Joinen mot businesses ger
     * sortering och sök på visningsnamnet (lokalt namn, annars företagets).
     *
     * @return Builder<BusinessLocation>
     */
    protected function publishedLocations(Request $request): Builder
    {
        return BusinessLocation::query()
            ->select('business_locations.*')
            ->join('businesses', 'businesses.id', '=', 'business_locations.business_id')
            ->published()
            ->whereNull('businesses.deleted_at')
            ->where('businesses.status', ContentStatus::Published)
            ->whereIn('business_locations.kommun_id', $this->kommunIds($request));
    }
}
