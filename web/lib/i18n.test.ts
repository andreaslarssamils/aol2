import { describe, expect, it } from 'vitest'
import { localizedPath, switchLocalePath, t } from './i18n'
import { alternatesFor, excerpt, pageMetadata } from './metadata'

describe('localizedPath', () => {
  it('lämnar svenska utan prefix och sätter /en för engelska', () => {
    expect(localizedPath('sv', '/foretag/x')).toBe('/foretag/x')
    expect(localizedPath('en', '/foretag/x')).toBe('/en/foretag/x')
    expect(localizedPath('sv', '/')).toBe('/')
    expect(localizedPath('en', '/')).toBe('/en')
    expect(localizedPath('en', 'om-oss')).toBe('/en/om-oss')
  })
})

describe('switchLocalePath', () => {
  it('byter språk på samma sida', () => {
    expect(switchLocalePath('/en/foretag', 'sv')).toBe('/foretag')
    expect(switchLocalePath('/foretag', 'en')).toBe('/en/foretag')
    expect(switchLocalePath('/', 'en')).toBe('/en')
    expect(switchLocalePath('/en', 'sv')).toBe('/')
    expect(switchLocalePath('/en/foretag/town-hotel', 'sv')).toBe('/foretag/town-hotel')
  })
})

describe('alternatesFor', () => {
  it('ger canonical för språket och hreflang för alla språk plus x-default', () => {
    expect(alternatesFor('en', '/foretag')).toEqual({
      canonical: '/en/foretag',
      languages: { sv: '/foretag', en: '/en/foretag', 'x-default': '/foretag' },
    })
  })

  it('använder sökvägen per språk för innehåll med slug och hoppar över språk utan', () => {
    expect(alternatesFor('en', '/foretag/town-hotel', { sv: '/foretag/stadshotellet', en: '/foretag/town-hotel' })).toEqual({
      canonical: '/en/foretag/town-hotel',
      languages: { sv: '/foretag/stadshotellet', en: '/en/foretag/town-hotel', 'x-default': '/foretag/stadshotellet' },
    })
    expect(alternatesFor('en', '/foretag/stadshotellet', { sv: '/foretag/stadshotellet' })).toEqual({
      canonical: '/en/foretag/stadshotellet',
      languages: { sv: '/foretag/stadshotellet', 'x-default': '/foretag/stadshotellet' },
    })
  })
})

describe('pageMetadata', () => {
  it('utelämnar title och description när de saknas så att layoutens default gäller', () => {
    const metadata = pageMetadata({ locale: 'sv', path: '/' })

    expect(metadata).not.toHaveProperty('title')
    expect(metadata).not.toHaveProperty('description')
    expect(metadata.openGraph).not.toHaveProperty('title')
    expect(metadata.alternates?.canonical).toBe('/')
  })

  it('sätter title, description ur HTML, bild och og:type', () => {
    const metadata = pageMetadata({
      locale: 'en',
      path: '/artiklar/x',
      title: 'Rubrik',
      description: '<p>Ingress</p>',
      images: [{ url: 'http://img/x.jpg', width: 1200, height: 800, alt: 'Alt', mime_type: 'image/jpeg' }],
      type: 'article',
    })

    expect(metadata.title).toBe('Rubrik')
    expect(metadata.description).toBe('Ingress')
    expect(metadata.openGraph).toMatchObject({
      type: 'article',
      locale: 'en_GB',
      url: '/en/artiklar/x',
      images: [{ url: 'http://img/x.jpg', width: 1200, height: 800, alt: 'Alt' }],
    })
  })
})

describe('excerpt', () => {
  it('tar bort HTML och kapar texten', () => {
    expect(excerpt('<p>Hej &amp; hå</p><p>Två</p>')).toBe('Hej & hå Två')
    expect(excerpt('x'.repeat(200))?.length).toBe(160)
    expect(excerpt('')).toBeUndefined()
    expect(excerpt(null)).toBeUndefined()
  })
})

describe('ordlistor', () => {
  function keys(value: unknown, prefix = ''): string[] {
    if (typeof value !== 'object' || value === null || Array.isArray(value)) {
      return [prefix]
    }

    return Object.entries(value).flatMap(([key, child]) => keys(child, prefix ? `${prefix}.${key}` : key))
  }

  it('har samma nycklar på alla språk', () => {
    expect(keys(t('en'))).toEqual(keys(t('sv')))
  })
})
