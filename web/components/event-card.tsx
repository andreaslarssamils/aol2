import Link from 'next/link'
import { type Event, segments } from '@/lib/api'
import { formatEventWhen } from '@/lib/datetime'
import { type Locale, localizedPath, t } from '@/lib/i18n'
import { Badge } from './badge'
import { Picture } from './picture'

export function EventCard({ event, locale }: { event: Event; locale: Locale }) {
  const dict = t(locale)
  const href = event.slug ? localizedPath(locale, `${segments.event}/${event.slug}`) : null
  const where = event.location?.namn ?? event.venue_name
  const image = event.images[0]

  return (
    <article className="flex gap-4 rounded-lg border border-stone-200 p-4">
      {image && (
        <div className="hidden w-28 shrink-0 sm:block">
          <Picture media={image} sizes="112px" className="rounded-md" />
        </div>
      )}
      <div className="min-w-0">
        <p className="text-sm text-stone-600">
          <time dateTime={event.starts_at}>{formatEventWhen(event, locale, dict.events.allDay)}</time>
        </p>
        <h3 className="mt-1 font-semibold leading-snug">
          {href ? (
            <Link href={href} className="hover:text-brand">
              {event.namn}
            </Link>
          ) : (
            event.namn
          )}
        </h3>
        {where && <p className="mt-1 text-sm text-stone-600">{where}</p>}
        {event.categories.length > 0 && (
          <div className="mt-2 flex flex-wrap gap-1">
            {event.categories.map((category) => (
              <Badge key={category.slug}>{category.namn}</Badge>
            ))}
          </div>
        )}
      </div>
    </article>
  )
}
