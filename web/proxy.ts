import { type NextRequest, NextResponse } from 'next/server'
import { defaultLocale, isLocale } from '@/lib/i18n'

/**
 * Språk via sökvägsprefix bara för icke-svenska: /en/... når app/[lang] direkt, allt annat
 * skrivs om internt till /sv/... så att [lang] alltid är satt. /sv/... i adressfältet
 * skickas till den prefixlösa adressen med 308.
 */
export function proxy(request: NextRequest) {
  const { pathname } = request.nextUrl
  const [, first = '', ...rest] = pathname.split('/')

  if (first === defaultLocale) {
    const url = request.nextUrl.clone()
    url.pathname = `/${rest.join('/')}`

    return NextResponse.redirect(url, 308)
  }

  if (isLocale(first)) {
    return NextResponse.next()
  }

  const url = request.nextUrl.clone()
  url.pathname = `/${defaultLocale}${pathname === '/' ? '' : pathname}`

  return NextResponse.rewrite(url)
}

export const config = {
  // Inte Next-interna filer och inte statiska filer (sökvägar med punkt).
  matcher: ['/((?!_next/|api/|.*\\..*).*)'],
}
