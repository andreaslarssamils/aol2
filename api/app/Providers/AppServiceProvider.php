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
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
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
    }
}
