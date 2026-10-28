import { notFound } from 'next/navigation'
import { type Locale, isLocale } from './i18n'

export type SearchParams = Record<string, string | string[] | undefined>

/** Språket ur [lang]; proxy.ts garanterar ett giltigt värde, allt annat är 404. */
export function asLocale(lang: string): Locale {
  if (!isLocale(lang)) {
    notFound()
  }

  return lang
}

/** Första värdet för en frågeparameter, trimmat; tomt → undefined. */
export function param(searchParams: SearchParams, key: string): string | undefined {
  const value = searchParams[key]
  const first = (Array.isArray(value) ? value[0] : value)?.trim()

  return first ? first : undefined
}

export function pageParam(searchParams: SearchParams): number {
  const page = Number.parseInt(param(searchParams, 'page') ?? '1', 10)

  return Number.isFinite(page) && page > 0 ? page : 1
}

/** YYYY-MM-DD, annars undefined – API:et tolkar datumet i Europe/Stockholm. */
export function dateParam(searchParams: SearchParams, key: string): string | undefined {
  const value = param(searchParams, key)

  return value && /^\d{4}-\d{2}-\d{2}$/.test(value) ? value : undefined
}

/** Sökväg med frågesträng; tomma värden utelämnas. */
export function withQuery(path: string, query: Record<string, string | number | undefined>): string {
  const params = new URLSearchParams()

  for (const [key, value] of Object.entries(query)) {
    if (value !== undefined && value !== '') {
      params.set(key, String(value))
    }
  }

  const search = params.toString()

  return search ? `${path}?${search}` : path
}
