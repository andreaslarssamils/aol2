<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Innehållsspråk för svaret: ?locale= vinner över Accept-Language. Bara språk i
 * config('app.locales') accepteras; allt annat faller tillbaka till standardspråket (sv).
 * Svaret varierar på Accept-Language så att mellanliggande cachar inte blandar språk.
 */
class ResolveLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = self::resolve($request);

        app()->setLocale($locale);
        $request->attributes->set('locale', $locale);

        $response = $next($request);
        $response->headers->set('Vary', 'Accept-Language');

        return $response;
    }

    public static function resolve(Request $request): string
    {
        $locales = array_keys(config('app.locales'));
        $requested = $request->query('locale');

        if (is_string($requested) && in_array($requested, $locales, true)) {
            return $requested;
        }

        return $request->getPreferredLanguage($locales) ?? $locales[0];
    }
}
