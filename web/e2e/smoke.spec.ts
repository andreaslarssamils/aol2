import { type APIRequestContext, expect, test } from '@playwright/test'

/**
 * Röktest per route mot en körande, seedad api/ (php artisan db:seed --class=DemoContentSeeder).
 * Förväntningarna hämtas från API:et så att testerna följer innehållet; de slugs som är
 * hårdkodade (stadshotellet, town-hotel, om-oss) kommer från demo-seedern.
 */
const API_URL = (process.env.API_URL ?? 'http://localhost:8000').replace(/\/$/, '')
const SITE_SLUG = process.env.SITE_SLUG ?? 'ludvika'

type Row = { slug: string | null; namn?: string; titel?: string; starts_at?: string }

async function api<T>(request: APIRequestContext, path: string, locale = 'sv'): Promise<T> {
  const response = await request.get(`${API_URL}/api/v1/${SITE_SLUG}${path}`, {
    headers: { 'Accept-Language': locale },
  })

  expect(response.ok(), `API ${path} svarade ${response.status()} – är api/ igång och seedad?`).toBeTruthy()

  return (await response.json()) as T
}

function escape(value: string): string {
  return value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')
}

test.describe('startsida', () => {
  test('/ visar aktuellt, reportage, evenemang och dagens lunch', async ({ page, request }) => {
    const { data: site } = await api<{ data: { namn: string } }>(request, '/site')
    const { data: home } = await api<{
      data: { promotions: { is_paid: boolean }[]; featured_articles: Row[]; lunch_menus: { location: { namn: string } }[] }
    }>(request, '/home')

    await page.goto('/')

    await expect(page).toHaveTitle(site.namn)
    await expect(page.locator('html')).toHaveAttribute('lang', 'sv')
    await expect(page.getByRole('heading', { level: 2, name: 'Reportage' })).toBeVisible()
    await expect(page.getByRole('heading', { level: 2, name: 'På gång' })).toBeVisible()
    await expect(page.getByRole('heading', { level: 2, name: 'Dagens lunch' })).toBeVisible()

    expect(home.featured_articles.length).toBeGreaterThan(0)
    for (const article of home.featured_articles) {
      await expect(page.getByRole('link', { name: article.titel as string, exact: true })).toBeVisible()
    }

    if (home.promotions.some((promotion) => promotion.is_paid)) {
      await expect(page.getByText('Annons', { exact: true }).first()).toBeVisible()
    }

    for (const menu of home.lunch_menus) {
      await expect(page.getByRole('link', { name: menu.location.namn, exact: true }).first()).toBeVisible()
    }
  })

  test('/en visar startsidan på engelska med hreflang', async ({ page }) => {
    await page.goto('/en')

    await expect(page.locator('html')).toHaveAttribute('lang', 'en')
    await expect(page.getByRole('link', { name: 'Businesses', exact: true })).toBeVisible()
    await expect(page.getByRole('heading', { level: 2, name: "Today's lunch" })).toBeVisible()
    // Roten renderas utan avslutande snedstreck: "http://host" eller "http://host/".
    await expect(page.locator('link[rel="alternate"][hreflang="sv"]')).toHaveAttribute('href', /^https?:\/\/[^/]+\/?$/)
    await expect(page.locator('link[rel="alternate"][hreflang="en"]')).toHaveAttribute('href', /^https?:\/\/[^/]+\/en$/)
    await expect(page.locator('link[rel="alternate"][hreflang="x-default"]')).toHaveAttribute('href', /^https?:\/\/[^/]+\/?$/)
  })
})

