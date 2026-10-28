import Link from 'next/link'
import { todayKey } from '@/lib/datetime'
import { type Locale, localizedPath, t } from '@/lib/i18n'

export function SiteFooter({ locale, siteName }: { locale: Locale; siteName: string }) {
  const dict = t(locale)

  return (
    <footer className="mt-12 border-t border-stone-200">
      <div className="mx-auto flex max-w-5xl flex-wrap items-center justify-between gap-4 px-4 py-6 text-sm text-stone-600 sm:px-6">
        <p>
          © {todayKey().slice(0, 4)} {siteName}
        </p>
        <nav className="flex flex-wrap gap-4">
          {dict.footer.links.map((link) => (
            <Link key={link.href} href={localizedPath(locale, link.href)} className="hover:text-brand">
              {link.label}
            </Link>
          ))}
        </nav>
      </div>
    </footer>
  )
}
