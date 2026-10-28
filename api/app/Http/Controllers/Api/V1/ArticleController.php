<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\Api\V1\ArticleDetailResource;
use App\Http\Resources\Api\V1\ArticleResource;
use App\Models\Article;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/** Reportage i sajtens kommuner, nyast först. ?featured=1 lyfter de utvalda överst. */
class ArticleController extends ApiController
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            ...$this->paginationRules(),
            'featured' => ['sometimes', 'boolean'],
        ]);

        $articles = $this->publishedArticles($request)
            ->when($request->boolean('featured'), fn (Builder $query) => $query->orderByDesc('featured'))
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->with(['kommun', 'slugs', 'media'])
            ->paginate($this->perPage($request))
            ->withQueryString();

        return ArticleResource::collection($articles);
    }

    public function show(Request $request, string $site, string $slug): ArticleDetailResource|JsonResponse
    {
        $article = $this->findBySlug(
            $this->publishedArticles($request)->with(['kommun', 'slugs', 'media']),
            $slug,
            $request,
        );

        return $article instanceof JsonResponse ? $article : ArticleDetailResource::make($article);
    }

    /** @return Builder<Article> */
    protected function publishedArticles(Request $request): Builder
    {
        return Article::query()
            ->published()
            ->whereIn('kommun_id', $this->kommunIds($request));
    }
}
