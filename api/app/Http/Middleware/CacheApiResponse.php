<?php

namespace App\Http\Middleware;

use App\Models\Site;
use App\Support\ApiCache;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cachar lyckade svar per sajt, locale och URL, taggade med sajt och svarstyp (ApiCache).
 * Innehållsändringar tömmer taggen via ApiCacheObserver; TTL:en är bara ett skyddsnät.
 * Svaret får X-Cache: HIT/MISS. Ligger innanför SetCacheHeaders så att en träff ger samma ETag.
 */
class CacheApiResponse
{
    public function __construct(protected ApiCache $cache) {}

    /** Middleware-sträng för rutter: typ och valfri TTL i sekunder. */
    public static function for(string $type, ?int $ttl = null): string
    {
        return static::class.':'.$type.($ttl === null ? '' : ",{$ttl}");
    }

    public function handle(Request $request, Closure $next, string $type, ?int $ttl = null): Response
    {
        $site = $request->attributes->get('site');

        if (! $site instanceof Site || ! $request->isMethodCacheable() || ! $this->cache->enabled()) {
            return $next($request);
        }

        $key = $this->key($request);
        $hit = $this->cache->get($site, $type, $key);

        if (is_array($hit)) {
            return response($hit['content'], $hit['status'], $hit['headers'])->header('X-Cache', 'HIT');
        }

        $response = $next($request);

        if ($response->isSuccessful() && is_string($response->getContent()) && $response->getContent() !== '') {
            $this->cache->put($site, $type, $key, [
                'content' => $response->getContent(),
                'status' => $response->getStatusCode(),
                'headers' => ['Content-Type' => $response->headers->get('Content-Type')],
            ], $ttl);
        }

        $response->headers->set('X-Cache', 'MISS');

        return $response;
    }

    /** Sajten ligger i taggen; nyckeln skiljer på locale, sökväg och (sorterad) query. */
    protected function key(Request $request): string
    {
        $query = $request->query();
        ksort($query);

        return sha1($request->attributes->get('locale').'|'.$request->path().'|'.http_build_query($query));
    }
}
