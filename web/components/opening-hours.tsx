import type { OpeningDay } from '@/lib/api'
import { formatDay, todayKey } from '@/lib/datetime'
import { type Locale, capitalize, t } from '@/lib/i18n'

/** Veckans öppettider från API:et (måndag–söndag). Dagens rad markeras, avvikelser lyfts fram med notering. */
export function OpeningHours({ days, locale, now }: { days: OpeningDay[]; locale: Locale; now?: Date }) {
  const dict = t(locale)
  const today = todayKey(now)
  const unknown = days.length === 0 || days.every((day) => day.closed && !day.is_exception)

  if (unknown) {
    return <p className="text-sm text-stone-600">{dict.business.noHours}</p>
  }

  return (
    <table className="w-full text-sm">
      <tbody>
        {days.map((day) => {
          const isToday = day.date === today

          return (
            <tr
              key={day.date}
              aria-current={isToday ? 'date' : undefined}
              className={`border-b border-stone-100 ${isToday ? 'font-semibold text-stone-900' : 'text-stone-700'} ${
                day.is_exception ? 'bg-amber-50' : ''
              }`}
            >
              <th scope="row" className="py-1.5 pr-3 text-left font-[inherit]">
                {capitalize(formatDay(day.date, locale, { weekday: 'long' }))}
                {isToday && <span className="ml-2 text-xs font-medium text-brand">{dict.business.today}</span>}
              </th>
              <td className="py-1.5 text-right tabular-nums">
                {day.closed ? dict.business.closed : day.intervals.map((i) => `${i.opens}–${i.closes}`).join(', ')}
                {day.is_exception && (
                  <span className="ml-2 inline-block text-xs font-normal text-amber-800">
                    {day.note ?? dict.business.exception}
                  </span>
                )}
              </td>
            </tr>
          )
        })}
      </tbody>
    </table>
  )
}
