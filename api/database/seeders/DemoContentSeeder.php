<?php

namespace Database\Seeders;

use App\Enums\BusinessKind;
use App\Enums\CategoryType;
use App\Enums\ContentStatus;
use App\Enums\DiscountType;
use App\Enums\ManagedBy;
use App\Enums\PromotionPlacement;
use App\Enums\PromotionStatus;
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
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Spatie\MediaLibrary\HasMedia;

/**
 * Demoinnehåll för Allt om Ludvika – det Next.js-frontendens röktester (web/e2e) kör mot.
 *
 * Körs separat: php artisan db:seed --class=DemoContentSeeder (efter DatabaseSeeder, som ger
 * kommuner, kategorier och sajten). Idempotent: innehållet identifieras på namn/slug och
 * uppdateras, så relativa datum (kommande evenemang, veckans lunch) flyttas fram vid varje körning.
 * Tidpunkter anges i visningstidszonen och lagras i UTC.
 */
class DemoContentSeeder extends Seeder
{
    protected Kommun $ludvika;

    protected User $editor;

    protected CarbonImmutable $now;

    public function run(): void
    {
        $this->ludvika = Kommun::where('kod', '2085')->firstOrFail();
        $this->editor = User::firstOrCreate(
            ['email' => 'test@example.com'],
            ['name' => 'Test User', 'password' => 'password'],
        );
        $this->now = now(config('app.display_timezone'))->toImmutable();

        $stadshotellet = $this->seedStadshotellet();
        $lyktan = $this->seedCafeLyktan();
        $bokhandeln = $this->seedBokhandeln();
        $this->seedCykelSport();
        $hembygd = $this->seedHembygdsforening();
        $this->seedSalongSaxen();
        $this->seedHotellVasman();
        $this->seedByggservice();

        $julmarknad = $this->seedEvents($stadshotellet, $bokhandeln, $hembygd);
        $vandringar = $this->seedArticles();
        $this->seedPages();
        $this->seedPromotions($julmarknad, $bokhandeln, $vandringar, $lyktan);
    }

    protected function seedStadshotellet(): BusinessLocation
    {
        $business = $this->business('Stadshotellet', ['restaurang-cafe', 'boende'], [
            'beskrivning' => [
                'sv' => '<p>Klassisk stadskrog mitt i Ludvika med husmanskost till lunch, à la carte på kvällen och pubquiz varje tisdag.</p><p>Vi har 24 hotellrum och festvåning för upp till 120 gäster.</p>',
                'en' => '<p>Classic town-centre restaurant in Ludvika with Swedish home cooking at lunch, à la carte in the evening and a pub quiz every Tuesday.</p><p>24 hotel rooms and a banquet hall for up to 120 guests.</p>',
            ],
            'epost' => 'info@stadshotellet-ludvika.se',
            'webbplats' => 'https://stadshotellet-ludvika.se',
        ]);

        $location = $this->location($business, [
            'telefon' => '0240-123 45',
            'adress' => 'Storgatan 12',
            'postnummer' => '771 30',
            'ort' => 'Ludvika',
            'lat' => 60.1496,
            'lng' => 15.1875,
        ]);

        // Gammal slug behålls som icke-kanonisk så att frontend får en 301 att hantera.
        $location->setSlug('sv', 'stadshotellet-ludvika');
        $location->setSlug('sv', 'stadshotellet');
        $location->setSlug('en', 'town-hotel');

        $this->openingHours($location, [
            0 => [['11:00', '22:00']],
            1 => [['11:00', '22:00']],
            2 => [['11:00', '22:00']],
            3 => [['11:00', '22:00']],
            4 => [['11:00', '00:00']],
            5 => [['12:00', '23:00']],
            6 => [],
        ]);

        // En avvikelse som alltid faller inom innevarande vecka: i morgon om veckan räcker, annars i dag.
        $exception = $this->now->dayOfWeekIso < 7 ? $this->now->addDay() : $this->now;
        $this->exceptions($location, [
            [$exception->toDateString(), null, null, 'Stängt för privat tillställning'],
        ]);

        $this->lunchMenu($location, [
            'mon' => ['Pannbiff med löksås och potatispuré', 'Vegetarisk lasagne'],
            'tue' => ['Stekt strömming med potatismos', 'Kikärtscurry'],
            'wed' => ['Köttbullar med gräddsås och lingon', 'Fisksoppa med aioli'],
            'thu' => ['Ärtsoppa och pannkakor', 'Kycklinggryta med ris'],
            'fri' => ['Ugnsbakad lax med dillsås', 'Halloumiburgare'],
            'sat' => ['Helgbrunch 11–14'],
            'sun' => ['Söndagsstek med tillbehör'],
        ]);

        $this->image($business, 'stadshotellet.jpg', 'Stadshotellet', [120, 70, 60], 'Stadshotellets fasad mot Storgatan');

        return $location;
    }

