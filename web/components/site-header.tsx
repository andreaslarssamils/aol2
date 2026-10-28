import Link from 'next/link'
import { type Locale, localizedPath, t } from '@/lib/i18n'
import { LocaleSwitch } from './locale-switch'

export function SiteHeader({ locale, siteName }: { locale: Locale; siteName: string }) {
  const dict = t(locale)
  const items = [
    { href: '/foretag', label: dict.nav.businesses },
    { href: '/evenemang', label: dict.nav.events },
    { href: '/artiklar', label: dict.nav.articles },
  ]

  return (
    <header className="border-b border-stone-200">
      <a
        href="#content"
        className="sr-only focus:not-sr-only focus:absolute focus:left-2 focus:top-2 focus:rounded focus:bg-white focus:px-3 focus:py-2 focus:shadow"
      >
        {dict.site.skipToContent}
      </a>
      <div className="mx-auto flex max-w-5xl flex-wrap items-center gap-x-6 gap-y-2 px-4 py-3 sm:px-6">
        <Link href={localizedPath(locale, '/')} className="text-lg font-semibold tracking-tight">
          {siteName}
        </Link>
        <nav aria-label={dict.nav.home} className="order-last flex w-full gap-4 text-sm sm:order-none sm:w-auto sm:flex-1">
          {items.map((item) => (
            <Link key={item.href} href={localizedPath(locale, item.href)} className="hover:text-brand">
              {item.label}
            </Link>
          ))}
        </nav>
        <LocaleSwitch locale={locale} />
      </div>
    </header>
  )
}
