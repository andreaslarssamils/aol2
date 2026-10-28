import Link from 'next/link'

type Props = {
  id: string
  title: string
  href?: string
  linkLabel?: string
}

export function SectionHeading({ id, title, href, linkLabel }: Props) {
  return (
    <div className="mb-4 flex items-baseline justify-between gap-4">
      <h2 id={id} className="text-xl font-semibold tracking-tight">
        {title}
      </h2>
      {href && linkLabel && (
        <Link href={href} className="text-sm text-brand hover:underline">
          {linkLabel}
        </Link>
      )}
    </div>
  )
}