    protected function seedCafeLyktan(): BusinessLocation
    {
        $business = $this->business('Café Lyktan', ['restaurang-cafe'], [
            'beskrivning' => [
                'sv' => '<p>Hembakat, egenrostat kaffe och en enkel lunch med soppa, sallad och dagens macka.</p>',
                'en' => '<p>Home baking, house-roasted coffee and a simple lunch of soup, salad and the sandwich of the day.</p>',
            ],
            'epost' => 'hej@cafelyktan.se',
            'webbplats' => 'https://cafelyktan.se',
        ]);

        $location = $this->location($business, [
            'telefon' => '0240-234 56',
            'adress' => 'Fredsgatan 3',
            'postnummer' => '771 31',
            'ort' => 'Ludvika',
            'lat' => 60.1483,
            'lng' => 15.1902,
        ]);
        $location->setSlug('sv', 'cafe-lyktan');
        $location->setSlug('en', 'cafe-lyktan');

        $this->openingHours($location, [
            0 => [['07:30', '17:00']],
            1 => [['07:30', '17:00']],
            2 => [['07:30', '17:00']],
            3 => [['07:30', '17:00']],
            4 => [['07:30', '17:00']],
            5 => [['09:00', '15:00']],
            6 => [],
        ]);

        $this->lunchMenu($location, [
            'mon' => ['Tomatsoppa med bröd', 'Caesarsallad'],
            'tue' => ['Morotssoppa med ingefära', 'Räkmacka'],
            'wed' => ['Linssoppa', 'Kycklingsallad'],
            'thu' => ['Potatis- och purjolökssoppa', 'Dagens macka'],
            'fri' => ['Fisksoppa', 'Grekisk sallad'],
        ]);

        $this->image($business, 'cafe-lyktan.jpg', 'Cafe Lyktan', [60, 110, 100], 'Fikabordet på Café Lyktan');

        return $location;
    }

    protected function seedBokhandeln(): BusinessLocation
    {
        $business = $this->business('Bokhandeln i Ludvika', ['butik'], [
            'beskrivning' => [
                'sv' => '<p>Oberoende bokhandel sedan 1952. Skönlitteratur, barnböcker, lokalhistoria och författarkvällar.</p>',
                'en' => '<p>Independent bookshop since 1952. Fiction, children\'s books, local history and author evenings.</p>',
            ],
            'epost' => 'info@bokhandelniludvika.se',
            'webbplats' => 'https://bokhandelniludvika.se',
        ]);

        $location = $this->location($business, [
            'telefon' => '0240-345 67',
            'adress' => 'Engelbrektsgatan 8',
            'postnummer' => '771 30',
            'ort' => 'Ludvika',
            'lat' => 60.1501,
            'lng' => 15.1860,
        ]);
        $location->setSlug('sv', 'bokhandeln-i-ludvika');
        $location->setSlug('en', 'ludvika-bookshop');

        $this->openingHours($location, [
            0 => [['10:00', '18:00']],
            1 => [['10:00', '18:00']],
            2 => [['10:00', '18:00']],
            3 => [['10:00', '18:00']],
            4 => [['10:00', '18:00']],
            5 => [['10:00', '14:00']],
            6 => [],
        ]);

        return $location;
    }

    protected function seedCykelSport(): BusinessLocation
    {
        $business = $this->business('Ludvika Cykel & Sport', ['butik', 'fritid-upplevelser'], [
            'beskrivning' => [
                'sv' => '<p>Cyklar, skidor och service. Vi hyr också ut cyklar och kajaker för turer runt Väsman.</p>',
                'en' => '<p>Bikes, skis and workshop. We also rent bikes and kayaks for trips around lake Väsman.</p>',
            ],
            'epost' => 'butik@ludvikacykel.se',
            'webbplats' => 'https://ludvikacykel.se',
        ]);

        $location = $this->location($business, [
            'telefon' => '0240-456 78',
            'adress' => 'Vasagatan 21',
            'postnummer' => '771 32',
            'ort' => 'Ludvika',
            'lat' => 60.1470,
            'lng' => 15.1950,
        ]);
        $location->setSlug('sv', 'ludvika-cykel-sport');

        $this->openingHours($location, [
            0 => [['10:00', '18:00']],
            1 => [['10:00', '18:00']],
            2 => [['10:00', '18:00']],
            3 => [['10:00', '18:00']],
            4 => [['10:00', '18:00']],
            5 => [['10:00', '15:00']],
            6 => [],
        ]);

        return $location;
    }

