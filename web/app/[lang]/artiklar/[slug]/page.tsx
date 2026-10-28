import type { Metadata } from 'next'
import { Picture } from '@/components/picture'
import { Prose } from '@/components/prose'
import { getArticle, segments, slugPaths } from '@/lib/api'
import { formatDate } from '@/lib/datetime'
import { t } from '@/lib/i18n'
import { pageMetadata } from '@/lib/metadata'
import { asLocale } from '@/lib/params'

export async function generateStaticParams() {
  return []
}

export async function generateMetadata({ params }: PageProps<'/[lang]/artiklar/[slug]'>): Promise<Metadata> {
  const { lang, slug } = await params
  const locale = asLocale(lang)
  const article = await getArticle(slug, locale)

  return pageMetadata({
    locale,
    path: `${segments.article}/${article.slug ?? slug}`,
    alternates: slugPaths(segments.article, article.slugs),
    title: article.titel ?? undefined,
    description: article.ingress ?? article.body,
    images: article.images,
    type: 'article',
  })
}

export default async function ArticlePage({ params }: PageProps<'/[lang]/artiklar/[slug]'>) {
  const { lang, slug } = await params
  const locale = asLocale(lang)
  const article = await getArticle(slug, locale)
  const dict = t(locale)
  const image = article.images[0]

  return (
    <article className="mx-auto max-w-3xl">
      <header>
        <h1 className="text-3xl font-semibold tracking-tight sm:text-4xl">{article.titel}</h1>
        {article.ingress && <p className="mt-4 text-xl leading-relaxed text-stone-700">{article.ingress}</p>}
        <p className="mt-4 text-sm text-stone-500">
          {article.author_name && (
            <>
              {dict.articles.by} {article.author_name}
            </>
          )}
          {article.author_name && article.published_at && ' · '}
          {article.published_at && <time dateTime={article.published_at}>{formatDate(article.published_at, locale)}</time>}
        </p>
      </header>

      {image && (
        <figure className="mt-8">
          <Picture media={image} sizes="(min-width: 768px) 768px, 100vw" fit="intrinsic" priority className="rounded-lg" />
          {image.alt && <figcaption className="mt-2 text-sm text-stone-500">{image.alt}</figcaption>}
        </figure>
      )}

      <Prose html={article.body} className="mt-8 text-lg" />
    </article>
  )
}
