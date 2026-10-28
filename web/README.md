# Allt om Ludvika – webb

Next.js-frontend (App Router, server components, Tailwind) mot läs-API:et i `../api`
(`/api/v1/{site}`, kontrakt i `../api/public/openapi.json`).

## Kom igång

1. Starta API:et i `../api` (`php artisan serve`, port 8000) och seeda demoinnehåll:

   ```bash
   cd ../api
   php artisan storage:link                       # bilder under /storage
   php artisan db:seed --class=DemoContentSeeder  # Ludvika-innehåll, kan köras om
   ```

2. Miljövariabler i `.env.local`:

   ```bash
   API_URL=http://localhost:8000   # Laravel-appen (även bilder)
   SITE_SLUG=ludvika               # sites.slug
   SITE_URL=http://localhost:3000  # metadataBase för canonical/hreflang/og
   ```

3. `npm install && npm run dev` → <http://localhost:3000>

## Routing

| Sökväg | Innehåll |
| --- | --- |
| `/` | Startsida (`/home`): aktuellt (promotions, "Annons" vid `is_paid`), reportage, kommande evenemang, dagens lunch |
| `/foretag`, `/foretag/[slug]` | Katalog med kategorifilter och sök (`?category=&q=&page=`), företagssida med veckans öppettider, lunch, bilder, evenemang |
| `/evenemang`, `/evenemang/[slug]` | Kalender (`?from=&to=&page=`), evenemang |
| `/artiklar`, `/artiklar/[slug]` | Reportage |
| `/[slug]` | Statiska sidor |
| `/en/...` | Engelska. Svenska har inget prefix; `proxy.ts` skriver om till `/sv/...` internt och skickar `/sv/...` i adressfältet till den prefixlösa adressen. |

Gammal eller annan-språkig slug: API:et svarar 301 med `redirect_to` i body (ingen Location-header),
klienten i `lib/api.ts` svarar med `permanentRedirect` (308) till den kanoniska sökvägen. 404 → `notFound()`.

## Data och cache

- `lib/api-types.ts` genereras från specen: `npm run types:api` (kör om när API:et ändras).
- Kategorifiltret hämtar `/categories?type=business`; hreflang på innehållssidor byggs av `slugs` i detaljsvaren; promotions länkas via `link.path` plus språkprefix.
- `lib/api.ts` sätter `Accept-Language` och ISR-revalidering via fetch: 60 s för listor, 300 s för detaljsidor.
  Detaljsidor och startsidan är statiska med `generateStaticParams() → []` (byggs vid första anrop); listsidor läser `searchParams` och renderas per anrop men återanvänder fetch-cachen.
- Tider från API:et har offset; allt formateras med `Intl.DateTimeFormat` i `Europe/Stockholm` (`lib/datetime.ts`).

## Tester

```bash
npm test          # Vitest: api-klienten (301, locale-header, revalidering), datum, i18n
npm run test:e2e  # Playwright-röktest per route mot seedad api/ (bygger och startar Next på port 3100)
npm run typecheck # next typegen + tsc
npm run lint
```