    protected function seedHembygdsforening(): BusinessLocation
    {
        $business = $this->business('Grängesbergs Hembygdsförening', ['forening'], [
            'kind' => BusinessKind::Association,
            'beskrivning' => [
                'sv' => '<p>Vi vårdar gruvsamhällets historia med hembygdsgård, arkiv och loppis varje månad.</p>',
                'en' => '<p>We preserve the history of the mining town with a heritage farm, archive and a monthly flea market.</p>',
            ],
            'epost' => 'kontakt@grangesbergshembygd.se',
            'webbplats' => null,
        ]);

        $location = $this->location($business, [
            'telefon' => '0240-567 89',
            'adress' => 'Hembygdsvägen 1',
            'postnummer' => '772 40',
            'ort' => 'Grängesberg',
            'lat' => 60.0744,
            'lng' => 15.0044,
        ]);
        $location->setSlug('sv', 'grangesbergs-hembygdsforening');

        return $location;
    }

    protected function seedSalongSaxen(): BusinessLocation
    {
        $business = $this->business('Salong Saxen', ['halsa-skonhet'], [
            'beskrivning' => [
                'sv' => '<p>Frisör för hela familjen. Boka tid online eller slink in.</p>',
                'en' => '<p>Hairdresser for the whole family. Book online or walk in.</p>',
            ],
            'epost' => 'boka@salongsaxen.se',
            'webbplats' => 'https://salongsaxen.se',
        ]);

        $location = $this->location($business, [
            'telefon' => '0240-678 90',
            'adress' => 'Kyrkogatan 5',
            'postnummer' => '771 30',
            'ort' => 'Ludvika',
            'lat' => 60.1490,
            'lng' => 15.1840,
        ]);
        $location->setSlug('sv', 'salong-saxen');

        $this->openingHours($location, [
            0 => [['09:00', '17:00']],
            1 => [['09:00', '17:00']],
            2 => [['09:00', '19:00']],
            3 => [['09:00', '17:00']],
            4 => [['09:00', '15:00']],
            5 => [],
            6 => [],
        ]);

        return $location;
    }

    protected function seedHotellVasman(): BusinessLocation
    {
        $business = $this->business('Hotell Väsman', ['boende'], [
            'beskrivning' => [
                'sv' => '<p>Hotell vid sjön Väsman med 40 rum, bastu och brygga. Frukost ingår.</p>',
                'en' => '<p>Lakeside hotel by Väsman with 40 rooms, sauna and jetty. Breakfast included.</p>',
            ],
            'epost' => 'bokning@hotellvasman.se',
            'webbplats' => 'https://hotellvasman.se',
        ]);

        $location = $this->location($business, [
            'telefon' => '0240-789 01',
            'adress' => 'Strandvägen 40',
            'postnummer' => '771 40',
            'ort' => 'Ludvika',
            'lat' => 60.1380,
            'lng' => 15.1700,
        ]);
        $location->setSlug('sv', 'hotell-vasman');

        $this->openingHours($location, array_fill(0, 7, [['07:00', '22:00']]));

        return $location;
    }

    protected function seedByggservice(): BusinessLocation
    {
        $business = $this->business('Byggservice Dalarna', ['hantverk-service'], [
            'beskrivning' => [
                'sv' => '<p>Snickeri, badrumsrenovering och fönsterbyten i hela Ludvika kommun.</p>',
                'en' => '<p>Carpentry, bathroom renovations and window replacement throughout Ludvika municipality.</p>',
            ],
            'epost' => 'offert@byggservicedalarna.se',
            'webbplats' => 'https://byggservicedalarna.se',
        ]);

        $location = $this->location($business, [
            'telefon' => '0240-890 12',
            'adress' => 'Industrivägen 14',
            'postnummer' => '771 42',
            'ort' => 'Ludvika',
            'lat' => 60.1550,
            'lng' => 15.2100,
        ]);
        $location->setSlug('sv', 'byggservice-dalarna');

        $this->openingHours($location, [
            0 => [['07:00', '16:00']],
            1 => [['07:00', '16:00']],
            2 => [['07:00', '16:00']],
            3 => [['07:00', '16:00']],
            4 => [['07:00', '14:00']],
            5 => [],
            6 => [],
        ]);

        return $location;
    }

