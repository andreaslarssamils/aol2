import { beforeEach, describe, expect, it, vi } from 'vitest'

vi.mock('next/navigation', () => ({
  notFound: vi.fn(() => {
    throw new Error('NEXT_HTTP_ERROR_FALLBACK;404')
  }),
  permanentRedirect: vi.fn((path: string) => {
    throw new Error(`NEXT_REDIRECT;${path}`)
  }),
}))

import { notFound, permanentRedirect } from 'next/navigation'
import {
  ApiError,
  DETAIL_REVALIDATE,
  LIST_REVALIDATE,
  getArticle,
  getBusiness,
  getEvent,
  getHome,
  getPage,
  getSite,
  listArticles,
  listBusinesses,
  listCategories,
  listEvents,
  promotionHref,
  slugPaths,
} from './api'

const fetchMock = vi.fn<typeof fetch>()

function json(status: number, body: unknown): Response {
  return new Response(JSON.stringify(body), { status, headers: { 'Content-Type': 'application/json' } })
}

function lastRequest() {
  const [input, init] = fetchMock.mock.calls.at(-1) as [string, RequestInit & { next?: { revalidate?: number } }]

  return { url: new URL(input), headers: new Headers(init.headers), init }
}

beforeEach(() => {
  fetchMock.mockReset()
  vi.stubGlobal('fetch', fetchMock)
  vi.stubEnv('API_URL', 'http://api.test/')
  vi.stubEnv('SITE_SLUG', 'ludvika')
  vi.mocked(notFound).mockClear()
  vi.mocked(permanentRedirect).mockClear()
})

describe('listor', () => {
  it('anropar sajtens endpoint med Accept-Language och 60 s revalidering', async () => {
    fetchMock.mockResolvedValue(json(200, { data: [], links: {}, meta: { total: 0 } }))

    await listBusinesses('en', { category: 'butik', q: 'roma', page: 2 })

    const { url, headers, init } = lastRequest()
    expect(`${url.origin}${url.pathname}`).toBe('http://api.test/api/v1/ludvika/businesses')
    expect(Object.fromEntries(url.searchParams)).toEqual({ category: 'butik', q: 'roma', page: '2' })
    expect(headers.get('accept-language')).toBe('en')
    expect(headers.get('accept')).toBe('application/json')
    expect(init.next).toEqual({ revalidate: LIST_REVALIDATE })
  })

  it('utelämnar tomma frågeparametrar', async () => {
    fetchMock.mockResolvedValue(json(200, { data: [], links: {}, meta: {} }))

    await listEvents('sv', { from: undefined, to: '', page: 1 })

    expect(lastRequest().url.search).toBe('?page=1')
  })

  it('skickar featured som 1 bara när det är satt', async () => {
    fetchMock.mockImplementation(async () => json(200, { data: [], links: {}, meta: {} }))

    await listArticles('sv', { featured: true })
    expect(lastRequest().url.search).toBe('?featured=1')

    await listArticles('sv', { featured: false })
    expect(lastRequest().url.search).toBe('')
  })

  it('packar upp data för startsidan och sajten', async () => {
    fetchMock.mockResolvedValue(json(200, { data: { namn: 'Allt om Ludvika' } }))

    await expect(getSite('sv')).resolves.toEqual({ namn: 'Allt om Ludvika' })
    expect(lastRequest().init.next).toEqual({ revalidate: DETAIL_REVALIDATE })

    fetchMock.mockResolvedValue(json(200, { data: { promotions: [] } }))
    await expect(getHome('en')).resolves.toEqual({ promotions: [] })
    expect(lastRequest().headers.get('accept-language')).toBe('en')
    expect(lastRequest().init.next).toEqual({ revalidate: LIST_REVALIDATE })
  })

  it('hämtar kategorier med typfilter och packar upp data', async () => {
    fetchMock.mockImplementation(async () => json(200, { data: [{ slug: 'butik', namn: 'Butik', type: 'business', icon: null }] }))

    const categories = await listCategories('sv', 'business')

    expect(categories).toEqual([{ slug: 'butik', namn: 'Butik', type: 'business', icon: null }])
    expect(lastRequest().url.pathname).toBe('/api/v1/ludvika/categories')
    expect(lastRequest().url.search).toBe('?type=business')
    expect(lastRequest().init.next).toEqual({ revalidate: LIST_REVALIDATE })

    await listCategories('en')
    expect(lastRequest().url.search).toBe('')
    expect(lastRequest().headers.get('accept-language')).toBe('en')
  })

  it('404 ger notFound()', async () => {
    fetchMock.mockResolvedValue(json(404, { message: 'Sajten finns inte.' }))

    await expect(getHome('sv')).rejects.toThrow('NEXT_HTTP_ERROR_FALLBACK;404')
    expect(notFound).toHaveBeenCalledOnce()
  })

  it('andra fel kastar ApiError med status och URL', async () => {
    fetchMock.mockResolvedValue(json(500, { message: 'boom' }))

    await expect(listBusinesses('sv')).rejects.toMatchObject({
      name: 'ApiError',
      status: 500,
      url: 'http://api.test/api/v1/ludvika/businesses',
    })
    await expect(listBusinesses('sv')).rejects.toBeInstanceOf(ApiError)
    expect(notFound).not.toHaveBeenCalled()
  })
})

