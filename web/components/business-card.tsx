import Link from 'next/link'
import { type BusinessLocation, segments } from '@/lib/api'
import { type Locale, localizedPath, t } from '@/lib/i18n'
import { Badge } from './badge'
import { Picture } from './picture'

export function BusinessCard({ location, locale }: { location: BusinessLocation; locale: Locale }) {
  const dict = t(locale)
  const href = location.slug ? localizedPath(locale, `${segments.business_location}/${location.slug}`) : null
  const address = [location.adress, location.ort].filter(Boolean).join(', ')
  const image = location.images[0]

  return (
    <article className="flex gap-4 rounded-lg border border-stone-200 p-4">
      {image && (
        <div className="hidden w-24 shrink-0 sm:block">
          <Picture media={image} sizes="96px" className="rounded-md" />
        </div>
      )}
      <div className="min-w-0 flex-1">
        <div className="flex flex-wrap items-center gap-2">
          <h3 className="font-semibold leading-snug">
            {href ? (
              <Link href={href} className="hover:text-brand">
                {location.namn}
              </Link>
            ) : (
              location.namn
            )}
          </h3>
          {location.open_now && <Badge tone="open">{dict.businesses.openNow}</Badge>}
        </div>
        {address && <p className="mt-1 text-sm text-stone-600">{address}</p>}
        {location.categories.length > 0 && (
          <div className="mt-2 flex flex-wrap gap-1">
            {location.categories.map((category) => (
              <Badge key={category.slug}>{category.namn}</Badge>
            ))}
          </div>
        )}
      </div>
    </article>
  )
}