    /** @return Event Julmarknaden – den lyfts fram som betald promotion på startsidan. */
    protected function seedEvents(BusinessLocation $stadshotellet, BusinessLocation $bokhandeln, BusinessLocation $hembygd): Event
    {
        $tuesday = $this->now->next(CarbonImmutable::TUESDAY);

        $this->event('Pubquiz på Stadshotellet', $tuesday->setTime(19, 0), $tuesday->setTime(21, 0), ['kultur'], [
            'business_location_id' => $stadshotellet->id,
            'beskrivning' => [
                'sv' => '<p>Veckans quiz i baren. Lag om max fem personer, anmälan på plats från 18.30.</p>',
                'en' => '<p>The weekly quiz in the bar. Teams of up to five, sign up at the venue from 18:30.</p>',
            ],
            'pris' => 'Fri entré',
        ], enSlug: 'pub-quiz-at-the-town-hotel');

        $this->event('Författarkväll på Bokhandeln', $this->now->addDays(3)->setTime(18, 0), $this->now->addDays(3)->setTime(19, 30), ['kultur'], [
            'business_location_id' => $bokhandeln->id,
            'beskrivning' => [
                'sv' => '<p>Samtal om den nya deckaren som utspelar sig i Grängesbergs gruvor. Boksignering efteråt.</p>',
                'en' => '<p>A conversation about the new crime novel set in the Grängesberg mines. Book signing afterwards.</p>',
            ],
            'pris' => '50 kr inkl. fika',
        ]);

        $this->event('Barnteater: Pettson och Findus', $this->now->addDays(5)->setTime(14, 0), $this->now->addDays(5)->setTime(15, 0), ['barn-familj', 'kultur'], [
            'venue_name' => 'Folkets hus',
            'adress' => 'Carlavägen 24, Ludvika',
            'beskrivning' => [
                'sv' => '<p>Dockteater för barn från 3 år. Cirka 45 minuter.</p>',
                'en' => '<p>Puppet theatre for children aged 3 and up. About 45 minutes.</p>',
            ],
            'pris' => '80 kr',
            'ticket_url' => 'https://example.com/biljetter/pettson',
        ]);

        $this->event('Loppis i Grängesberg', $this->now->addDays(7)->setTime(10, 0), $this->now->addDays(7)->setTime(14, 0), ['marknad-mat'], [
            'business_location_id' => $hembygd->id,
            'beskrivning' => [
                'sv' => '<p>Månadens loppis på hembygdsgården. Kaffeservering i Lillstugan.</p>',
                'en' => '<p>The monthly flea market at the heritage farm. Coffee served in the cottage.</p>',
            ],
            'pris' => 'Fri entré',
        ]);

        $this->event('Ludvika Open – innebandy', $this->now->addDays(10)->setTime(9, 0), $this->now->addDays(10)->setTime(17, 0), ['sport-motion'], [
            'venue_name' => 'Sporthallen',
            'adress' => 'Hälsingegatan 2, Ludvika',
            'beskrivning' => [
                'sv' => '<p>Ungdomsturnering i innebandy, 24 lag från hela Dalarna.</p>',
                'en' => '<p>Youth floorball tournament with 24 teams from all over Dalarna.</p>',
            ],
            'pris' => 'Fri entré',
        ]);

        $this->event('Höstkonsert med Ludvika Musikkår', $this->now->addDays(14)->setTime(18, 0), $this->now->addDays(14)->setTime(20, 0), ['musik'], [
            'venue_name' => 'Ludvika kyrka',
            'adress' => 'Kyrkogatan 1, Ludvika',
            'beskrivning' => [
                'sv' => '<p>Blåsmusik från filmduken och svenska klassiker.</p>',
                'en' => '<p>Brass band music from the silver screen and Swedish classics.</p>',
            ],
            'pris' => '150 kr',
            'ticket_url' => 'https://example.com/biljetter/hostkonsert',
        ]);

        $julmarknad = $this->event('Julmarknad på torget', $this->now->addDays(21)->startOfDay(), $this->now->addDays(21)->endOfDay(), ['marknad-mat', 'barn-familj'], [
            'all_day' => true,
            'venue_name' => 'Ludvika torg',
            'adress' => 'Storgatan, Ludvika',
            'beskrivning' => [
                'sv' => '<p>Knallar, glögg, tomte och ponnyridning. Marknaden pågår hela dagen.</p>',
                'en' => '<p>Stalls, mulled wine, Santa and pony rides. The market runs all day.</p>',
            ],
            'pris' => 'Fri entré',
        ], enSlug: 'christmas-market-on-the-square');

        $this->event('Ljusfest vid Väsman', $this->now->addDays(28)->setTime(17, 0), $this->now->addDays(28)->setTime(20, 0), ['kultur', 'barn-familj'], [
            'venue_name' => 'Väsmanstrand',
            'adress' => 'Strandvägen, Ludvika',
            'beskrivning' => [
                'sv' => '<p>Lyktor, eldshow och varm choklad nere vid vattnet.</p>',
                'en' => '<p>Lanterns, a fire show and hot chocolate down by the water.</p>',
            ],
            'pris' => 'Fri entré',
        ]);

        // Syns inte i API:et: passerat respektive utkast.
        $this->event('Skördemarknad', $this->now->subDays(7)->setTime(10, 0), $this->now->subDays(7)->setTime(15, 0), ['marknad-mat'], [
            'venue_name' => 'Ludvika torg',
        ]);
        $this->event('Hemligt releaseparty', $this->now->addDays(9)->setTime(20, 0), $this->now->addDays(9)->setTime(23, 0), ['musik'], [
            'venue_name' => 'Okänd lokal',
            'status' => ContentStatus::Draft,
            'published_at' => null,
        ]);

        return $julmarknad;
    }

