import { notFound, permanentRedirect } from 'next/navigation'
import type { components } from './api-types'
import { type Locale, isLocale, localizedPath } from './i18n'

/**
 * Tunn klient mot läs-API:et (api/public/openapi.json). Alla anrop går till
 * {API_URL}/api/v1/{SITE_SLUG} med Accept-Language för språket och Next-fetchens
 * ISR-revalidering: 60 s för listor, 300 s för detaljsidor.
 *
 * Slug-uppslag: 301 (redirect_to i body, ingen Location) → permanentRedirect till den
 * kanoniska sökvägen, 404 → notFound(). Övriga fel kastar ApiError.
 */
type Schemas = components['schemas']

export type Site = Schemas['Site']
export type Home = Schemas['Home']
export type Kommun = Schemas['Kommun']
export type Category = Schemas['Category']
export type Media = Schemas['Media']
export type Business = Schemas['Business']
export type BusinessLocation = Schemas['BusinessLocation']
export type BusinessLocationDetail = Schemas['BusinessLocationDetail']
export type OpeningDay = Schemas['OpeningDay']
export type LunchMenu = Schemas['LunchMenu']
export type Event = Schemas['Event']
export type EventDetail = Schemas['EventDetail']
export type Article = Schemas['Article']
export type ArticleDetail = Schemas['ArticleDetail']
export type Page = Schemas['Page']
export type Offer = Schemas['Offer']
export type Promotion = Schemas['Promotion']
export type ContentLink = Schemas['Link']
export type PaginationMeta = Schemas['PaginationMeta']

export type Paginated<T> = {
  data: T[]
  links: Schemas['PaginationLinks']
  meta: PaginationMeta
}

export const LIST_REVALIDATE = 60
export const DETAIL_REVALIDATE = 300

/** Frontendens sökvägssegment per innehållstyp – håll i takt med config('api.frontend.paths') i api/. */
export const segments = {
  business_location: '/foretag',
  event: '/evenemang',
  article: '/artiklar',
  page: '',
} as const

export class ApiError extends Error {
  constructor(
    public readonly status: number,
    public readonly url: string,
  ) {
    super(`API:et svarade ${status} för ${url}`)
    this.name = 'ApiError'
  }
}

type Query = Record<string, string | number | boolean | undefined | null>

function settings(): { base: string; site: string } {
  const base = process.env.API_URL
  const site = process.env.SITE_SLUG

  if (!base || !site) {
    throw new Error('API_URL och SITE_SLUG måste vara satta (se README).')
  }

  return { base: base.replace(/\/+$/, ''), site }
}

/** Absolut URL till en resurs; tomma frågeparametrar utelämnas. */
export function apiUrl(path: string, query: Query = {}): string {
  const { base, site } = settings()
  const url = new URL(`${base}/api/v1/${site}${path}`)

  for (const [key, value] of Object.entries(query)) {
    if (value === undefined || value === null || value === '') {
      continue
    }

    url.searchParams.set(key, String(value))
  }

  return url.toString()
}

function request(url: string, locale: Locale, revalidate: number): Promise<Response> {
  return fetch(url, {
    headers: { Accept: 'application/json', 'Accept-Language': locale },
    next: { revalidate },
  })
}

async function getJson<T>(path: string, locale: Locale, revalidate: number, query: Query = {}): Promise<T> {
  const url = apiUrl(path, query)
  const response = await request(url, locale, revalidate)

  if (response.status === 404) {
    notFound()
  }

  if (!response.ok) {
    throw new ApiError(response.status, url)
  }

  return (await response.json()) as T
}

async function getBySlug<T>(apiPath: string, segment: string, slug: string, locale: Locale): Promise<T> {
  const url = apiUrl(`${apiPath}/${encodeURIComponent(slug)}`)
  const response = await request(url, locale, DETAIL_REVALIDATE)

  if (response.status === 301) {
    const { redirect_to } = (await response.json()) as Schemas['Redirect']

    permanentRedirect(localizedPath(locale, `${segment}/${redirect_to}`))
  }

  if (response.status === 404) {
    notFound()
  }

  if (!response.ok) {
    throw new ApiError(response.status, url)
  }

  const { data } = (await response.json()) as { data: T }

  return data
}

export async function getSite(locale: Locale): Promise<Site> {
  const { data } = await getJson<{ data: Site }>('/site', locale, DETAIL_REVALIDATE)

  return data
}

export async function getHome(locale: Locale): Promise<Home> {
  const { data } = await getJson<{ data: Home }>('/home', locale, LIST_REVALIDATE)

  return data
}

export type BusinessQuery = { category?: string; q?: string; page?: number; per_page?: number }

export function listBusinesses(locale: Locale, query: BusinessQuery = {}): Promise<Paginated<BusinessLocation>> {
  return getJson('/businesses', locale, LIST_REVALIDATE, query)
}

export type EventQuery = { from?: string; to?: string; category?: string; page?: number; per_page?: number }

export function listEvents(locale: Locale, query: EventQuery = {}): Promise<Paginated<Event>> {
  return getJson('/events', locale, LIST_REVALIDATE, query)
}

export type ArticleQuery = { page?: number; per_page?: number; featured?: boolean }

export function listArticles(locale: Locale, query: ArticleQuery = {}): Promise<Paginated<Article>> {
  return getJson('/articles', locale, LIST_REVALIDATE, { ...query, featured: query.featured ? 1 : undefined })
}

export type Placement = Promotion['placement']

export async function listPromotions(locale: Locale, placement?: Placement): Promise<Promotion[]> {
  const { data } = await getJson<{ data: Promotion[] }>('/promotions', locale, LIST_REVALIDATE, { placement })

  return data
}

export type CategoryType = 'business' | 'event'

/** Kategorier med publicerat innehåll på sajten, valfritt avgränsat till en typ. */
export async function listCategories(locale: Locale, type?: CategoryType): Promise<Category[]> {
  const { data } = await getJson<{ data: Category[] }>('/categories', locale, LIST_REVALIDATE, { type })

  return data
}

export function getBusiness(slug: string, locale: Locale): Promise<BusinessLocationDetail> {
  return getBySlug('/businesses', segments.business_location, slug, locale)
}

export function getEvent(slug: string, locale: Locale): Promise<EventDetail> {
  return getBySlug('/events', segments.event, slug, locale)
}

export function getArticle(slug: string, locale: Locale): Promise<ArticleDetail> {
  return getBySlug('/articles', segments.article, slug, locale)
}

export function getPage(slug: string, locale: Locale): Promise<Page> {
  return getBySlug('/pages', segments.page, slug, locale)
}

/** Länk för en promotion: API:ets sajtrelativa `path` med språkprefix. null när innehållet saknar sida. */
export function promotionHref(link: ContentLink, locale: Locale): string | null {
  return link?.path ? localizedPath(locale, link.path) : null
}

/** Sökväg per språk för hreflang: segmentet plus den kanoniska sluggen för varje språk som har en. */
export function slugPaths(segment: string, slugs: Record<string, string>): Partial<Record<Locale, string>> {
  const paths: Partial<Record<Locale, string>> = {}

  for (const [locale, slug] of Object.entries(slugs)) {
    if (isLocale(locale)) {
      paths[locale] = `${segment}/${slug}`
    }
  }

  return paths
}
