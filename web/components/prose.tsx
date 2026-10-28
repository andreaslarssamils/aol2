/** HTML från redaktionen (RichEditor i Filament). Renderas som den är. */
export function Prose({ html, className = '' }: { html: string | null | undefined; className?: string }) {
  if (!html) {
    return null
  }

  return <div className={`prose text-stone-800 ${className}`} dangerouslySetInnerHTML={{ __html: html }} />
}