describe('detaljsidor via slug', () => {
  it('hämtar med 300 s revalidering, locale-header och packar upp data', async () => {
    fetchMock.mockResolvedValue(json(200, { data: { slug: 'stadshotellet', namn: 'Stadshotellet' } }))

    const location = await getBusiness('stadshotellet', 'sv')

    expect(location.namn).toBe('Stadshotellet')
    const { url, headers, init } = lastRequest()
    expect(url.pathname).toBe('/api/v1/ludvika/businesses/stadshotellet')
    expect(headers.get('accept-language')).toBe('sv')
    expect(init.next).toEqual({ revalidate: DETAIL_REVALIDATE })
  })

  it('URL-kodar sluggen', async () => {
    fetchMock.mockResolvedValue(json(200, { data: {} }))

    await getEvent('a b/c', 'sv')

    expect(lastRequest().url.pathname).toBe('/api/v1/ludvika/events/a%20b%2Fc')
  })

  it('301 med redirect_to i body (ingen Location) ger permanentRedirect till den kanoniska sökvägen', async () => {
    fetchMock.mockResolvedValue(json(301, { redirect_to: 'town-hotel' }))

    await expect(getBusiness('stadshotellet', 'en')).rejects.toThrow('NEXT_REDIRECT;/en/foretag/town-hotel')
    expect(permanentRedirect).toHaveBeenCalledWith('/en/foretag/town-hotel')
    expect(notFound).not.toHaveBeenCalled()
  })

  it('svenska sökvägar får inget språkprefix', async () => {
    fetchMock.mockResolvedValue(json(301, { redirect_to: 'stadshotellet' }))

    await expect(getBusiness('stadshotellet-ludvika', 'sv')).rejects.toThrow('NEXT_REDIRECT;/foretag/stadshotellet')
  })

  it('använder rätt segment per innehållstyp', async () => {
    fetchMock.mockResolvedValue(json(301, { redirect_to: 'ny' }))
    await expect(getEvent('gammal', 'sv')).rejects.toThrow('NEXT_REDIRECT;/evenemang/ny')

    fetchMock.mockResolvedValue(json(301, { redirect_to: 'ny' }))
    await expect(getArticle('gammal', 'en')).rejects.toThrow('NEXT_REDIRECT;/en/artiklar/ny')

    fetchMock.mockResolvedValue(json(301, { redirect_to: 'about-us' }))
    await expect(getPage('om-oss', 'en')).rejects.toThrow('NEXT_REDIRECT;/en/about-us')

    fetchMock.mockResolvedValue(json(301, { redirect_to: 'om-oss' }))
    await expect(getPage('om', 'sv')).rejects.toThrow('NEXT_REDIRECT;/om-oss')
  })

  it('404 ger notFound()', async () => {
    fetchMock.mockResolvedValue(json(404, { message: 'Finns inte.' }))

    await expect(getPage('finns-inte', 'sv')).rejects.toThrow('NEXT_HTTP_ERROR_FALLBACK;404')
    expect(notFound).toHaveBeenCalledOnce()
    expect(permanentRedirect).not.toHaveBeenCalled()
  })

  it('andra fel kastar ApiError', async () => {
    fetchMock.mockResolvedValue(json(503, { message: 'nere' }))

    await expect(getArticle('x', 'sv')).rejects.toMatchObject({ status: 503 })
  })
})

describe('promotionHref', () => {
  const link = {
    type: 'event' as const,
    slug: 'julmarknad',
    locale: 'sv',
    kommun: 'ludvika',
    site: 'ludvika',
    path: '/evenemang/julmarknad',
  }

  it('sätter språkprefix på API:ets sajtrelativa sökväg', () => {
    expect(promotionHref(link, 'sv')).toBe('/evenemang/julmarknad')
    expect(promotionHref(link, 'en')).toBe('/en/evenemang/julmarknad')
  })

  it('saknar länk när innehållet inte har någon sida', () => {
    expect(promotionHref(null, 'sv')).toBeNull()
    expect(promotionHref({ ...link, path: null }, 'sv')).toBeNull()
  })
})

describe('slugPaths', () => {
  it('ger sökväg per språk för de språk som har kanonisk slug', () => {
    expect(slugPaths('/foretag', { sv: 'stadshotellet', en: 'town-hotel' })).toEqual({
      sv: '/foretag/stadshotellet',
      en: '/foretag/town-hotel',
    })
    expect(slugPaths('', { sv: 'om-oss' })).toEqual({ sv: '/om-oss' })
    expect(slugPaths('/evenemang', { sv: 'x', de: 'y' })).toEqual({ sv: '/evenemang/x' })
  })
})