    /** @return Article "Fem vandringar runt Väsman" – lyfts fram som obetald promotion. */
    protected function seedArticles(): Article
    {
        $this->article('Nya ägare till Stadshotellet satsar på lunchen', 'nya-agare-till-stadshotellet', [
            'en_titel' => 'New owners of the Town Hotel bet on lunch',
            'en_slug' => 'new-owners-of-the-town-hotel',
            'ingress' => [
                'sv' => 'Efter tjugo år byter Stadshotellet ägare. Först på listan: en lunch som får folk att gå från jobbet.',
                'en' => 'After twenty years the Town Hotel changes hands. First on the list: a lunch worth leaving the office for.',
            ],
            'body' => [
                'sv' => '<p>Syskonen Anna och Johan Berg tog över Stadshotellet i oktober. De har båda jobbat i Stockholmskrogar men ville hem till Ludvika.</p><h2>Husmanskost med lokala råvaror</h2><p>Lunchmenyn byggs kring kött från Grängesberg och fisk från Väsman. Priset ska hållas under 130 kronor.</p><p>Pubquizen på tisdagar blir kvar, lovar Johan.</p>',
                'en' => '<p>Siblings Anna and Johan Berg took over the Town Hotel in October. Both have worked in Stockholm restaurants but wanted to come home to Ludvika.</p><h2>Home cooking with local produce</h2><p>The lunch menu is built around meat from Grängesberg and fish from lake Väsman, with prices kept under 130 kronor.</p><p>The Tuesday pub quiz stays, Johan promises.</p>',
            ],
            'author_name' => 'Maria Lindqvist',
            'featured' => true,
            'published_at' => $this->now->subDay(),
            'image' => ['stadshotellet-lunch.jpg', 'Lunch pa Stadshotellet', [150, 90, 50], 'Dagens lunch serveras på Stadshotellet'],
        ]);

        $this->article('Så blir julmarknaden på torget i år', 'sa-blir-julmarknaden-pa-torget', [
            'ingress' => [
                'sv' => 'Fler knallar, längre öppettider och en isbana för barnen. Vi har hela programmet.',
                'en' => 'More stalls, longer hours and an ice rink for the kids. We have the full programme.',
            ],
            'body' => [
                'sv' => '<p>Julmarknaden på Ludvika torg växer. I år deltar över sextio knallar och marknaden pågår hela dagen.</p><p>Nytt är isbanan på torgets norra del, öppen för alla med egna skridskor.</p>',
                'en' => '<p>The Christmas market on Ludvika square is growing. This year more than sixty stalls take part and the market runs all day.</p><p>New this year is the ice rink on the northern part of the square, open to anyone with their own skates.</p>',
            ],
            'author_name' => 'Erik Sandström',
            'featured' => true,
            'published_at' => $this->now->subDays(3),
        ]);

        $this->article('Ludvika Cykel & Sport firar 30 år', 'ludvika-cykel-sport-firar-30-ar', [
            'ingress' => [
                'sv' => 'Från två anställda till tio. Butiken på Vasagatan firar med öppet hus och servicekampanj.',
                'en' => 'From two employees to ten. The shop on Vasagatan celebrates with an open house and a service campaign.',
            ],
            'body' => [
                'sv' => '<p>När Lars Nyström öppnade butiken 1996 sålde han mest barncyklar. I dag är uthyrningen av kajaker sommarens största affär.</p>',
                'en' => '<p>When Lars Nyström opened the shop in 1996 he mostly sold children\'s bikes. Today kayak rental is the biggest summer business.</p>',
            ],
            'author_name' => 'Maria Lindqvist',
            'featured' => true,
            'published_at' => $this->now->subDays(5),
        ]);

        $this->article('Hembygdsföreningen öppnar arkivet för allmänheten', 'hembygdsforeningen-oppnar-arkivet', [
            'ingress' => [
                'sv' => 'Tusentals fotografier från gruvepoken blir sökbara. Första visningen hålls i samband med loppisen.',
                'en' => 'Thousands of photographs from the mining era become searchable. The first viewing is held during the flea market.',
            ],
            'body' => [
                'sv' => '<p>Arkivet har digitaliserats under två år med hjälp av frivilliga.</p>',
                'en' => '<p>The archive has been digitised over two years with the help of volunteers.</p>',
            ],
            'author_name' => 'Erik Sandström',
            'featured' => false,
            'published_at' => $this->now->subDays(8),
        ]);

        return $this->article('Fem vandringar runt Väsman', 'fem-vandringar-runt-vasman', [
            'en_titel' => 'Five hikes around lake Väsman',
            'en_slug' => 'five-hikes-around-lake-vasman',
            'ingress' => [
                'sv' => 'Från barnvagnsvänlig strandpromenad till heldagstur över Högberget.',
                'en' => 'From a pram-friendly lakeside stroll to a full-day hike over Högberget.',
            ],
            'body' => [
                'sv' => '<p>Väsman bjuder på vandringar för alla. Här är fem favoriter.</p><ol><li>Strandpromenaden, 3 km</li><li>Hillängen–Sörvik, 7 km</li><li>Högberget, 12 km</li><li>Grängesbergsleden, 15 km</li><li>Runt sjön, 42 km</li></ol>',
                'en' => '<p>Lake Väsman offers hikes for everyone. Here are five favourites.</p><ol><li>The lakeside promenade, 3 km</li><li>Hillängen–Sörvik, 7 km</li><li>Högberget, 12 km</li><li>The Grängesberg trail, 15 km</li><li>Around the lake, 42 km</li></ol>',
            ],
            'author_name' => 'Maria Lindqvist',
            'featured' => false,
            'published_at' => $this->now->subDays(12),
            'image' => ['vasman.jpg', 'Vasman', [50, 90, 140], 'Utsikt över Väsman från Högberget'],
        ]);
    }

