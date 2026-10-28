import type { Metadata } from 'next'
import Link from 'next/link'
import { ArticleCard } from '@/components/article-card'
import { EventCard } from '@/components/event-card'
import { PromotionCard } from '@/components/promotion-card'
import { SectionHeading } from '@/components/section-heading'
import { getHome, getSite, segments } from '@/lib/api'
import { localizedPath, t } from '@/lib/i18n'
import { pageMetadata } from '@/lib/metadata'
import { asLocale } from '@/lib/params'

// ISR: ingen förrendering vid bygge, sidan byggs vid första anropet och revalideras via fetch (60 s).
export async function generateStaticParams() {
  return []
}

export async function generateMetadata({ params }: PageProps<'/[lang]'>): Promise<Metadata> {
  const locale = asLocale((await params).lang)
  const site = await getSite(locale)

  return pageMetadata({
    locale,
    path: '/',
    description: t(locale).site.description(site.primary_kommun?.namn ?? site.namn),
  })
}

export default async function HomePage({ params }: PageProps<'/[lang]'>) {
  const locale = asLocale((await params).lang)
  const [site, home] = await Promise.all([getSite(locale), getHome(locale)])
  const dict = t(locale)

  return (
    <>
      <h1 className="sr-only">{site.namn}</h1>

      {home.promotions.length > 0 && (
        <section aria-labelledby="promotions">
          <SectionHeading id="promotions" title={dict.home.promotions} />
          <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            {home.promotions.map((promotion) => (
              <PromotionCard key={promotion.id} promotion={promotion} locale={locale} />
            ))}
          </div>
        </section>
      )}

      {home.featured_articles.length > 0 && (
        <section aria-labelledby="articles" className="mt-10">
          <SectionHeading
            id="articles"
            title={dict.home.articles}
            href={localizedPath(locale, segments.article)}
            linkLabel={dict.home.allArticles}
          />
          <div className="grid gap-6 sm:grid-cols-3">
            {home.featured_articles.map((article, index) => (
              <ArticleCard key={article.id} article={article} locale={locale} priority={index === 0} />
            ))}
          </div>
        </section>
      )}

      <div className="mt-10 grid gap-10 lg:grid-cols-3">
        <section aria-labelledby="events" className="lg:col-span-2">
          <SectionHeading
            id="events"
            title={dict.home.events}
            href={localizedPath(locale, segments.event)}
            linkLabel={dict.home.allEvents}
          />
          {home.upcoming_events.length > 0 ? (
            <div className="grid gap-3">
              {home.upcoming_events.map((event) => (
                <EventCard key={event.id} event={event} locale={locale} />
              ))}
            </div>
          ) : (
            <p className="text-sm text-stone-600">{dict.home.noEvents}</p>
          )}
        </section>

        <section aria-labelledby="lunch">
          <SectionHeading
            id="lunch"
            title={dict.home.lunch}
            href={localizedPath(locale, `${segments.business_location}?category=restaurang-cafe`)}
            linkLabel={dict.home.allBusinesses}
          />
          {home.lunch_menus.length > 0 ? (
            <ul className="divide-y divide-stone-100">
              {home.lunch_menus.map((menu) => (
                <li key={menu.location?.slug ?? menu.location?.namn} className="py-3">
                  <h3 className="font-medium">
                    {menu.location?.slug ? (
                      <Link
                        href={localizedPath(locale, `${segments.business_location}/${menu.location.slug}`)}
                        className="hover:text-brand"
                      >
                        {menu.location.namn}
                      </Link>
                    ) : (
                      menu.location?.namn
                    )}
                  </h3>
                  <p className="mt-0.5 text-sm text-stone-700">{menu.today.join(' · ')}</p>
                </li>
              ))}
            </ul>
          ) : (
            <p className="text-sm text-stone-600">{dict.home.noLunch}</p>
          )}
        </section>
      </div>
    </>
  )
}
