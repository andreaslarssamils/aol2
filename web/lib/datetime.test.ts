import { describe, expect, it } from 'vitest'
import { dateKey, formatDate, formatDay, formatEventWhen, formatTime, lunchDayKey, weekdayName } from './datetime'

describe('tider visas i Europe/Stockholm oavsett processens tidszon', () => {
  it('klockslag med offset visas som de är', () => {
    expect(formatTime('2026-11-03T19:00:00+01:00', 'sv')).toBe('19:00')
    expect(formatTime('2026-11-03T19:00:00+01:00', 'en')).toBe('19:00')
  })

  it('UTC-tidpunkter flyttas till svensk tid, sommartid inräknad', () => {
    expect(formatTime('2026-07-01T10:00:00Z', 'sv')).toBe('12:00')
    expect(formatTime('2026-12-01T10:00:00Z', 'sv')).toBe('11:00')
  })

  it('kalenderdagen följer svensk tid runt midnatt', () => {
    expect(dateKey('2026-11-03T23:30:00Z')).toBe('2026-11-04')
    expect(dateKey('2026-11-03T19:00:00+01:00')).toBe('2026-11-03')
    expect(lunchDayKey(new Date('2026-11-03T23:30:00Z'))).toBe('wed')
    expect(lunchDayKey(new Date('2026-11-08T12:00:00Z'))).toBe('sun')
  })

  it('rena datum formateras utan att dagen skiftar', () => {
    expect(formatDay('2026-11-02', 'sv', { weekday: 'long' })).toBe('måndag')
    expect(formatDay('2026-11-02', 'en', { weekday: 'long' })).toBe('Monday')
    expect(weekdayName(0, 'sv')).toBe('måndag')
    expect(weekdayName(6, 'en')).toBe('Sunday')
  })

  it('formaterar datum på valt språk', () => {
    expect(formatDate('2026-11-03T19:00:00+01:00', 'sv')).toBe('3 november 2026')
    expect(formatDate('2026-11-03T19:00:00+01:00', 'en')).toBe('3 November 2026')
  })
})

describe('formatEventWhen', () => {
  it('samma dag med sluttid', () => {
    expect(
      formatEventWhen({ starts_at: '2026-11-03T19:00:00+01:00', ends_at: '2026-11-03T21:00:00+01:00', all_day: false }, 'sv'),
    ).toMatch(/^tis\.? 3 nov\.? 19:00–21:00$/)
  })

  it('utan sluttid', () => {
    expect(formatEventWhen({ starts_at: '2026-11-03T19:00:00+01:00', ends_at: null, all_day: false }, 'en')).toMatch(
      /^Tue,? 3 Nov 19:00$/,
    )
  })

  it('över flera dagar', () => {
    expect(
      formatEventWhen({ starts_at: '2026-11-06T18:00:00+01:00', ends_at: '2026-11-07T02:00:00+01:00', all_day: false }, 'sv'),
    ).toMatch(/^fre\.? 6 nov\.? 18:00 – lör\.? 7 nov\.? 02:00$/)
  })

  it('heldag visar bara dagen och etiketten', () => {
    expect(
      formatEventWhen({ starts_at: '2026-11-21T00:00:00+01:00', ends_at: '2026-11-21T23:59:59+01:00', all_day: true }, 'sv', 'Hela dagen'),
    ).toMatch(/^lör\.? 21 nov\.?, Hela dagen$/)
  })
})
