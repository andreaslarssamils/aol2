import type { LunchMenu } from '@/lib/api'
import { dayKeys, lunchDayKey, weekdayName } from '@/lib/datetime'
import { type Locale, capitalize, t } from '@/lib/i18n'

/** Veckans lunch med dagens rätter markerade. Dagar utan rätter visas inte. */
export function LunchMenuWeek({ menu, locale, now }: { menu: LunchMenu; locale: Locale; now?: Date }) {
  const dict = t(locale)
  const today = lunchDayKey(now)
  const days = dayKeys.filter((key) => (menu.menu[key]?.length ?? 0) > 0)

  return (
    <dl className="divide-y divide-stone-100 text-sm">
      {days.map((key, index) => {
        const isToday = key === today

        return (
          <div key={key} className={`flex gap-4 py-2 ${isToday ? 'font-semibold text-stone-900' : 'text-stone-700'}`}>
            <dt className="w-28 shrink-0">
              {capitalize(weekdayName(dayKeys.indexOf(key) >= 0 ? dayKeys.indexOf(key) : index, locale))}
              {isToday && <span className="ml-2 text-xs font-medium text-brand">{dict.business.today}</span>}
            </dt>
            <dd>
              <ul>
                {menu.menu[key].map((dish) => (
                  <li key={dish}>{dish}</li>
                ))}
              </ul>
            </dd>
          </div>
        )
      })}
    </dl>
  )
}
