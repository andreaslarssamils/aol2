import type { Metadata } from 'next'
import Link from 'next/link'
import { Badge } from '@/components/badge'
import { Picture } from '@/components/picture'
import { Prose } from '@/components/prose'
import { getEvent, segments, slugPaths } from '@/lib/api'
import { formatEventWhen } from '@/lib/datetime'
import { localizedPath, t } from '@/lib/i18n'
import { pageMetadata } from '@/lib/metadata'
import { asLocale } from '@/lib/params'

export async function generateStaticParams() {
  return []
}

export async function generateMetadata({ params }: PageProps<'/[lang]/evenemang/[slug]'>): Promise<Metadata> {
  const { lang, slug } = await params
  const locale = asLocale(lang)
  const event = await getEvent(slug, locale)

  return pageMetadata({
    locale,
    path: `${segments.event}/${event.slug ?? slug}`,
    alternates: slugPaths(segments.event, event.slugs),
    title: event.namn,
    description: event.beskrivning,
    images: event.images,
  })
}

export default async function EventPage({ params }: PageProps<'/[lang]/evenemang/[slug]'>) {
  const { lang, slug } = await params
  const locale = asLocale(lang)
  const event = await getEvent(slug, locale)
  const dict = t(locale)
  const when = formatEventWhen(event, locale, dict.events.allDay)
  const image = event.images[0]
  const locationHref = event.location?.slug
    ? localizedPath(locale, `${segments.business_location}/${event.location.slug}`)
    : null

  return (
    <article>
      <header>
        {event.categories.length > 0 && (
          <div className="flex flex-wrap gap-2">
            {event.categories.map((category) => (
              <Badge key={category.slug}>{category.namn}</Badge>
            ))}
          </div>
        )}
        <h1 className="mt-3 text-3xl font-semibold tracking-tight sm:text-4xl">{event.namn}</h1>
        <p className="mt-2 text-lg text-stone-700">
          <time dateTime={event.starts_at}>{when}</time>
        </p>
      </header>

      {image && <Picture media={image} sizes="(min-width: 1024px) 1024px, 100vw" fit="intrinsic" priority className="mt-6 rounded-lg" />}

      <div className="mt-8 grid gap-10 lg:grid-cols-3">
        <Prose html={event.beskrivning} className="lg:col-span-2" />

        <aside>
          <dl className="space-y-4 text-sm">
            <div>
              <dt className="font-medium text-stone-500">{dict.events.when}</dt>
              <dd className="mt-0.5">{when}</dd>
            </div>
            {(event.location?.namn || event.venue_name || event.adress) && (
              <div>
                <dt className="font-medium text-stone-500">{dict.events.venue}</dt>
                <dd className="mt-0.5">
                  {locationHref ? (
                    <Link href={locationHref} className="text-brand hover:underline">
                      {event.location?.namn}
                    </Link>
                  ) : (
                    event.location?.namn ?? event.venue_name
                  )}
                  {event.adress && <div className="text-stone-600">{event.adress}</div>}
                </dd>
              </div>
            )}
            {event.pris && (
              <div>
                <dt className="font-medium text-stone-500">{dict.events.price}</dt>
                <dd className="mt-0.5">{event.pris}</dd>
              </div>
            )}
            {event.ticket_url && (
              <div>
                <dt className="sr-only">{dict.events.tickets}</dt>
                <dd>
                  <a
                    href={event.ticket_url}
                    rel="noopener"
                    className="inline-block rounded-md bg-brand px-4 py-2 font-medium text-white hover:bg-brand-dark"
                  >
                    {dict.events.tickets}
                  </a>
                </dd>
              </div>
            )}
          </dl>
        </aside>
      </div>
    </article>
  )
}
