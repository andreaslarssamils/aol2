<?php

namespace App\Support;

use App\Models\Article;
use App\Models\Business;
use App\Models\BusinessLocation;
use App\Models\Category;
use App\Models\Event;
use App\Models\Kommun;
use App\Models\LunchMenu;
use App\Models\Offer;
use App\Models\OpeningHour;
use App\Models\OpeningHourException;
use App\Models\Page;
use App\Models\Promotion;
use App\Models\Site;
use App\Models\Slug;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Cache;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Svarscache för det publika API:et. Varje svar taggas med sajt och svarstyp. En ändring i
 * innehåll tömmer de svarstyper som visar den modellen – på alla sajter, eftersom en kommun
 * kan visas på flera sajter (site_kommun) och innehåll kan lyftas fram på andra orter (promotions).
 * Kräver en cache-store med taggstöd; annars är cachen avstängd (se config/api.php).
 */
class ApiCache
{
    public const string SITE = 'site';

    public const string HOME = 'home';

    public const string BUSINESSES = 'businesses';

    public const string EVENTS = 'events';

    public const string ARTICLES = 'articles';

    public const string PAGES = 'pages';

    public const string PROMOTIONS = 'promotions';

    /** @var list<string> */
    public const array TYPES = [
        self::SITE, self::HOME, self::BUSINESSES, self::EVENTS,
        self::ARTICLES, self::PAGES, self::PROMOTIONS,
    ];

    /**
     * Vilka svarstyper som visar respektive modell.
     *
     * @var array<class-string<Model>, list<string>>
     */
    protected const array DEPENDENTS = [
        Article::class => [self::ARTICLES, self::HOME, self::PROMOTIONS],
        Event::class => [self::EVENTS, self::HOME, self::PROMOTIONS, self::BUSINESSES],
        Page::class => [self::PAGES],
        Business::class => [self::BUSINESSES, self::HOME, self::PROMOTIONS, self::EVENTS],
        BusinessLocation::class => [self::BUSINESSES, self::HOME, self::PROMOTIONS, self::EVENTS],
        LunchMenu::class => [self::BUSINESSES, self::HOME],
        Offer::class => [self::PROMOTIONS, self::HOME],
        OpeningHour::class => [self::BUSINESSES, self::HOME, self::PROMOTIONS],
        OpeningHourException::class => [self::BUSINESSES, self::HOME, self::PROMOTIONS],
        Promotion::class => [self::PROMOTIONS, self::HOME],
        Category::class => [self::BUSINESSES, self::EVENTS, self::HOME, self::PROMOTIONS],
    ];

    public function store(): Repository
    {
        return Cache::store(config('api.cache.store'));
    }

    /** Taggar kräver redis, memcached eller array – med database eller file är cachen avstängd. */
    public function enabled(): bool
    {
        return $this->store()->supportsTags();
    }

    public function get(Site $site, string $type, string $key): mixed
    {
        return $this->store()->tags($this->tags($site, $type))->get($key);
    }

    public function put(Site $site, string $type, string $key, mixed $value, ?int $ttl = null): void
    {
        $this->store()->tags($this->tags($site, $type))->put($key, $value, $ttl ?? (int) config('api.cache.ttl'));
    }

    /** @return list<string> */
    public function tags(Site $site, string $type): array
    {
        return [$this->siteTag($site), $this->typeTag($type)];
    }

    public function flushTypes(string ...$types): void
    {
        if (! $this->enabled()) {
            return;
        }

        foreach (array_unique($types) as $type) {
            $this->store()->tags([$this->typeTag($type)])->flush();
        }
    }

    public function flushSite(Site $site): void
    {
        if ($this->enabled()) {
            $this->store()->tags([$this->siteTag($site)])->flush();
        }
    }

    public function flushAll(): void
    {
        $this->flushTypes(...self::TYPES);
    }

    /** Tömmer de svar som kan visa modellen. */
    public function flushFor(Model $model): void
    {
        if ($model instanceof Site) {
            $this->flushSite($model);

            return;
        }

        $this->flushTypes(...$this->dependentsOf($model));
    }

    /** @return list<string> */
    protected function dependentsOf(Model $model): array
    {
        $class = match (true) {
            $model instanceof Kommun => null,
            $model instanceof Slug => Relation::getMorphedModel($model->slugable_type) ?? $model->slugable_type,
            $model instanceof Media => Relation::getMorphedModel($model->model_type) ?? $model->model_type,
            default => $model::class,
        };

        if ($class === null) {
            return self::TYPES;
        }

        $types = self::DEPENDENTS[$class] ?? [];

        // Sluggar styr länkarna i promotions oavsett innehållstyp.
        return $model instanceof Slug ? [...$types, self::PROMOTIONS] : $types;
    }

    protected function siteTag(Site $site): string
    {
        return "api:site:{$site->slug}";
    }

    protected function typeTag(string $type): string
    {
        return "api:type:{$type}";
    }
}
