type Tone = 'neutral' | 'brand' | 'ad' | 'open'

const tones: Record<Tone, string> = {
  neutral: 'bg-stone-100 text-stone-700',
  brand: 'bg-brand-light text-brand-dark',
  ad: 'bg-amber-100 text-amber-900',
  open: 'bg-green-100 text-green-900',
}

export function Badge({ tone = 'neutral', children }: { tone?: Tone; children: React.ReactNode }) {
  return (
    <span className={`inline-flex items-center rounded px-1.5 py-0.5 text-xs font-medium ${tones[tone]}`}>
      {children}
    </span>
  )
}
