<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Site;
use App\Models\Slug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Gemensamt för det publika API:et: sajt, kommuner och locale från ResolveSite/ResolveLocale,
 * paginering och uppslag via slug med 301 till den kanoniska.
 */
abstract class ApiController extends Controller
{
    protected function site(Request $request): Site
    {
        return $request->attributes->get('site');
    }

    /** @return list<int> */
    protected function kommunIds(Request $request): array
    {
        return $request->attributes->get('kommun_ids', []);
    }

    protected function locale(Request $request): string
    {
        return $request->attributes->get('locale') ?? array_key_first(config('app.locales'));
    }

    /** @return array<string, list<string>> */
    protected function paginationRules(): array
    {
        return [
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'between:1,'.config('api.pagination.max_per_page')],
        ];
    }

    protected function perPage(Request $request): int
    {
        return $request->integer('per_page', (int) config('api.pagination.per_page'));
    }

    /** Fritextsök med escapade LIKE-jokertecken. */
    protected function likeTerm(string $term): string
    {
        return '%'.addcslashes($term, '%_\\').'%';
    }

    /**
     * Publicerat innehåll via slug – kanonisk eller gammal – i sajtens kommuner (och nationellt
     * när $includeNational). Kanonisk rad vinner över gammal, ortens post över nationell och
     * primärkommunen över övriga. Är sluggen inte den kanoniska för vald locale blir svaret
     * 301 med den kanoniska i body, så att frontend kan omdirigera.
     *
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query  Publicerat innehåll, redan avgränsat till sajten.
     * @return TModel|JsonResponse
     */
    protected function findBySlug(Builder $query, string $slug, Request $request, bool $includeNational = false): Model|JsonResponse
    {
        $locale = $this->locale($request);
        $kommunIds = $this->kommunIds($request);
        $rank = array_flip($kommunIds);

        $candidates = Slug::query()
            ->where('slugable_type', $query->getModel()->getMorphClass())
            ->where('slug', $slug)
            ->where(fn (Builder $slugs) => $slugs
                ->whereIn('kommun_id', $kommunIds)
                ->when($includeNational, fn (Builder $slugs) => $slugs->orWhereNull('kommun_id')))
            ->get()
            ->sortBy(fn (Slug $candidate): string => sprintf(
                '%d-%03d-%d',
                $candidate->is_canonical ? 0 : 1,
                $candidate->kommun_id === null ? 999 : ($rank[$candidate->kommun_id] ?? 998),
                $candidate->locale === $locale ? 0 : 1,
            ));

        foreach ($candidates as $candidate) {
            $content = (clone $query)->whereKey($candidate->slugable_id)->first();

            if ($content === null) {
                continue;
            }

            $canonical = $content->localizedSlug($locale);

            if ($canonical === null || $canonical === $slug) {
                return $content;
            }

            return response()->json(['redirect_to' => $canonical], 301);
        }

        abort(404);
    }
}