    protected function seedPages(): void
    {
        $this->page('Om Allt om Ludvika', 'om-oss', $this->ludvika, [
            'en_titel' => 'About Allt om Ludvika',
            'en_slug' => 'about-us',
            'body' => [
                'sv' => '<p>Allt om Ludvika samlar företag, föreningar, evenemang och reportage från Ludvika kommun på ett ställe.</p><p>Redaktionen sitter på Storgatan och drivs av Allt om-plattformen.</p>',
                'en' => '<p>Allt om Ludvika gathers businesses, associations, events and stories from Ludvika municipality in one place.</p><p>The editorial team is based on Storgatan.</p>',
            ],
        ]);

        $this->page('Kontakt', 'kontakt', $this->ludvika, [
            'en_titel' => 'Contact',
            'en_slug' => 'contact',
            'body' => [
                'sv' => '<p>Tips och frågor: <a href="mailto:redaktionen@alltomludvika.se">redaktionen@alltomludvika.se</a></p><p>Vill du synas med ditt företag? Hör av dig så berättar vi mer.</p>',
                'en' => '<p>Tips and questions: <a href="mailto:redaktionen@alltomludvika.se">redaktionen@alltomludvika.se</a></p>',
            ],
        ]);

        $this->page('Integritetspolicy', 'integritetspolicy', null, [
            'en_titel' => 'Privacy policy',
            'en_slug' => 'privacy-policy',
            'body' => [
                'sv' => '<p>Vi samlar inte in personuppgifter utöver det som krävs för nyhetsbrevet. Du kan avregistrera dig när som helst.</p>',
                'en' => '<p>We collect no personal data beyond what the newsletter requires. You can unsubscribe at any time.</p>',
            ],
        ]);
    }

