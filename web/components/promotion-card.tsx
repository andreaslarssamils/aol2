import Link from 'next/link'
import {
  type Article,
  type BusinessLocation,
  type Event,
  type Media,
  type Offer,
  type Promotion,
  promotionHref,
} from '@/lib/api'
import { formatEventWhen } from '@/lib/datetime'
import { type Locale, t } from '@/lib/i18n'
import { excerpt } from '@/lib/metadata'
import { Badge } from './badge'
import { Picture } from './picture'

type Summary = { title: string; text?: string | null; meta?: string | null; image?: Media }

function summarize(promotion: Promotion, locale: Locale): Summary {
  const dict = t(locale)

  switch (promotion.type) {
    case 'event': {
      const event = promotion.content as Event

      return {
        title: event.namn,
        meta: formatEventWhen(event, locale, dict.events.allDay),
        text: event.location?.namn ?? event.venue_name,
        image: event.images[0],
      }
    }
    case 'article': {
      const article = promotion.content as Article

      return { title: article.titel ?? '', text: article.ingress, image: article.images[0] }
    }
    case 'business_location': {
      const location = promotion.content as BusinessLocation

      return {
        title: location.namn,
        meta: location.categories.map((category) => category.namn).join(' · '),
        text: [location.adress, location.ort].filter(Boolean).join(', '),
        image: location.images[0],
      }
    }
    case 'offer': {
      const offer = promotion.content as Offer

      return {
        title: offer.titel ?? offer.business.namn,
        meta: `${dict.offer} · ${offer.business.namn}`,
        text: offer.beskrivning,
      }
    }
  }
}

/** En placering på startsidan. is_paid ger etiketten "Annons". */
export function PromotionCard({ promotion, locale }: { promotion: Promotion; locale: Locale }) {
  const dict = t(locale)
  const { title, text, meta, image } = summarize(promotion, locale)
  const href = promotionHref(promotion.link, locale)
  const external = href?.startsWith('http') ?? false

  return (
    <article className="flex flex-col overflow-hidden rounded-lg border border-stone-200">
      {image && <Picture media={image} sizes="(min-width: 1024px) 25vw, (min-width: 640px) 50vw, 100vw" />}
      <div className="flex flex-1 flex-col p-4">
        <div className="flex flex-wrap items-center gap-2 text-xs text-stone-500">
          {promotion.is_paid && <Badge tone="ad">{dict.ad}</Badge>}
          {meta && <span>{meta}</span>}
        </div>
        <h3 className="mt-2 font-semibold leading-snug">
          {href && external ? (
            <a href={href} className="hover:text-brand" rel="noopener">
              {title}
            </a>
          ) : href ? (
            <Link href={href} className="hover:text-brand">
              {title}
            </Link>
          ) : (
            title
          )}
        </h3>
        {text && <p className="mt-1 line-clamp-3 text-sm text-stone-700">{excerpt(text)}</p>}
      </div>
    </article>
  )
}
