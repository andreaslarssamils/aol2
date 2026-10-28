import type { Metadata } from 'next'
import { Badge } from '@/components/badge'
import { EventCard } from '@/components/event-card'
import { LunchMenuWeek } from '@/components/lunch-menu'
import { OpeningHours } from '@/components/opening-hours'
import { Picture } from '@/components/picture'
import { Prose } from '@/components/prose'
import { getBusiness, segments, slugPaths } from '@/lib/api'
import { t } from '@/lib/i18n'
import { pageMetadata } from '@/lib/metadata'
import { asLocale } from '@/lib/params'

export async function generateStaticParams() {
  return []
}

export async function generateMetadata({ params }: PageProps<'/[lang]/foretag/[slug]'>): Promise<Metadata> {
  const { lang, slug } = await params
  const locale = asLocale(lang)
  const location = await getBusiness(slug, locale)

  return pageMetadata({
    locale,
    path: `${segments.business_location}/${location.slug ?? slug}`,
    alternates: slugPaths(segments.business_location, location.slugs),
    title: location.namn,
    description: location.business.beskrivning,
    images: location.images,
  })
}

export default async function BusinessPage({ params }: PageProps<'/[lang]/foretag/[slug]'>) {
  const { lang, slug } = await params
  const locale = asLocale(lang)
  const location = await getBusiness(slug, locale)
  const dict = t(locale)
  const [hero, ...gallery] = location.images
  const addressLines = [location.adress, [location.postnummer, location.ort].filter(Boolean).join(' ')].filter(Boolean)
  const website = location.business.webbplats

  return (
    <article>
      <header>
        <div className="flex flex-wrap items-center gap-2">
          {location.categories.map((category) => (
            <Badge key={category.slug}>{category.namn}</Badge>
          ))}
          {location.open_now && <Badge tone="open">{dict.businesses.openNow}</Badge>}
        </div>
        <h1 className="mt-3 text-3xl font-semibold tracking-tight sm:text-4xl">{location.namn}</h1>
        {location.namn !== location.business.namn && <p className="mt-1 text-stone-600">{location.business.namn}</p>}
      </header>

      {hero && <Picture media={hero} sizes="(min-width: 1024px) 1024px, 100vw" fit="intrinsic" priority className="mt-6 rounded-lg" />}

      <div className="mt-8 grid gap-10 lg:grid-cols-3">
        <div className="space-y-10 lg:col-span-2">
          <Prose html={location.business.beskrivning} />

          {location.lunch_menu && (
            <section aria-labelledby="lunch">
              <h2 id="lunch" className="mb-3 text-xl font-semibold tracking-tight">
                {dict.business.lunch(location.lunch_menu.week)}
              </h2>
              <LunchMenuWeek menu={location.lunch_menu} locale={locale} />
            </section>
          )}

          {location.upcoming_events.length > 0 && (
            <section aria-labelledby="events">
              <h2 id="events" className="mb-3 text-xl font-semibold tracking-tight">
                {dict.business.events}
              </h2>
              <div className="grid gap-3">
                {location.upcoming_events.map((event) => (
                  <EventCard key={event.id} event={event} locale={locale} />
                ))}
              </div>
            </section>
          )}

          {gallery.length > 0 && (
            <section aria-labelledby="images">
              <h2 id="images" className="mb-3 text-xl font-semibold tracking-tight">
                {dict.business.images}
              </h2>
              <div className="grid grid-cols-2 gap-3 sm:grid-cols-3">
                {gallery.map((image) => (
                  <Picture key={image.url} media={image} sizes="(min-width: 640px) 33vw, 50vw" className="rounded-md" />
                ))}
              </div>
            </section>
          )}
        </div>

        <aside className="space-y-8">
          <section aria-labelledby="hours">
            <h2 id="hours" className="mb-3 text-lg font-semibold tracking-tight">
              {dict.business.openingHours}
            </h2>
            <OpeningHours days={location.opening_hours} locale={locale} />
          </section>

          <section aria-labelledby="contact">
            <h2 id="contact" className="mb-3 text-lg font-semibold tracking-tight">
              {dict.business.contact}
            </h2>
            <dl className="space-y-2 text-sm">
              {addressLines.length > 0 && (
                <div>
                  <dt className="sr-only">{dict.business.contact}</dt>
                  <dd>
                    {addressLines.map((line) => (
                      <div key={line}>{line}</div>
                    ))}
                  </dd>
                </div>
              )}
              {location.telefon && (
                <div className="flex gap-2">
                  <dt className="w-20 shrink-0 text-stone-500">{dict.business.phone}</dt>
                  <dd>
                    <a href={`tel:${location.telefon.replace(/[^\d+]/g, '')}`} className="hover:text-brand">
                      {location.telefon}
                    </a>
                  </dd>
                </div>
              )}
              {location.business.epost && (
                <div className="flex gap-2">
                  <dt className="w-20 shrink-0 text-stone-500">{dict.business.email}</dt>
                  <dd>
                    <a href={`mailto:${location.business.epost}`} className="break-all hover:text-brand">
                      {location.business.epost}
                    </a>
                  </dd>
                </div>
              )}
              {website && (
                <div className="flex gap-2">
                  <dt className="w-20 shrink-0 text-stone-500">{dict.business.website}</dt>
                  <dd>
                    <a href={website} rel="noopener" className="break-all hover:text-brand">
                      {website.replace(/^https?:\/\//, '').replace(/\/$/, '')}
                    </a>
                  </dd>
                </div>
              )}
            </dl>
          </section>
        </aside>
      </div>
    </article>
  )
}
