import Link from 'next/link'
import type { PaginationMeta } from '@/lib/api'
import { type Locale, t } from '@/lib/i18n'
import { withQuery } from '@/lib/params'

type Props = {
  meta: PaginationMeta
  locale: Locale
  /** Listans sökväg med språkprefix. */
  path: string
  /** Aktiva filter som ska följa med. */
  query: Record<string, string | undefined>
}

export function Pagination({ meta, locale, path, query }: Props) {
  if (meta.last_page <= 1) {
    return null
  }

  const dict = t(locale)
  const href = (page: number) => withQuery(path, { ...query, page: page > 1 ? page : undefined })
  const linkClass = 'rounded-md border border-stone-300 px-3 py-1.5 hover:border-stone-500'

  return (
    <nav aria-label={dict.pagination.page(meta.current_page, meta.last_page)} className="mt-8 flex items-center justify-between text-sm">
      {meta.current_page > 1 ? (
        <Link rel="prev" href={href(meta.current_page - 1)} className={linkClass}>
          ← {dict.pagination.previous}
        </Link>
      ) : (
        <span />
      )}
      <span className="text-stone-600">{dict.pagination.page(meta.current_page, meta.last_page)}</span>
      {meta.current_page < meta.last_page ? (
        <Link rel="next" href={href(meta.current_page + 1)} className={linkClass}>
          {dict.pagination.next} →
        </Link>
      ) : (
        <span />
      )}
    </nav>
  )
}