test.describe('företag', () => {
  test('/foretag listar katalogen och filtrerar via URL-parametrar', async ({ page, request }) => {
    const all = await api<{ data: Row[] }>(request, '/businesses')
    const filtered = await api<{ data: Row[]; meta: { total: number } }>(request, '/businesses?category=restaurang-cafe')

    await page.goto('/foretag')

    await expect(page.getByRole('heading', { level: 1 })).toHaveText('Företag och föreningar')
    for (const row of all.data.slice(0, 3)) {
      await expect(page.getByRole('link', { name: row.namn as string, exact: true })).toBeVisible()
    }

    const categories = await api<{ data: { slug: string; namn: string }[] }>(request, '/categories?type=business')

    await page.goto('/foretag?category=restaurang-cafe')

    for (const category of categories.data) {
      await expect(page.locator('select[name="category"] option', { hasText: category.namn })).toHaveAttribute('value', category.slug)
    }

    const total = filtered.meta.total
    await expect(page.getByRole('status')).toHaveText(total === 1 ? '1 träff' : `${total} träffar`)
    await expect(page.locator('select[name="category"]')).toHaveValue('restaurang-cafe')
    for (const row of filtered.data) {
      await expect(page.getByRole('link', { name: row.namn as string, exact: true })).toBeVisible()
    }

    await page.goto('/foretag?q=zzz-finns-inte')

    await expect(page.getByText('Inga företag matchade.')).toBeVisible()
    await expect(page.locator('input[name="q"]')).toHaveValue('zzz-finns-inte')
  })

  test('/foretag/[slug] visar öppettider med dagens rad, avvikelser, lunch och kontakt', async ({ page, request }) => {
    const list = await api<{ data: Row[] }>(request, '/businesses')
    const slug = list.data.find((row) => row.slug === 'stadshotellet')?.slug ?? (list.data[0].slug as string)
    const { data } = await api<{
      data: {
        namn: string
        slugs: Record<string, string>
        opening_hours: { is_exception: boolean; note: string | null }[]
        lunch_menu: { today: string[] } | null
      }
    }>(request, `/businesses/${slug}`)

    await page.goto(`/foretag/${slug}`)

    await expect(page.getByRole('heading', { level: 1 })).toHaveText(data.namn)
    await expect(page).toHaveTitle(new RegExp(`^${escape(data.namn)} – `))
    await expect(page.locator('link[rel="canonical"]')).toHaveAttribute('href', new RegExp(`/foretag/${escape(slug)}$`))
    if (data.slugs.en) {
      await expect(page.locator('link[rel="alternate"][hreflang="en"]')).toHaveAttribute(
        'href',
        new RegExp(`/en/foretag/${escape(data.slugs.en)}$`),
      )
    }
    await expect(page.getByRole('heading', { name: 'Öppettider denna vecka' })).toBeVisible()
    await expect(page.locator('tr[aria-current="date"]')).toHaveCount(1)
    await expect(page.locator('tr[aria-current="date"]')).toContainText('I dag')

    const exception = data.opening_hours.find((day) => day.is_exception)
    if (exception?.note) {
      await expect(page.getByText(exception.note)).toBeVisible()
    }

    if (data.lunch_menu) {
      for (const dish of data.lunch_menu.today) {
        await expect(page.getByText(dish, { exact: true }).first()).toBeVisible()
      }
    }

    await expect(page.getByRole('heading', { name: 'Kontakt' })).toBeVisible()
  })

  test('gammal slug svarar 308 till den kanoniska', async ({ request }) => {
    const response = await request.get('/foretag/stadshotellet-ludvika', { maxRedirects: 0 })

    expect(response.status()).toBe(308)
    expect(response.headers()['location']).toMatch(/\/foretag\/stadshotellet$/)
  })

  test('svensk slug under /en svarar 308 till den engelska', async ({ request }) => {
    const response = await request.get('/en/foretag/stadshotellet', { maxRedirects: 0 })

    expect(response.status()).toBe(308)
    expect(response.headers()['location']).toMatch(/\/en\/foretag\/town-hotel$/)
  })

  test('okänd slug ger 404 i sajtens layout', async ({ page }) => {
    const response = await page.goto('/foretag/finns-inte-alls')

    expect(response?.status()).toBe(404)
    await expect(page.getByRole('heading', { level: 1 })).toHaveText('Sidan finns inte')
    await expect(page.getByRole('link', { name: 'Företag' })).toBeVisible()
  })
})

