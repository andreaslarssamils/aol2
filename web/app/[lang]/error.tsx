'use client'

import { useParams } from 'next/navigation'
import { defaultLocale, isLocale, t } from '@/lib/i18n'

export default function ErrorPage({ reset }: { error: Error; reset: () => void }) {
  const params = useParams<{ lang?: string }>()
  const dict = t(isLocale(params?.lang) ? params.lang : defaultLocale)

  return (
    <section className="py-16 text-center">
      <h1 className="text-3xl font-semibold tracking-tight">{dict.error.title}</h1>
      <p className="mt-3 text-stone-600">{dict.error.text}</p>
      <button
        type="button"
        onClick={reset}
        className="mt-6 rounded-md bg-brand px-4 py-2 text-sm font-medium text-white hover:bg-brand-dark"
      >
        {dict.error.retry}
      </button>
    </section>
  )
}
