import type { Metadata } from 'next'
import Link from 'next/link'
import { EventCard } from '@/components/event-card'
import { Pagination } from '@/components/pagination'
import { type Event, listEvents, segments } from '@/lib/api'
import { dateKey, formatDay, todayKey } from '@/lib/datetime'
import { capitalize, localizedPath, t } from '@/lib/i18n'
import { pageMetadata } from '@/lib/metadata'
import { asLocale, dateParam, pageParam, param } from '@/lib/params'

export async function generateMetadata({ params }: PageProps<'/[lang]/evenemang'>): Promise<Metadata> {
  const locale = asLocale((await params).lang)

  return pageMetadata({ locale, path: segments.event, title: t(locale).events.title })
}

/** Evenemang per kalenderdag i Europe/Stockholm, i listans ordning. */
function groupByDay(events: Event[]): [string, Event[]][] {
  const groups = new Map<string, Event[]>()

  for (const event of events) {
    const day = dateKey(event.starts_at)
    groups.set(day, [...(groups.get(day) ?? []), event])
  }

  return [...groups.entries()]
}

export default async function EventsPage({ params, searchParams }: PageProps<'/[lang]/evenemang'>) {
  const locale = asLocale((await params).lang)
  const query = await searchParams
  const from = dateParam(query, 'from')
  const to = dateParam(query, 'to')
  const category = param(query, 'category')
  const page = pageParam(query)
  const dict = t(locale)
  const path = localizedPath(locale, segments.event)
  const result = await listEvents(locale, { from, to, category, page })
  const inputClass = 'rounded-md border border-stone-300 px-3 py-2'

  return (
    <>
      <h1 className="text-3xl font-semibold tracking-tight">{dict.events.title}</h1>

      <form method="get" action={path} className="mt-6 flex flex-wrap items-end gap-3">
        <label className="grid gap-1 text-sm">
          <span className="font-medium">{dict.events.from}</span>
          <input type="date" name="from" defaultValue={from ?? todayKey()} className={inputClass} />
        </label>
        <label className="grid gap-1 text-sm">
          <span className="font-medium">{dict.events.to}</span>
          <input type="date" name="to" defaultValue={to ?? ''} min={from} className={inputClass} />
        </label>
        {category && <input type="hidden" name="category" value={category} />}
        <button type="submit" className="rounded-md bg-brand px-4 py-2 text-sm font-medium text-white hover:bg-brand-dark">
          {dict.events.show}
        </button>
        {(from || to) && (
          <Link href={path} className="text-sm text-stone-600 hover:text-brand">
            {dict.events.reset}
          </Link>
        )}
      </form>

      {result.data.length === 0 ? (
        <p className="mt-8 text-stone-600">{dict.events.none}</p>
      ) : (
        groupByDay(result.data).map(([day, events]) => (
          <section key={day} aria-labelledby={`day-${day}`} className="mt-8">
            <h2 id={`day-${day}`} className="mb-3 text-lg font-semibold tracking-tight">
              <time dateTime={day}>{capitalize(formatDay(day, locale))}</time>
            </h2>
            <div className="grid gap-3">
              {events.map((event) => (
                <EventCard key={event.id} event={event} locale={locale} />
              ))}
            </div>
          </section>
        ))
      )}

      <Pagination meta={result.meta} locale={locale} path={path} query={{ from, to, category }} />
    </>
  )
}
