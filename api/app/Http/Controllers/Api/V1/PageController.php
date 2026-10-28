<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\Api\V1\PageResource;
use App\Models\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Statisk sida via slug. En sida i någon av sajtens kommuner vinner över en nationell med samma slug. */
class PageController extends ApiController
{
    public function __invoke(Request $request, string $site, string $slug): PageResource|JsonResponse
    {
        $kommunIds = $this->kommunIds($request);

        $query = Page::query()
            ->published()
            ->where(fn (Builder $pages) => $pages->whereIn('kommun_id', $kommunIds)->orWhereNull('kommun_id'))
            ->with(['kommun', 'slugs', 'media']);

        $page = $this->findBySlug($query, $slug, $request, includeNational: true);

        return $page instanceof JsonResponse ? $page : PageResource::make($page);
    }
}
