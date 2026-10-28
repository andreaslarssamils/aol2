<?php

namespace App\Console\Commands;

use App\Enums\BusinessKind;
use App\Enums\CategoryType;
use App\Enums\DiscountType;
use App\Enums\PromotionPlacement;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\HomeController;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Skriver OpenAPI 3.1-specen för det publika läs-API:et. Specen underhålls här för hand –
 * projektet har ingen generator – så uppdatera den när en resurs ändras. Testet i
 * tests/Feature/Console/Commands säkerställer att varje rutt under /api/v1 finns med.
 */
class GenerateOpenApiSpec extends Command
{
    protected $signature = 'api:openapi {--path=public/openapi.json : Målfil, relativt projektroten}';

    protected $description = 'Skriv OpenAPI-specen för det publika läs-API:et';

    public function handle(): int
    {
        $path = base_path($this->option('path'));

        File::ensureDirectoryExists(dirname($path));
        File::put($path, json_encode($this->spec(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n");

        $this->components->info("OpenAPI-spec skriven till {$path}");

        return self::SUCCESS;
    }

    /** @return array<string, mixed> */
    public function spec(): array
    {
        $timezone = config('app.display_timezone');

        return [
            'openapi' => '3.1.0',
            'info' => [
                'title' => 'Allt om – publikt läs-API',
                'version' => '1.0.0',
                'description' => implode("\n\n", [
                    'Läs-API för Next.js-frontenden. Alla anrop ligger under `/api/v1/{site}` där `{site}` är sajtens slug; bara aktiva sajter svarar (annars 404).',
                    'Bara publicerat innehåll i sajtens kommuner returneras – aldrig utkast, arkiverat, raderat, väntande revisioner eller väntande media.',
                    'Språk väljs med `?locale=` eller `Accept-Language` (bara språk i `app.locales`, standard `sv`). Översättningsbara fält returneras som en sträng i valt språk med fallback till svenska. `slug` är alltid den kanoniska för valt språk; detaljresurserna (`businesses/{slug}`, `events/{slug}`, `articles/{slug}`, `pages/{slug}`) ger dessutom `slugs` med kanonisk slug per språk, för hreflang och språkväxling.',
                    '**301 utan Location-header.** Anropas en `{slug}`-resurs med en gammal slug, eller med sluggen för ett annat språk än det valda, svarar API:et `301` med `{"redirect_to": "<kanonisk slug>"}` i body och **ingen** `Location`-header. HTTP-klienter följer därför inte omdirigeringen själva (fetch returnerar 301-svaret som det är). Frontend bygger den kanoniska URL:en av `redirect_to` och svarar besökaren med en egen 301/308. Svaret har ingen ETag och cachas inte.',
                    'Alla lyckade svar har `ETag` och svarar 304 på matchande `If-None-Match`. Svaren cachas per sajt och typ på servern och töms när innehåll publiceras eller avpubliceras.',
                    "Tidpunkter returneras som ISO 8601 med offset i visningstidszonen `{$timezone}`, t.ex. `2026-11-03T19:00:00+01:00` (lagringen är UTC). Öppet just nu, dagens lunch, innevarande vecka och datumparametrarna `from`/`to` tolkas i samma tidszon.",
                ]),
            ],
            'servers' => [
                ['url' => '/api/v1', 'description' => 'Relativt specens host – samma Laravel-app som serverar /openapi.json.'],
            ],
            'tags' => [
                ['name' => 'Sajt'], ['name' => 'Startsida'], ['name' => 'Verksamheter'],
                ['name' => 'Evenemang'], ['name' => 'Reportage'], ['name' => 'Sidor'], ['name' => 'Promotions'], ['name' => 'Kategorier'],
            ],
            'paths' => $this->paths(),
            'components' => [
                'parameters' => $this->parameters(),
                'responses' => $this->responses(),
                'schemas' => $this->schemas(),
            ],
        ];
    }

    /** @return array<string, mixed> */
    protected function paths(): array
    {
        $p = fn (string $name): array => ['$ref' => "#/components/parameters/{$name}"];
        $r = fn (string $name): array => ['$ref' => "#/components/responses/{$name}"];
        $common = ['304' => $r('NotModified'), '404' => $r('NotFound')];

        return [
            '/{site}/site' => ['get' => [
                'tags' => ['Sajt'],
                'summary' => 'Sajten',
                'description' => 'Sajt, kommuner (primärkommunen först), primärkommun och inställningar.',
                'operationId' => 'getSite',
                'parameters' => [$p('site'), $p('locale')],
                'responses' => ['200' => $this->item('Site', 'Sajten')] + $common,
            ]],
            '/{site}/home' => ['get' => [
                'tags' => ['Startsida'],
                'summary' => 'Startsidan i ett anrop',
                'description' => sprintf(
                    'Utvalda reportage (%d), kommande evenemang (%d), aktiva promotions för ytan "startsida", dagens lunchmenyer och väder (null tills SMHI kopplas på).',
                    HomeController::FEATURED_ARTICLES,
                    HomeController::UPCOMING_EVENTS,
                ),
                'operationId' => 'getHome',
                'parameters' => [$p('site'), $p('locale')],
                'responses' => ['200' => $this->item('Home', 'Startsidans innehåll')] + $common,
            ]],
            '/{site}/businesses' => ['get' => [
                'tags' => ['Verksamheter'],
                'summary' => 'Lokaliseringar i katalogen',
                'description' => 'Paginerad lista över publicerade lokaliseringar vars företag också är publicerat, sorterad på visningsnamn (lokalt namn, annars företagets). `open_now` räknas via öppettider och avvikelser för dagen.',
                'operationId' => 'listBusinesses',
                'parameters' => [$p('site'), $p('locale'), $p('page'), $p('per_page'), $p('category'), $p('q')],
                'responses' => ['200' => $this->page('BusinessLocation', 'Lokaliseringar'), '422' => $r('ValidationError')] + $common,
            ]],
            '/{site}/businesses/{slug}' => ['get' => [
                'tags' => ['Verksamheter'],
                'summary' => 'En lokalisering',
                'description' => 'Lokaliseringen med företaget, veckans öppettider inklusive avvikelser, aktuell lunchmeny (innevarande ISO-vecka), bilder och kommande evenemang på platsen. Gammal eller annan-språkig slug ger 301 utan Location-header, se svaret 301.',
                'operationId' => 'getBusiness',
                'parameters' => [$p('site'), $p('slug'), $p('locale')],
                'responses' => ['200' => $this->item('BusinessLocationDetail', 'Lokaliseringen'), '301' => $r('Redirect')] + $common,
            ]],
            '/{site}/events' => ['get' => [
                'tags' => ['Evenemang'],
                'summary' => 'Evenemangskalendern',
                'description' => 'Paginerad lista sorterad på starttid. Innehåller engångsevent och förekomster av återkommande event (`parent_event_id` satt) – inte mallposterna med `recurrence_rule`. Utan `from` visas allt som pågår eller börjar från och med nu.',
                'operationId' => 'listEvents',
                'parameters' => [$p('site'), $p('locale'), $p('page'), $p('per_page'), $p('from'), $p('to'), $p('eventCategory')],
                'responses' => ['200' => $this->page('Event', 'Evenemang'), '422' => $r('ValidationError')] + $common,
            ]],
            '/{site}/events/{slug}' => ['get' => [
                'tags' => ['Evenemang'],
                'summary' => 'Ett evenemang',
                'description' => 'Gammal eller annan-språkig slug ger 301 utan Location-header, se svaret 301.',
                'operationId' => 'getEvent',
                'parameters' => [$p('site'), $p('slug'), $p('locale')],
                'responses' => ['200' => $this->item('EventDetail', 'Evenemanget'), '301' => $r('Redirect')] + $common,
            ]],
            '/{site}/articles' => ['get' => [
                'tags' => ['Reportage'],
                'summary' => 'Reportage',
                'description' => 'Paginerad lista, nyast först. Med `featured=1` sorteras utvalda reportage överst.',
                'operationId' => 'listArticles',
                'parameters' => [$p('site'), $p('locale'), $p('page'), $p('per_page'), $p('featured')],
                'responses' => ['200' => $this->page('Article', 'Reportage'), '422' => $r('ValidationError')] + $common,
            ]],
            '/{site}/articles/{slug}' => ['get' => [
                'tags' => ['Reportage'],
                'summary' => 'Ett reportage',
                'description' => 'Gammal eller annan-språkig slug ger 301 utan Location-header, se svaret 301.',
                'operationId' => 'getArticle',
                'parameters' => [$p('site'), $p('slug'), $p('locale')],
                'responses' => ['200' => $this->item('ArticleDetail', 'Reportaget'), '301' => $r('Redirect')] + $common,
            ]],
            '/{site}/pages/{slug}' => ['get' => [
                'tags' => ['Sidor'],
                'summary' => 'En statisk sida',
                'description' => 'En sida i någon av sajtens kommuner vinner över en nationell sida med samma slug. Gammal eller annan-språkig slug ger 301 utan Location-header, se svaret 301.',
                'operationId' => 'getPage',
                'parameters' => [$p('site'), $p('slug'), $p('locale')],
                'responses' => ['200' => $this->item('Page', 'Sidan'), '301' => $r('Redirect')] + $common,
            ]],
            '/{site}/promotions' => ['get' => [
                'tags' => ['Promotions'],
                'summary' => 'Aktiva placeringar',
                'description' => 'Placeringar med status active vars tidsfönster omfattar nu, riktade mot sajtens kommuner och med publicerat innehåll. Högst prioritet först. `is_paid` betyder att en "Annons"-etikett ska visas; `link.path` är den sajtrelativa sökvägen till innehållets kanoniska sida, utan origin och språkprefix.',
                'operationId' => 'listPromotions',
                'parameters' => [$p('site'), $p('locale'), $p('placement')],
                'responses' => ['200' => $this->collection('Promotion', 'Placeringar'), '422' => $r('ValidationError')] + $common,
            ]],
            '/{site}/categories' => ['get' => [
                'tags' => ['Kategorier'],
                'summary' => 'Kategorier med innehåll',
                'description' => 'Kategorier som har publicerat innehåll på sajten: verksamhetskategorier med minst en lokalisering i katalogen, evenemangskategorier med minst ett kommande publicerat evenemang. Sorterade på typ och sorteringsordning. Utan `type` ges båda typerna.',
                'operationId' => 'listCategories',
                'parameters' => [$p('site'), $p('locale'), $p('type')],
                'responses' => ['200' => $this->collection('Category', 'Kategorier'), '422' => $r('ValidationError')] + $common,
            ]],
        ];
    }

    /** @return array<string, mixed> */
    protected function parameters(): array
    {
        $query = fn (string $name, string $description, array $schema): array => [
            'name' => $name, 'in' => 'query', 'required' => false, 'description' => $description, 'schema' => $schema,
        ];

        return [
            'site' => ['name' => 'site', 'in' => 'path', 'required' => true, 'description' => 'Sajtens slug (sites.slug).', 'schema' => ['type' => 'string', 'pattern' => '^[a-z0-9-]+$']],
            'slug' => ['name' => 'slug', 'in' => 'path', 'required' => true, 'description' => 'Innehållets slug – kanonisk eller gammal. En gammal slug ger 301.', 'schema' => ['type' => 'string']],
            'locale' => $query('locale', 'Innehållsspråk. Vinner över Accept-Language; okända värden faller tillbaka till standardspråket.', ['type' => 'string', 'enum' => array_keys(config('app.locales')), 'default' => array_key_first(config('app.locales'))]),
            'page' => $query('page', 'Sida (1-baserad).', ['type' => 'integer', 'minimum' => 1, 'default' => 1]),
            'per_page' => $query('per_page', 'Rader per sida.', ['type' => 'integer', 'minimum' => 1, 'maximum' => config('api.pagination.max_per_page'), 'default' => config('api.pagination.per_page')]),
            'category' => $query('category', 'Kategorislug (type business).', ['type' => 'string']),
            'eventCategory' => $query('category', 'Kategorislug (type event).', ['type' => 'string']),
            'q' => $query('q', 'Fritextsök på lokaliseringens namn, ort och företagets namn.', ['type' => 'string']),
            'from' => $query('from', 'Visa evenemang som pågår eller börjar från och med denna tidpunkt: ett datum (tolkas i visningstidszonen) eller ISO 8601 med offset. Standard: nu.', ['type' => 'string', 'format' => 'date-time']),
            'to' => $query('to', 'Visa evenemang som börjar senast denna tidpunkt. Ett rent datum gäller hela den dagen i visningstidszonen.', ['type' => 'string', 'format' => 'date-time']),
            'featured' => $query('featured', 'Sortera utvalda reportage först.', ['type' => 'boolean']),
            'placement' => $query('placement', 'Avgränsa till en yta.', ['type' => 'string', 'enum' => array_column(PromotionPlacement::cases(), 'value')]),
            'type' => $query('type', 'Avgränsa till en kategorityp.', ['type' => 'string', 'enum' => array_column(CategoryController::TYPES, 'value')]),
        ];
    }

    /** @return array<string, mixed> */
    protected function responses(): array
    {
        return [
            'NotModified' => ['description' => 'If-None-Match matchade svarets ETag.'],
            'NotFound' => ['description' => 'Sajten är inte aktiv, eller innehållet finns inte, är opublicerat eller hör till en annan kommun.', 'content' => ['application/json' => ['schema' => $this->ref('Error')]]],
            'Redirect' => [
                'description' => 'Sluggen är inte den kanoniska för valt språk (gammal slug eller annat språks slug). Svaret är 301 UTAN Location-header, så klienten följer inte omdirigeringen själv: bygg den kanoniska URL:en av `redirect_to` (bara sluggen, inte en URL) och omdirigera besökaren själv. Ingen ETag, cachas inte.',
                'x-redirect-without-location' => true,
                'content' => ['application/json' => ['schema' => $this->ref('Redirect')]],
            ],
            'ValidationError' => ['description' => 'Ogiltiga frågeparametrar.', 'content' => ['application/json' => ['schema' => $this->ref('ValidationError')]]],
        ];
    }

    /** @return array<string, mixed> */
    protected function schemas(): array
    {
        $string = ['type' => 'string'];
        $nullableString = ['type' => ['string', 'null']];
        $dateTime = ['type' => ['string', 'null'], 'format' => 'date-time', 'example' => '2026-11-03T21:00:00+01:00'];
        $coordinate = ['type' => ['number', 'null']];
        $id = ['type' => 'integer'];
        $list = fn (string $schema): array => ['type' => 'array', 'items' => $this->ref($schema)];
        $object = fn (array $properties, array $required = []): array => array_filter([
            'type' => 'object',
            'properties' => $properties,
            'required' => $required ?: array_keys($properties),
        ]);

        $locationLink = ['type' => ['object', 'null'], 'description' => 'Platsen i katalogen, om dess företag är publicerat.', 'properties' => ['slug' => $nullableString, 'namn' => $nullableString]];
        $slugs = ['type' => 'object', 'description' => 'Kanonisk slug per språk – bara språk som har en. För hreflang och språkväxling.', 'additionalProperties' => $string, 'example' => ['sv' => 'stadshotellet', 'en' => 'town-hotel']];

        return [
            'Error' => $object(['message' => $string]),
            'ValidationError' => $object(['message' => $string, 'errors' => ['type' => 'object', 'additionalProperties' => ['type' => 'array', 'items' => $string]]]),
            'Redirect' => $object(['redirect_to' => ['type' => 'string', 'description' => 'Kanonisk slug för valt språk – bara sluggen, inte en URL. Svaret saknar Location-header.', 'example' => 'pubquiz-tisdagar']]),
            'PaginationLinks' => $object(['first' => $nullableString, 'last' => $nullableString, 'prev' => $nullableString, 'next' => $nullableString]),
            'PaginationMeta' => $object([
                'current_page' => $id, 'from' => ['type' => ['integer', 'null']], 'last_page' => $id,
                'links' => ['type' => 'array', 'items' => ['type' => 'object']], 'path' => $string,
                'per_page' => $id, 'to' => ['type' => ['integer', 'null']], 'total' => $id,
            ]),
            'Kommun' => $object(['id' => $id, 'namn' => $string, 'slug' => $string, 'kod' => ['type' => 'string', 'description' => 'SCB-kod, t.ex. "2085".'], 'lat' => $coordinate, 'lng' => $coordinate]),
            'Site' => $object([
                'slug' => $string, 'namn' => $string, 'domain' => $nullableString,
                'primary_kommun' => ['oneOf' => [$this->ref('Kommun'), ['type' => 'null']]],
                'kommuner' => ['type' => 'array', 'description' => 'Primärkommunen först.', 'items' => $this->ref('Kommun')],
                'settings' => ['type' => 'object', 'additionalProperties' => true],
            ]),
            'Category' => $object(['slug' => $string, 'namn' => $string, 'type' => ['type' => 'string', 'enum' => array_column(CategoryType::cases(), 'value')], 'icon' => $nullableString]),
            'Media' => $object([
                'url' => ['type' => 'string', 'format' => 'uri'], 'alt' => $nullableString,
                'width' => ['type' => ['integer', 'null']], 'height' => ['type' => ['integer', 'null']], 'mime_type' => $nullableString,
            ]),
            'BusinessSummary' => $object(['id' => $id, 'namn' => $string, 'kind' => ['type' => 'string', 'enum' => array_column(BusinessKind::cases(), 'value')]]),
            'Business' => $object([
                'id' => $id, 'namn' => $string, 'kind' => ['type' => 'string', 'enum' => array_column(BusinessKind::cases(), 'value')],
                'beskrivning' => $nullableString, 'epost' => $nullableString, 'webbplats' => $nullableString, 'categories' => $list('Category'),
            ]),
            'BusinessLocation' => $object([
                'id' => $id, 'slug' => $nullableString, 'namn' => ['type' => 'string', 'description' => 'Lokalt namn, annars företagets.'],
                'business' => $this->ref('BusinessSummary'), 'categories' => $list('Category'),
                'adress' => $nullableString, 'postnummer' => $nullableString, 'ort' => $nullableString, 'telefon' => $nullableString,
                'lat' => $coordinate, 'lng' => $coordinate, 'is_primary' => ['type' => 'boolean'], 'kommun' => $this->ref('Kommun'),
                'open_now' => ['type' => 'boolean', 'description' => 'Öppet just nu enligt öppettider och avvikelser, i visningstidszonen.'],
                'images' => $list('Media'),
            ]),
            'OpeningInterval' => $object(['opens' => ['type' => 'string', 'example' => '09:00'], 'closes' => ['type' => 'string', 'example' => '17:00']]),
            'OpeningDay' => $object([
                'date' => ['type' => 'string', 'format' => 'date'], 'weekday' => ['type' => 'integer', 'minimum' => 0, 'maximum' => 6, 'description' => '0 = måndag ... 6 = söndag'],
                'closed' => ['type' => 'boolean'], 'is_exception' => ['type' => 'boolean', 'description' => 'Dagen styrs av en avvikelse (röd dag, semester).'],
                'note' => $nullableString, 'intervals' => $list('OpeningInterval'),
            ]),
            'LunchMenu' => $object([
                'iso_year' => $id, 'week' => $id,
                'menu' => ['type' => 'object', 'description' => '{"mon": [...], ..., "sun": [...]}', 'additionalProperties' => ['type' => 'array', 'items' => $string]],
                'today' => ['type' => 'array', 'items' => $string, 'description' => 'Dagens rätter, i visningstidszonen.'],
                'location' => ['type' => 'object', 'properties' => ['slug' => $nullableString, 'namn' => $nullableString]],
            ], ['iso_year', 'week', 'menu', 'today']),
            'BusinessLocationDetail' => ['allOf' => [
                $this->ref('BusinessLocation'),
                $object([
                    'slugs' => $slugs,
                    'business' => $this->ref('Business'),
                    'opening_hours' => ['type' => 'array', 'description' => 'Måndag till söndag innevarande vecka.', 'items' => $this->ref('OpeningDay'), 'minItems' => 7, 'maxItems' => 7],
                    'lunch_menu' => ['oneOf' => [$this->ref('LunchMenu'), ['type' => 'null']]],
                    'upcoming_events' => $list('Event'),
                ]),
            ]],
            'Event' => $object([
                'id' => $id, 'slug' => $nullableString, 'namn' => $string, 'beskrivning' => $nullableString, 'all_day' => ['type' => 'boolean'],
                'starts_at' => ['type' => 'string', 'format' => 'date-time', 'example' => '2026-11-03T19:00:00+01:00'], 'ends_at' => $dateTime,
                'venue_name' => $nullableString, 'adress' => $nullableString, 'lat' => $coordinate, 'lng' => $coordinate,
                'pris' => $nullableString, 'ticket_url' => $nullableString, 'published_at' => $dateTime,
                'kommun' => $this->ref('Kommun'), 'categories' => $list('Category'), 'location' => $locationLink, 'images' => $list('Media'),
            ]),
            'Article' => $object([
                'id' => $id, 'slug' => $nullableString, 'titel' => $nullableString, 'ingress' => $nullableString, 'author_name' => $nullableString,
                'featured' => ['type' => 'boolean'], 'published_at' => $dateTime, 'kommun' => $this->ref('Kommun'), 'images' => $list('Media'),
            ]),
            'EventDetail' => ['allOf' => [$this->ref('Event'), $object(['slugs' => $slugs])]],
            'ArticleDetail' => ['allOf' => [$this->ref('Article'), $object(['slugs' => $slugs, 'body' => ['type' => ['string', 'null'], 'description' => 'HTML.']])]],
            'Page' => $object([
                'id' => $id, 'slug' => $nullableString, 'slugs' => $slugs, 'titel' => $nullableString, 'body' => ['type' => ['string', 'null'], 'description' => 'HTML.'],
                'published_at' => $dateTime, 'kommun' => ['oneOf' => [$this->ref('Kommun'), ['type' => 'null']], 'description' => 'null = nationell sida.'],
                'images' => $list('Media'),
            ]),
            'Offer' => $object([
                'id' => $id, 'titel' => $nullableString, 'beskrivning' => $nullableString,
                'discount_type' => ['type' => ['string', 'null'], 'enum' => [...array_column(DiscountType::cases(), 'value'), null]],
                'discount_value' => ['type' => ['number', 'null']], 'code' => $nullableString, 'starts_at' => $dateTime, 'ends_at' => $dateTime,
                'business' => $this->ref('Business'),
            ]),
            'Link' => ['type' => ['object', 'null'], 'description' => 'Sajtrelativ länk till innehållets kanoniska sida, utan origin och utan språkprefix – frontend lägger på båda. null när innehållet saknar egen sida.', 'properties' => [
                'type' => ['type' => 'string', 'enum' => ['business_location', 'event', 'article', 'page']],
                'slug' => $nullableString, 'locale' => $string, 'kommun' => $nullableString,
                'site' => ['type' => ['string', 'null'], 'description' => 'Innehållets hemsajt: aktiv sajt vars primärkommun är innehållets kommun, annars första aktiva sajt som visar kommunen.'],
                'path' => ['type' => ['string', 'null'], 'description' => 'Sökvägssegment per typ plus kanonisk slug för valt språk.', 'example' => '/evenemang/nyhammarsfesten'],
            ]],
            'Promotion' => $object([
                'id' => $id, 'placement' => ['type' => 'string', 'enum' => array_column(PromotionPlacement::cases(), 'value')], 'priority' => $id,
                'is_paid' => ['type' => 'boolean', 'description' => 'true → visa "Annons"-etikett.'],
                'starts_at' => ['type' => 'string', 'format' => 'date-time', 'example' => '2026-11-03T19:00:00+01:00'], 'ends_at' => $dateTime,
                'type' => ['type' => 'string', 'enum' => ['business_location', 'event', 'article', 'offer']],
                'content' => ['oneOf' => [$this->ref('Event'), $this->ref('Article'), $this->ref('BusinessLocation'), $this->ref('Offer')]],
                'link' => $this->ref('Link'),
            ]),
            'Home' => $object([
                'featured_articles' => $list('Article'), 'upcoming_events' => $list('Event'), 'promotions' => $list('Promotion'),
                'lunch_menus' => ['type' => 'array', 'description' => 'Restauranger med lunch idag, sorterade på namn.', 'items' => $this->ref('LunchMenu')],
                'weather' => ['type' => 'null', 'description' => 'Placeholder tills SMHI kopplas på.'],
            ]),
        ];
    }

    /** @return array<string, mixed> */
    protected function ref(string $schema): array
    {
        return ['$ref' => "#/components/schemas/{$schema}"];
    }

    /** @return array<string, mixed> */
    protected function item(string $schema, string $description): array
    {
        return ['description' => $description, 'content' => ['application/json' => ['schema' => [
            'type' => 'object', 'required' => ['data'], 'properties' => ['data' => $this->ref($schema)],
        ]]]];
    }

    /** @return array<string, mixed> */
    protected function collection(string $schema, string $description): array
    {
        return ['description' => $description, 'content' => ['application/json' => ['schema' => [
            'type' => 'object', 'required' => ['data'], 'properties' => ['data' => ['type' => 'array', 'items' => $this->ref($schema)]],
        ]]]];
    }

    /** @return array<string, mixed> */
    protected function page(string $schema, string $description): array
    {
        return ['description' => $description, 'content' => ['application/json' => ['schema' => [
            'type' => 'object', 'required' => ['data', 'links', 'meta'], 'properties' => [
                'data' => ['type' => 'array', 'items' => $this->ref($schema)],
                'links' => $this->ref('PaginationLinks'),
                'meta' => $this->ref('PaginationMeta'),
            ],
        ]]]];
    }
}
