import Image from 'next/image'
import type { Media } from '@/lib/api'

type Props = {
  media: Media
  /** srcset-sizes, t.ex. "(min-width: 640px) 33vw, 100vw". */
  sizes: string
  /** cover: fast 3:2-yta, intrinsic: bildens egna mått. */
  fit?: 'cover' | 'intrinsic'
  priority?: boolean
  className?: string
}

export function Picture({ media, sizes, fit = 'cover', priority = false, className = '' }: Props) {
  const alt = media.alt ?? ''

  if (fit === 'intrinsic' && media.width && media.height) {
    return (
      <Image
        src={media.url}
        alt={alt}
        width={media.width}
        height={media.height}
        sizes={sizes}
        priority={priority}
        className={`h-auto w-full ${className}`}
      />
    )
  }

  return (
    <div className={`relative aspect-[3/2] overflow-hidden bg-stone-100 ${className}`}>
      <Image src={media.url} alt={alt} fill sizes={sizes} priority={priority} className="object-cover" />
    </div>
  )
}
