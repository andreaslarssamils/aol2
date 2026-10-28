import { type Locale, intlLocale } from './i18n'

/**
 * All visning sker i Europe/Stockholm, oavsett serverns tidszon. API:et levererar
 * tidpunkter som ISO 8601 med offset; rena datum (YYYY-MM-DD) är kalenderdagar.
 */
export const TIME_ZONE = 'Europe/Stockholm'

const cache = new Map<string, Intl.DateTimeFormat>()

function formatter(locale: Locale, options: Intl.DateTimeFormatOptions, timeZone: string = TIME_ZONE): Intl.DateTimeFormat {
  const key = `${locale}|${timeZone}|${JSON.stringify(options)}`
  let instance = cache.get(key)

  if (!instance) {
    instance = new Intl.DateTimeFormat(intlLocale[locale], { timeZone, ...options })
    cache.set(key, instance)
  }

  return instance
}

/** Kalenderdagen (YYYY-MM-DD) för en tidpunkt, i Europe/Stockholm. */
export function dateKey(value: string | Date): string {
  const parts = new Intl.DateTimeFormat('en-CA', {
    timeZone: TIME_ZONE,
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
  }).formatToParts(new Date(value))
  const part = (type: Intl.DateTimeFormatPartTypes) => parts.find((p) => p.type === type)?.value ?? ''

  return `${part('year')}-${part('month')}-${part('day')}`
}

export function todayKey(now: Date = new Date()): string {
  return dateKey(now)
}

export type DayKey = 'mon' | 'tue' | 'wed' | 'thu' | 'fri' | 'sat' | 'sun'

export const dayKeys: readonly DayKey[] = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun']

/** Lunchmenyns nyckel för dagen i Europe/Stockholm. */
export function lunchDayKey(now: Date = new Date()): DayKey {
  return new Intl.DateTimeFormat('en-US', { timeZone: TIME_ZONE, weekday: 'short' })
    .format(now)
    .toLowerCase() as DayKey
}

/** Veckodagens namn för index 0 (måndag) … 6 (söndag). */
export function weekdayName(index: number, locale: Locale): string {
  // 2024-01-01 är en måndag.
  return formatDay(`2024-01-0${index + 1}`, locale, { weekday: 'long' })
}

/** Ett rent datum (YYYY-MM-DD) – formateras i UTC så att kalenderdagen inte skiftar. */
export function formatDay(
  date: string,
  locale: Locale,
  options: Intl.DateTimeFormatOptions = { weekday: 'long', day: 'numeric', month: 'long' },
): string {
  return formatter(locale, options, 'UTC').format(new Date(`${date}T00:00:00Z`))
}

export function formatDate(
  value: string,
  locale: Locale,
  options: Intl.DateTimeFormatOptions = { day: 'numeric', month: 'long', year: 'numeric' },
): string {
  return formatter(locale, options).format(new Date(value))
}

export function formatTime(value: string, locale: Locale): string {
  return formatter(locale, { hour: '2-digit', minute: '2-digit', hourCycle: 'h23' }).format(new Date(value))
}

const dayAndMonth: Intl.DateTimeFormatOptions = { weekday: 'short', day: 'numeric', month: 'short' }

/** "tis 3 nov 19:00–21:00", "lör 21 nov" (heldag) eller "fre 6 nov 18:00 – lör 7 nov 02:00". */
export function formatEventWhen(
  event: { starts_at: string; ends_at: string | null; all_day: boolean },
  locale: Locale,
  allDayLabel?: string,
): string {
  const sameDay = event.ends_at === null || dateKey(event.starts_at) === dateKey(event.ends_at)
  const startDay = formatDate(event.starts_at, locale, dayAndMonth)

  if (event.all_day) {
    const days = sameDay ? startDay : `${startDay} – ${formatDate(event.ends_at as string, locale, dayAndMonth)}`

    return allDayLabel ? `${days}, ${allDayLabel}` : days
  }

  const start = `${startDay} ${formatTime(event.starts_at, locale)}`

  if (event.ends_at === null) {
    return start
  }

  if (sameDay) {
    return `${start}–${formatTime(event.ends_at, locale)}`
  }

  return `${start} – ${formatDate(event.ends_at, locale, dayAndMonth)} ${formatTime(event.ends_at, locale)}`
}
