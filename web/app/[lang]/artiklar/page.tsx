import type { Metadata } from 'next'
import { ArticleCard } from '@/components/article-card'
import { Pagination } from '@/components/pagination'
import { listArticles, segments } from '@/lib/api'
import { localizedPath, t } from '@/lib/i18n'
import { pageMetadata } from '@/lib/metadata'
import { asLocale, pageParam } from '@/lib/params'

export async function generateMetadata({ params }: PageProps<'/[lang]/artiklar'>): Promise<Metadata> {
  const locale = asLocale((await params).lang)

  return pageMetadata({ locale, path: segments.article, title: t(locale).articles.title })
}

export default async function ArticlesPage({ params, searchParams }: PageProps<'/[lang]/artiklar'>) {
  const locale = asLocale((await params).lang)
  const page = pageParam(await searchParams)
  const dict = t(locale)
  const result = await listArticles(locale, { page })

  return (
    <>
      <h1 className="text-3xl font-semibold tracking-tight">{dict.articles.title}</h1>

      {result.data.length > 0 ? (
        <div className="mt-6 grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
          {result.data.map((article, index) => (
            <ArticleCard key={article.id} article={article} locale={locale} priority={index < 3} />
          ))}
        </div>
      ) : (
        <p className="mt-6 text-stone-600">{dict.articles.none}</p>
      )}

      <Pagination meta={result.meta} locale={locale} path={localizedPath(locale, segments.article)} query={{}} />
    </>
  )
}
