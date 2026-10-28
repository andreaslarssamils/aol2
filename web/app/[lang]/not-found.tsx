import Link from 'next/link'
import { lang } from 'next/root-params'
import { type Locale, defaultLocale, isLocale, localizedPath, t } from '@/lib/i18n'

export default async function NotFound() {
  const value = await lang()
  const locale: Locale = isLocale(value) ? value : defaultLocale
  const dict = t(locale)

  return (
    <section className="py-16 text-center">
      <h1 className="text-3xl font-semibold tracking-tight">{dict.notFound.title}</h1>
      <p className="mt-3 text-stone-600">{dict.notFound.text}</p>
      <Link href={localizedPath(locale, '/')} className="mt-6 inline-block text-brand hover:underline">
        {dict.notFound.home}
      </Link>
    </section>
  )
}