    protected function seedPromotions(Event $julmarknad, BusinessLocation $bokhandeln, Article $vandringar, BusinessLocation $lyktan): void
    {
        $offer = Offer::query()->where('business_id', $bokhandeln->business_id)->first() ?? new Offer;
        $offer->fill([
            'business_id' => $bokhandeln->business_id,
            'titel' => ['sv' => '20 % på all skönlitteratur', 'en' => '20% off all fiction'],
            'beskrivning' => ['sv' => 'Gäller i butiken till och med månadens slut.', 'en' => 'Valid in store until the end of the month.'],
            'discount_type' => DiscountType::Percentage,
            'discount_value' => 20,
            'code' => null,
            'starts_at' => $this->now->subDay()->utc(),
            'ends_at' => $this->now->addMonth()->utc(),
            'status' => ContentStatus::Published,
        ])->save();

        $this->promotion($julmarknad, ['priority' => 10, 'is_paid' => true, 'price' => 1500]);
        $this->promotion($offer, ['priority' => 5, 'is_paid' => true, 'price' => 500]);
        $this->promotion($vandringar, ['priority' => 1, 'is_paid' => false]);
        $this->promotion($lyktan, ['priority' => 0, 'is_paid' => false]);
    }

    /**
     * @param  list<string>  $categorySlugs
     * @param  array<string, mixed>  $attributes
     */
    protected function business(string $namn, array $categorySlugs, array $attributes): Business
    {
        $business = Business::query()->where('namn', $namn)->first() ?? new Business;
        $business->fill([
            'kind' => BusinessKind::Company,
            'managed_by' => ManagedBy::Editorial,
            'namn' => $namn,
            'status' => ContentStatus::Published,
            'published_at' => $business->published_at ?? $this->now->utc(),
            ...$attributes,
        ])->save();

        $business->categories()->sync(
            Category::query()->where('type', CategoryType::Business)->whereIn('slug', $categorySlugs)->pluck('id'),
        );

        return $business;
    }

    /** @param  array<string, mixed>  $attributes */
    protected function location(Business $business, array $attributes): BusinessLocation
    {
        $location = $business->locations()->where('is_primary', true)->first() ?? new BusinessLocation;
        $location->fill([
            'business_id' => $business->id,
            'kommun_id' => $this->ludvika->id,
            'namn' => null,
            'is_primary' => true,
            'status' => ContentStatus::Published,
            'published_at' => $location->published_at ?? $this->now->utc(),
            ...$attributes,
        ])->save();

        return $location;
    }

    /** @param  array<int, list<array{0: string, 1: string}>>  $week  weekday (0 = måndag) => intervall; tom lista = stängt. */
    protected function openingHours(BusinessLocation $location, array $week): void
    {
        $location->openingHours()->delete();

        foreach ($week as $weekday => $intervals) {
            if ($intervals === []) {
                OpeningHour::create(['business_location_id' => $location->id, 'weekday' => $weekday, 'opens' => null, 'closes' => null, 'closed' => true]);

                continue;
            }

            foreach ($intervals as [$opens, $closes]) {
                OpeningHour::create(['business_location_id' => $location->id, 'weekday' => $weekday, 'opens' => $opens, 'closes' => $closes, 'closed' => false]);
            }
        }
    }

    /** @param  list<array{0: string, 1: ?string, 2: ?string, 3: ?string}>  $rows  [datum, öppnar, stänger, notering]; null-tider = stängt. */
    protected function exceptions(BusinessLocation $location, array $rows): void
    {
        $location->openingHourExceptions()->delete();

        foreach ($rows as [$date, $opens, $closes, $note]) {
            OpeningHourException::create([
                'business_location_id' => $location->id,
                'date' => $date,
                'opens' => $opens,
                'closes' => $closes,
                'closed' => $opens === null,
                'note' => $note,
            ]);
        }
    }

    /** @param  array<string, list<string>>  $menu */
    protected function lunchMenu(BusinessLocation $location, array $menu): void
    {
        LunchMenu::query()->updateOrCreate(
            ['business_location_id' => $location->id, 'iso_year' => $this->now->isoWeekYear, 'week' => $this->now->isoWeek],
            ['menu' => $menu, 'status' => ContentStatus::Published, 'published_at' => $this->now->utc()],
        );
    }

