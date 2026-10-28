import type { Metadata } from 'next'
import type { Media } from './api'
import { type Locale, defaultLocale, locales, localizedPath, ogLocale } from './i18n'

/**
 * canonical för sidan och hreflang per språk plus x-default (svenska). Samma sökväg för alla
 * språk om inget annat anges; sidor med slug skickar sökvägen per språk (slugPaths) så att
 * bara språk med egen kanonisk slug listas.
 */
export function alternatesFor(
  locale: Locale,
  path: string,
  perLocale?: Partial<Record<Locale, string>>,
): NonNullable<Metadata['alternates']> {
  const languages: Record<string, string> = {}

  for (const candidate of locales) {
    const candidatePath = perLocale ? perLocale[candidate] : path

    if (candidatePath !== undefined) {
      languages[candidate] = localizedPath(candidate, candidatePath)
    }
  }

  const defaultPath = perLocale ? perLocale[defaultLocale] : path

  if (defaultPath !== undefined) {
    languages['x-default'] = localizedPath(defaultLocale, defaultPath)
  }

  return { canonical: localizedPath(locale, path), languages }
}

/** Ren text ur HTML, kapad för meta description. */
export function excerpt(html: string | null | undefined, max = 160): string | undefined {
  if (!html) {
    return undefined
  }

  const text = html
    .replace(/<[^>]*>/g, ' ')
    .replace(/&nbsp;/g, ' ')
    .replace(/&amp;/g, '&')
    .replace(/\s+/g, ' ')
    .trim()

  if (text === '') {
    return undefined
  }

  return text.length > max ? `${text.slice(0, max - 1).trimEnd()}…` : text
}

type PageMetadata = {
  locale: Locale
  /** Sidans sökväg utan språkprefix. */
  path: string
  /** Sökväg per språk när de skiljer sig (innehåll med slug). */
  alternates?: Partial<Record<Locale, string>>
  title?: string
  description?: string | null
  images?: Media[]
  type?: 'website' | 'article'
}

/** Metadata för en sida. Utan title ärvs layoutens default (sajtnamnet) – nyckeln utelämnas därför helt. */
export function pageMetadata({ locale, path, alternates, title, description, images, type = 'website' }: PageMetadata): Metadata {
  const text = excerpt(description)
  const image = images?.[0]

  return {
    ...(title !== undefined && { title }),
    ...(text !== undefined && { description: text }),
    alternates: alternatesFor(locale, path, alternates),
    openGraph: {
      ...(title !== undefined && { title }),
      ...(text !== undefined && { description: text }),
      type,
      locale: ogLocale[locale],
      url: localizedPath(locale, path),
      ...(image && {
        images: [{ url: image.url, width: image.width ?? undefined, height: image.height ?? undefined, alt: image.alt ?? undefined }],
      }),
    },
  }
}
