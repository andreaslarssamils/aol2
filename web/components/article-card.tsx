import Link from 'next/link'
import { type Article, segments } from '@/lib/api'
import { formatDate } from '@/lib/datetime'
import { type Locale, localizedPath } from '@/lib/i18n'
import { Picture } from './picture'

export function ArticleCard({ article, locale, priority = false }: { article: Article; locale: Locale; priority?: boolean }) {
  const href = article.slug ? localizedPath(locale, `${segments.article}/${article.slug}`) : null
  const image = article.images[0]
  const byline = [article.author_name, article.published_at ? formatDate(article.published_at, locale) : null]
    .filter(Boolean)
    .join(' · ')

  return (
    <article className="flex flex-col">
      {image && (
        <Picture media={image} sizes="(min-width: 640px) 33vw, 100vw" priority={priority} className="rounded-lg" />
      )}
      <h3 className="mt-3 text-lg font-semibold leading-snug">
        {href ? (
          <Link href={href} className="hover:text-brand">
            {article.titel}
          </Link>
        ) : (
          article.titel
        )}
      </h3>
      {article.ingress && <p className="mt-1 line-clamp-3 text-sm text-stone-700">{article.ingress}</p>}
      {byline && <p className="mt-2 text-xs text-stone-500">{byline}</p>}
    </article>
  )
}