    /**
     * @param  list<string>  $categorySlugs
     * @param  array<string, mixed>  $attributes
     */
    protected function event(string $namn, CarbonImmutable $startsAt, CarbonImmutable $endsAt, array $categorySlugs, array $attributes, ?string $enSlug = null): Event
    {
        $event = Event::query()->where('namn', $namn)->first() ?? new Event;
        $event->fill([
            'kommun_id' => $this->ludvika->id,
            'created_by' => $this->editor->id,
            'namn' => $namn,
            'beskrivning' => ['sv' => "<p>{$namn}.</p>", 'en' => "<p>{$namn}.</p>"],
            'all_day' => false,
            'starts_at' => $startsAt->utc(),
            'ends_at' => $endsAt->utc(),
            'status' => ContentStatus::Published,
            'published_at' => $this->now->utc(),
            ...$attributes,
        ])->save();

        $event->categories()->sync(
            Category::query()->where('type', CategoryType::Event)->whereIn('slug', $categorySlugs)->pluck('id'),
        );

        $event->setSlug('sv', str($namn)->slug()->toString());

        if ($enSlug !== null) {
            $event->setSlug('en', $enSlug);
        }

        return $event;
    }

    /** @param  array<string, mixed>  $attributes */
    protected function article(string $titel, string $slug, array $attributes): Article
    {
        $article = Article::query()->whereCanonicalSlug($slug, 'sv', $this->ludvika)->first() ?? new Article;
        $image = $attributes['image'] ?? null;
        $enTitel = $attributes['en_titel'] ?? $titel;
        $enSlug = $attributes['en_slug'] ?? null;
        unset($attributes['image'], $attributes['en_titel'], $attributes['en_slug']);

        $article->fill([
            'kommun_id' => $this->ludvika->id,
            'author_id' => $this->editor->id,
            'titel' => ['sv' => $titel, 'en' => $enTitel],
            'status' => ContentStatus::Published,
            ...$attributes,
            'published_at' => $attributes['published_at']->utc(),
        ])->save();

        $article->setSlug('sv', $slug);

        if ($enSlug !== null) {
            $article->setSlug('en', $enSlug);
        }

        if ($image !== null) {
            $this->image($article, ...$image);
        }

        return $article;
    }

    /** @param  array<string, mixed>  $attributes */
    protected function page(string $titel, string $slug, ?Kommun $kommun, array $attributes): Page
    {
        $page = Page::query()->whereCanonicalSlug($slug, 'sv', $kommun)->first() ?? new Page;
        $enTitel = $attributes['en_titel'] ?? $titel;
        $enSlug = $attributes['en_slug'] ?? null;
        unset($attributes['en_titel'], $attributes['en_slug']);

        $page->fill([
            'kommun_id' => $kommun?->id,
            'titel' => ['sv' => $titel, 'en' => $enTitel],
            'status' => ContentStatus::Published,
            'published_at' => $page->published_at ?? $this->now->utc(),
            ...$attributes,
        ])->save();

        $page->setSlug('sv', $slug);

        if ($enSlug !== null) {
            $page->setSlug('en', $enSlug);
        }

        return $page;
    }

    /** @param  array<string, mixed>  $attributes */
    protected function promotion(Model $promotable, array $attributes): Promotion
    {
        return Promotion::query()->updateOrCreate(
            [
                'promotable_type' => $promotable->getMorphClass(),
                'promotable_id' => $promotable->getKey(),
                'placement' => PromotionPlacement::Startsida,
            ],
            [
                'kommun_id' => $this->ludvika->id,
                'status' => PromotionStatus::Active,
                'starts_at' => $this->now->subDay()->utc(),
                'ends_at' => $this->now->addMonth()->utc(),
                'created_by' => $this->editor->id,
                ...$attributes,
            ],
        );
    }

    /**
     * En enfärgad platshållarbild med etikett (GD), bara om modellen saknar bilder.
     *
     * @param  array{0: int, 1: int, 2: int}  $rgb
     */
    protected function image(HasMedia&Model $model, string $fileName, string $label, array $rgb, string $alt, int $width = 1200, int $height = 800): void
    {
        if ($model->getMedia(Business::MEDIA_IMAGES)->isNotEmpty()) {
            return;
        }

        $canvas = imagecreatetruecolor($width, $height);
        imagefilledrectangle($canvas, 0, 0, $width, $height, imagecolorallocate($canvas, ...$rgb));
        $font = 5;
        $x = (int) (($width - imagefontwidth($font) * strlen($label)) / 2);
        imagestring($canvas, $font, max($x, 0), (int) ($height / 2) - imagefontheight($font), $label, imagecolorallocate($canvas, 255, 255, 255));

        $path = tempnam(sys_get_temp_dir(), 'demo');
        imagejpeg($canvas, $path, 85);
        imagedestroy($canvas);

        $model->addMedia($path)
            ->usingFileName($fileName)
            ->withCustomProperties(['alt' => $alt])
            ->toMediaCollection(Business::MEDIA_IMAGES);
    }
}
