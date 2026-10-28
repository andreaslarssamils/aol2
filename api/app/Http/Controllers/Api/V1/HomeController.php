<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\PromotionPlacement;
use App\Http\Resources\Api\V1\ArticleResource;
use App\Http\Resources\Api\V1\EventResource;
use App\Http\Resources\Api\V1\LunchMenuResource;
use App\Http\Resources\Api\V1\PromotionResource;
use App\Models\Article;
use App\Models\Event;
use App\Models\LunchMenu;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Startsidan i ett anrop: tre utvalda reportage, sex kommande evenemang, aktiva placeringar
 * på ytan "startsida" och dagens lunchmenyer. Vädret är null tills SMHI kopplas på.
 */
class HomeController extends ApiController
{
    public const int FEATURED_ARTICLES = 3;

    public const int UPCOMING_EVENTS = 6;

    public function __invoke(Request $request): JsonResource
    {
        $kommunIds = $this->kommunIds($request);
        $now = now();

        $articles = Article::query()
            ->published()
            ->whereIn('kommun_id', $kommunIds)
            ->where('featured', true)
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->with(['kommun', 'slugs', 'media'])
            ->limit(self::FEATURED_ARTICLES)
            ->get();

        $events = Event::query()
            ->published()
            ->whereIn('kommun_id', $kommunIds)
            ->withoutRecurrenceTemplates()
            ->upcoming(now())
            ->orderBy('starts_at')
            ->orderBy('id')
            ->with(['kommun', 'categories', 'businessLocation.business', 'slugs', 'media'])
            ->limit(self::UPCOMING_EVENTS)
            ->get();

        $promotions = PromotionController::activeFor($kommunIds, PromotionPlacement::Startsida)->get();

        $today = LunchMenuResource::todayKey();

        $lunchMenus = LunchMenu::query()
            ->published()
            ->forIsoWeek($now)
            ->whereHas('businessLocation', fn (Builder $location) => $location
                ->whereIn('kommun_id', $kommunIds)
                ->inCatalog())
            ->with(['businessLocation.business', 'businessLocation.slugs'])
            ->get()
            ->filter(fn (LunchMenu $menu): bool => filled($menu->menu[$today] ?? null))
            ->sortBy(fn (LunchMenu $menu): string => mb_strtolower((string) $menu->businessLocation->display_name))
            ->values();

        return new JsonResource([
            'featured_articles' => ArticleResource::collection($articles),
            'upcoming_events' => EventResource::collection($events),
            'promotions' => PromotionResource::collection($promotions),
            'lunch_menus' => LunchMenuResource::collection($lunchMenus),
            'weather' => null,
        ]);
    }
}
