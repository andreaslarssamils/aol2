<?php

use App\Http\Controllers\Api\V1\ArticleController;
use App\Http\Controllers\Api\V1\BusinessLocationController;
use App\Http\Controllers\Api\V1\EventController;
use App\Http\Controllers\Api\V1\HomeController;
use App\Http\Controllers\Api\V1\PageController;
use App\Http\Controllers\Api\V1\PromotionController;
use App\Http\Controllers\Api\V1\SiteController;
use App\Http\Middleware\CacheApiResponse;
use App\Http\Middleware\ResolveLocale;
use App\Http\Middleware\ResolveSite;
use App\Support\ApiCache;
use Illuminate\Http\Middleware\SetCacheHeaders;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Publikt läs-API för Next.js-frontenden
|--------------------------------------------------------------------------
|
| Allt ligger under /api/v1/{site} där {site} är sites.slug. Bara publicerat
| innehåll i sajtens kommuner svarar. Svaren cachas per sajt och typ (CacheApiResponse)
| och får ETag så att frontend kan revalidera med If-None-Match.
|
*/

Route::prefix('v1/{site}')
    ->where(['site' => '[a-z0-9-]+'])
    ->name('api.v1.')
    ->middleware([
        ResolveSite::class,
        ResolveLocale::class,
        SetCacheHeaders::using(['etag' => true, 'public' => true, 'max_age' => 0, 'must_revalidate' => true]),
    ])
    ->group(function (): void {
        Route::get('site', SiteController::class)
            ->middleware(CacheApiResponse::for(ApiCache::SITE))
            ->name('site');
        Route::get('home', HomeController::class)
            ->middleware(CacheApiResponse::for(ApiCache::HOME))
            ->name('home');

        // "Öppet just nu" ändras med klockan – kortare livslängd än övriga svar.
        Route::get('businesses', [BusinessLocationController::class, 'index'])
            ->middleware(CacheApiResponse::for(ApiCache::BUSINESSES, 60))
            ->name('businesses.index');
        Route::get('businesses/{slug}', [BusinessLocationController::class, 'show'])
            ->middleware(CacheApiResponse::for(ApiCache::BUSINESSES, 60))
            ->name('businesses.show');

        Route::get('events', [EventController::class, 'index'])
            ->middleware(CacheApiResponse::for(ApiCache::EVENTS))
            ->name('events.index');
        Route::get('events/{slug}', [EventController::class, 'show'])
            ->middleware(CacheApiResponse::for(ApiCache::EVENTS))
            ->name('events.show');

        Route::get('articles', [ArticleController::class, 'index'])
            ->middleware(CacheApiResponse::for(ApiCache::ARTICLES))
            ->name('articles.index');
        Route::get('articles/{slug}', [ArticleController::class, 'show'])
            ->middleware(CacheApiResponse::for(ApiCache::ARTICLES))
            ->name('articles.show');

        Route::get('pages/{slug}', PageController::class)
            ->middleware(CacheApiResponse::for(ApiCache::PAGES))
            ->name('pages.show');

        Route::get('promotions', PromotionController::class)
            ->middleware(CacheApiResponse::for(ApiCache::PROMOTIONS))
            ->name('promotions.index');
    });
