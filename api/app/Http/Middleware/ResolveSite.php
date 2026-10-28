<?php

namespace App\Http\Middleware;

use App\Models\Site;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Slår upp sajten i {site} (sites.slug). Bara aktiva sajter svarar – "kommer snart" och dolda
 * ger 404. Sajten och dess kommun-id:n (primärkommunen först) läggs på requesten så att varje
 * listfråga kan filtrera på kommun_id IN (...).
 */
class ResolveSite
{
    public function handle(Request $request, Closure $next): Response
    {
        $site = Site::query()
            ->active()
            ->where('slug', $request->route('site'))
            ->with(['kommuner', 'primaryKommun'])
            ->first();

        abort_if($site === null, 404, 'Sajten finns inte.');

        $request->attributes->set('site', $site);
        $request->attributes->set('kommun_ids', self::kommunIds($site));

        return $next($request);
    }

    /**
     * Kommunerna sajten visar, primärkommunen först.
     *
     * @return list<int>
     */
    public static function kommunIds(Site $site): array
    {
        $ids = $site->kommuner->pluck('id')->all();

        return array_values(array_unique(array_filter([$site->primary_kommun_id, ...$ids])));
    }
}
