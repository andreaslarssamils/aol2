import type { Metadata } from 'next'
import { Geist } from 'next/font/google'
import { SiteFooter } from '@/components/site-footer'
import { SiteHeader } from '@/components/site-header'
import { getSite } from '@/lib/api'
import { ogLocale, t } from '@/lib/i18n'
import { asLocale } from '@/lib/params'
import '../globals.css'

const geist = Geist({ subsets: ['latin'], variable: '--font-geist-sans' })

export async function generateMetadata({ params }: LayoutProps<'/[lang]'>): Promise<Metadata> {
  const locale = asLocale((await params).lang)
  const site = await getSite(locale)

  return {
    metadataBase: new URL(process.env.SITE_URL ?? 'http://localhost:3000'),
    title: { default: site.namn, template: `%s – ${site.namn}` },
    description: t(locale).site.description(site.primary_kommun?.namn ?? site.namn),
    openGraph: { siteName: site.namn, locale: ogLocale[locale], type: 'website' },
  }
}

export default async function RootLayout({ children, params }: LayoutProps<'/[lang]'>) {
  const locale = asLocale((await params).lang)
  const site = await getSite(locale)

  return (
    <html lang={locale} className={`${geist.variable} h-full antialiased`}>
      <body className="flex min-h-full flex-col bg-white text-stone-900">
        <SiteHeader locale={locale} siteName={site.namn} />
        <main id="content" className="mx-auto w-full max-w-5xl flex-1 px-4 py-6 sm:px-6 sm:py-8">
          {children}
        </main>
        <SiteFooter locale={locale} siteName={site.namn} />
      </body>
    </html>
  )
}
