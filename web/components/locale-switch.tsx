'use client'

import Link from 'next/link'
import { usePathname } from 'next/navigation'
import { type Locale, switchLocalePath, t } from '@/lib/i18n'

/** Samma sida på det andra språket. Skiljer sig sluggen svarar API:et 301 och sidan 308 till den kanoniska. */
export function LocaleSwitch({ locale }: { locale: Locale }) {
  const pathname = usePathname() ?? '/'
  const other: Locale = locale === 'sv' ? 'en' : 'sv'

  return (
    <Link
      href={switchLocalePath(pathname, other)}
      hrefLang={other}
      lang={other}
      className="text-sm text-stone-600 hover:text-brand"
    >
      {t(locale).switchLanguage}
    </Link>
  )
}
