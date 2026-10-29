<?php

namespace App\Providers;

use App\Models\Article;
use App\Models\Business;
use App\Models\BusinessLocation;
use App\Models\Event;
use App\Models\LunchMenu;
use App\Models\Offer;
use App\Models\Page;
use App\Models\User;
use App\Observers\ApiCacheObserver;
use App\Support\HtmlSanitizer;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // En sanerare per request: Symfony-konfigen byggs en gång och sajternas domäner läses en gång.
        $this->app->scoped(HtmlSanitizer::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Korta, stabila typnamn i polymorfa kolumner (revisions, promotions, media, roller).
        Relation::enforceMorphMap([
            'user' => User::class,
            'business' => Business::class,
            'business_location' => BusinessLocation::class,
            'event' => Event::class,
            'article' => Article::class,
            'lunch_menu' => LunchMenu::class,
            'offer' => Offer::class,
            'page' => Page::class,
        ]);

        // Spaties modell kan inte bära #[ObservedBy] – bilder tömmer API-cachen härifrån.
        Media::observe(ApiCacheObserver::class);
    }
}
