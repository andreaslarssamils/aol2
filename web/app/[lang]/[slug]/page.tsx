import type { Metadata } from 'next'
import { Picture } from '@/components/picture'
import { Prose } from '@/components/prose'
import { getPage, segments, slugPaths } from '@/lib/api'
import { pageMetadata } from '@/lib/metadata'
import { asLocale } from '@/lib/params'

export async function generateStaticParams() {
  return []
}

export async function generateMetadata({ params }: PageProps<'/[lang]/[slug]'>): Promise<Metadata> {
  const { lang, slug } = await params
  const locale = asLocale(lang)
  const page = await getPage(slug, locale)

  return pageMetadata({
    locale,
    path: `${segments.page}/${page.slug ?? slug}`,
    alternates: slugPaths(segments.page, page.slugs),
    title: page.titel ?? undefined,
    description: page.body,
    images: page.images,
  })
}

export default async function ContentPage({ params }: PageProps<'/[lang]/[slug]'>) {
  const { lang, slug } = await params
  const locale = asLocale(lang)
  const page = await getPage(slug, locale)
  const image = page.images[0]

  return (
    <article className="mx-auto max-w-3xl">
      <h1 className="text-3xl font-semibold tracking-tight sm:text-4xl">{page.titel}</h1>
      {image && (
        <Picture media={image} sizes="(min-width: 768px) 768px, 100vw" fit="intrinsic" priority className="mt-6 rounded-lg" />
      )}
      <Prose html={page.body} className="mt-6 text-lg" />
    </article>
  )
}