test.describe('evenemang', () => {
  test('/evenemang grupperar kalendern per dag och filtrerar på från/till', async ({ page, request }) => {
    const { data } = await api<{ data: Row[] }>(request, '/events')
    expect(data.length).toBeGreaterThan(1)

    await page.goto('/evenemang')

    await expect(page.getByRole('heading', { level: 1 })).toHaveText('Evenemang')
    for (const event of data.slice(0, 3)) {
      await expect(page.getByRole('link', { name: event.namn as string, exact: true })).toBeVisible()
    }

    const day = (data[0].starts_at as string).slice(0, 10)
    const later = data.find((event) => (event.starts_at as string).slice(0, 10) > day)

    await page.goto(`/evenemang?from=${day}&to=${day}`)

    await expect(page.locator('input[name="from"]')).toHaveValue(day)
    await expect(page.locator('input[name="to"]')).toHaveValue(day)
    await expect(page.getByRole('link', { name: data[0].namn as string, exact: true })).toBeVisible()
    if (later) {
      await expect(page.getByRole('link', { name: later.namn as string, exact: true })).toHaveCount(0)
    }
  })

  test('/evenemang/[slug] visar tid, plats och beskrivning', async ({ page, request }) => {
    const { data } = await api<{ data: Row[] }>(request, '/events')
    const event = data[0]

    await page.goto(`/evenemang/${event.slug}`)

    await expect(page.getByRole('heading', { level: 1 })).toHaveText(event.namn as string)
    await expect(page.getByText('När', { exact: true })).toBeVisible()
    await expect(page.locator(`time[datetime="${event.starts_at}"]`).first()).toBeVisible()
    await expect(page.locator('.prose')).toBeVisible()
  })
})

test.describe('artiklar', () => {
  test('/artiklar listar reportage', async ({ page, request }) => {
    const { data } = await api<{ data: Row[] }>(request, '/articles')

    await page.goto('/artiklar')

    await expect(page.getByRole('heading', { level: 1 })).toHaveText('Reportage')
    for (const article of data.slice(0, 3)) {
      await expect(page.getByRole('link', { name: article.titel as string, exact: true })).toBeVisible()
    }
  })

  test('/artiklar/[slug] visar reportaget med brödtext och metadata', async ({ page, request }) => {
    const { data } = await api<{ data: Row[] }>(request, '/articles')
    const { data: article } = await api<{ data: { titel: string; author_name: string | null } }>(request, `/articles/${data[0].slug}`)

    await page.goto(`/artiklar/${data[0].slug}`)

    await expect(page.getByRole('heading', { level: 1 })).toHaveText(article.titel)
    await expect(page).toHaveTitle(new RegExp(`^${escape(article.titel)} – `))
    if (article.author_name) {
      await expect(page.getByText(`Av ${article.author_name}`)).toBeVisible()
    }
    await expect(page.locator('.prose')).toBeVisible()
    await expect(page.locator('meta[property="og:type"]')).toHaveAttribute('content', 'article')
  })
})

test.describe('sidor', () => {
  test('/[slug] visar en statisk sida med canonical', async ({ page, request }) => {
    const { data } = await api<{ data: { titel: string; slugs: Record<string, string> } }>(request, '/pages/om-oss')

    await page.goto('/om-oss')

    await expect(page.getByRole('heading', { level: 1 })).toHaveText(data.titel)
    await expect(page.locator('.prose')).toBeVisible()
    await expect(page.locator('link[rel="canonical"]')).toHaveAttribute('href', /\/om-oss$/)
    if (data.slugs.en) {
      await expect(page.locator('link[rel="alternate"][hreflang="en"]')).toHaveAttribute('href', new RegExp(`/en/${escape(data.slugs.en)}$`))
    }
  })

  test('/sv/... skickas till adressen utan prefix', async ({ request }) => {
    const response = await request.get('/sv/foretag', { maxRedirects: 0 })

    expect(response.status()).toBe(308)
    expect(response.headers()['location']).toMatch(/\/foretag$/)
  })

  test('okänd adress med flera segment ger 404 i sajtens layout', async ({ page }) => {
    const response = await page.goto('/finns/inte')

    expect(response?.status()).toBe(404)
    await expect(page.getByRole('link', { name: 'Till startsidan' })).toBeVisible()
  })
})
