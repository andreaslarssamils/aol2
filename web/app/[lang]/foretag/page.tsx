import type { Metadata } from 'next'
import Link from 'next/link'
import { BusinessCard } from '@/components/business-card'
import { Pagination } from '@/components/pagination'
import { listBusinesses, listCategories, segments } from '@/lib/api'
import { localizedPath, t } from '@/lib/i18n'
import { pageMetadata } from '@/lib/metadata'
import { asLocale, pageParam, param } from '@/lib/params'

export async function generateMetadata({ params }: PageProps<'/[lang]/foretag'>): Promise<Metadata> {
  const locale = asLocale((await params).lang)

  return pageMetadata({ locale, path: segments.business_location, title: t(locale).businesses.title })
}

export default async function BusinessesPage({ params, searchParams }: PageProps<'/[lang]/foretag'>) {
  const locale = asLocale((await params).lang)
  const query = await searchParams
  const category = param(query, 'category')
  const q = param(query, 'q')
  const page = pageParam(query)
  const dict = t(locale)
  const path = localizedPath(locale, segments.business_location)

  const [result, categories] = await Promise.all([
    listBusinesses(locale, { category, q, page }),
    listCategories(locale, 'business'),
  ])
  const filtered = Boolean(category || q)

  return (
    <>
      <h1 className="text-3xl font-semibold tracking-tight">{dict.businesses.title}</h1>

      <form method="get" action={path} className="mt-6 grid gap-3 sm:grid-cols-[1fr_1fr_auto] sm:items-end">
        <label className="grid gap-1 text-sm">
          <span className="font-medium">{dict.businesses.category}</span>
          <select name="category" defaultValue={category ?? ''} className="rounded-md border border-stone-300 bg-white px-3 py-2">
            <option value="">{dict.businesses.allCategories}</option>
            {categories.map((item) => (
              <option key={item.slug} value={item.slug}>
                {item.namn}
              </option>
            ))}
          </select>
        </label>
        <label className="grid gap-1 text-sm">
          <span className="font-medium">{dict.businesses.search}</span>
          <input
            type="search"
            name="q"
            defaultValue={q ?? ''}
            placeholder={dict.businesses.searchPlaceholder}
            className="rounded-md border border-stone-300 px-3 py-2"
          />
        </label>
        <div className="flex items-center gap-3">
          <button type="submit" className="rounded-md bg-brand px-4 py-2 text-sm font-medium text-white hover:bg-brand-dark">
            {dict.businesses.filter}
          </button>
          {filtered && (
            <Link href={path} className="text-sm text-stone-600 hover:text-brand">
              {dict.businesses.reset}
            </Link>
          )}
        </div>
      </form>

      <p className="mt-6 text-sm text-stone-600" role="status">
        {dict.businesses.results(result.meta.total)}
      </p>

      {result.data.length > 0 ? (
        <div className="mt-3 grid gap-3">
          {result.data.map((location) => (
            <BusinessCard key={location.id} location={location} locale={locale} />
          ))}
        </div>
      ) : (
        <p className="mt-3 text-stone-600">{dict.businesses.none}</p>
      )}

      <Pagination meta={result.meta} locale={locale} path={path} query={{ category, q }} />
    </>
  )
}
