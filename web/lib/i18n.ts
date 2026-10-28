/**
 * Språk i URL:en: svenska utan prefix (/foretag), övriga språk med (/en/foretag).
 * proxy.ts skriver om svenska sökvägar till /sv/... internt så att app/[lang] alltid får språket.
 */
export const locales = ['sv', 'en'] as const

export type Locale = (typeof locales)[number]

export const defaultLocale: Locale = 'sv'

export function isLocale(value: string | undefined): value is Locale {
  return locales.includes(value as Locale)
}

/** BCP 47-tagg för Intl. */
export const intlLocale: Record<Locale, string> = { sv: 'sv-SE', en: 'en-GB' }

/** Open Graph-locale. */
export const ogLocale: Record<Locale, string> = { sv: 'sv_SE', en: 'en_GB' }

/** Besökarens sökväg för ett språk: "/foretag/x" → "/foretag/x" (sv) eller "/en/foretag/x". */
export function localizedPath(locale: Locale, path: string): string {
  const clean = path.startsWith('/') ? path : `/${path}`

  if (locale === defaultLocale) {
    return clean
  }

  return clean === '/' ? `/${locale}` : `/${locale}${clean}`
}

/** Samma sida på ett annat språk: tar bort ett befintligt språkprefix och sätter det nya. */
export function switchLocalePath(pathname: string, to: Locale): string {
  const [, first = '', ...rest] = pathname.split('/')
  const bare = isLocale(first) ? `/${rest.join('/')}` : pathname

  return localizedPath(to, bare === '' ? '/' : bare)
}

export function capitalize(value: string): string {
  return value.charAt(0).toUpperCase() + value.slice(1)
}

const sv = {
  switchLanguage: 'English',
  nav: { home: 'Start', businesses: 'Företag', events: 'Evenemang', articles: 'Artiklar' },
  site: {
    description: (place: string) => `Företag, föreningar, evenemang och reportage i ${place}.`,
    skipToContent: 'Till innehållet',
  },
  home: {
    promotions: 'Aktuellt',
    articles: 'Reportage',
    events: 'På gång',
    lunch: 'Dagens lunch',
    noLunch: 'Ingen lunch inlagd i dag.',
    noEvents: 'Inga kommande evenemang.',
    allArticles: 'Alla reportage',
    allEvents: 'Hela kalendern',
    allBusinesses: 'Alla företag',
  },
  ad: 'Annons',
  offer: 'Erbjudande',
  businesses: {
    title: 'Företag och föreningar',
    category: 'Kategori',
    allCategories: 'Alla kategorier',
    search: 'Sök',
    searchPlaceholder: 'Namn eller ort',
    filter: 'Visa',
    reset: 'Rensa filter',
    results: (count: number) => (count === 1 ? '1 träff' : `${count} träffar`),
    none: 'Inga företag matchade.',
    openNow: 'Öppet nu',
    closedNow: 'Stängt nu',
  },
  business: {
    openingHours: 'Öppettider denna vecka',
    today: 'I dag',
    closed: 'Stängt',
    noHours: 'Öppettider saknas.',
    exception: 'Avvikande tider',
    lunch: (week: number) => `Lunch vecka ${week}`,
    events: 'På gång här',
    contact: 'Kontakt',
    phone: 'Telefon',
    email: 'E-post',
    website: 'Webbplats',
    images: 'Bilder',
  },
  events: {
    title: 'Evenemang',
    from: 'Från',
    to: 'Till',
    show: 'Visa',
    reset: 'Alla kommande',
    none: 'Inga evenemang i perioden.',
    allDay: 'Hela dagen',
    price: 'Pris',
    tickets: 'Biljetter',
    venue: 'Plats',
    when: 'När',
  },
  articles: { title: 'Reportage', by: 'Av', none: 'Inga reportage ännu.' },
  pagination: {
    previous: 'Föregående',
    next: 'Nästa',
    page: (current: number, last: number) => `Sida ${current} av ${last}`,
  },
  notFound: {
    title: 'Sidan finns inte',
    text: 'Innehållet kan ha flyttats eller tagits bort.',
    home: 'Till startsidan',
  },
  error: { title: 'Något gick fel', text: 'Innehållet kunde inte hämtas just nu.', retry: 'Försök igen' },
  footer: {
    links: [
      { label: 'Om oss', href: '/om-oss' },
      { label: 'Kontakt', href: '/kontakt' },
      { label: 'Integritetspolicy', href: '/integritetspolicy' },
    ],
  },
}

export type Dictionary = typeof sv

const en: Dictionary = {
  switchLanguage: 'Svenska',
  nav: { home: 'Home', businesses: 'Businesses', events: 'Events', articles: 'Stories' },
  site: {
    description: (place: string) => `Businesses, associations, events and stories from ${place}.`,
    skipToContent: 'Skip to content',
  },
  home: {
    promotions: 'Featured',
    articles: 'Stories',
    events: 'Coming up',
    lunch: "Today's lunch",
    noLunch: 'No lunch menu posted for today.',
    noEvents: 'No upcoming events.',
    allArticles: 'All stories',
    allEvents: 'Full calendar',
    allBusinesses: 'All businesses',
  },
  ad: 'Ad',
  offer: 'Offer',
  businesses: {
    title: 'Businesses and associations',
    category: 'Category',
    allCategories: 'All categories',
    search: 'Search',
    searchPlaceholder: 'Name or town',
    filter: 'Show',
    reset: 'Clear filters',
    results: (count: number) => (count === 1 ? '1 result' : `${count} results`),
    none: 'No businesses matched.',
    openNow: 'Open now',
    closedNow: 'Closed now',
  },
  business: {
    openingHours: 'Opening hours this week',
    today: 'Today',
    closed: 'Closed',
    noHours: 'No opening hours listed.',
    exception: 'Special hours',
    lunch: (week: number) => `Lunch, week ${week}`,
    events: 'Coming up here',
    contact: 'Contact',
    phone: 'Phone',
    email: 'Email',
    website: 'Website',
    images: 'Photos',
  },
  events: {
    title: 'Events',
    from: 'From',
    to: 'To',
    show: 'Show',
    reset: 'All upcoming',
    none: 'No events in this period.',
    allDay: 'All day',
    price: 'Price',
    tickets: 'Tickets',
    venue: 'Venue',
    when: 'When',
  },
  articles: { title: 'Stories', by: 'By', none: 'No stories yet.' },
  pagination: {
    previous: 'Previous',
    next: 'Next',
    page: (current: number, last: number) => `Page ${current} of ${last}`,
  },
  notFound: {
    title: 'Page not found',
    text: 'The content may have moved or been removed.',
    home: 'Back to the start page',
  },
  error: { title: 'Something went wrong', text: 'The content could not be loaded right now.', retry: 'Try again' },
  footer: {
    links: [
      { label: 'About', href: '/about-us' },
      { label: 'Contact', href: '/contact' },
      { label: 'Privacy policy', href: '/privacy-policy' },
    ],
  },
}

const dictionaries: Record<Locale, Dictionary> = { sv, en }

export function t(locale: Locale): Dictionary {
  return dictionaries[locale]
}
