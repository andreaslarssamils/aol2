<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Paginering
    |--------------------------------------------------------------------------
    */

    'pagination' => [
        'per_page' => 20,
        'max_per_page' => 50,
    ],

    /*
    |--------------------------------------------------------------------------
    | Svarscache
    |--------------------------------------------------------------------------
    |
    | Svaren taggas per sajt och svarstyp och töms av ApiCacheObserver när innehåll
    | publiceras, avpubliceras eller ändras. Taggar kräver redis, memcached eller
    | array – med database eller file är svarscachen avstängd. null = standardstore.
    |
    */

    'cache' => [
        'store' => env('API_CACHE_STORE'),
        'ttl' => (int) env('API_CACHE_TTL', 300),
    ],

    /*
    |--------------------------------------------------------------------------
    | Kanoniska frontend-URL:er
    |--------------------------------------------------------------------------
    |
    | Promotions länkar till innehållets kanoniska sida på dess hemsajt: sajtens domän
    | om den har en, annars frontend-URL:en + sajtens slug. Sökvägssegment per typ
    | (morph-alias) – håll dem i takt med Next.js-routerna.
    |
    */

    'frontend' => [
        'url' => env('FRONTEND_URL', 'http://localhost:3000'),
        'paths' => [
            'business_location' => 'foretag',
            'event' => 'evenemang',
            'article' => 'artiklar',
            'page' => '',
        ],
    ],

];
