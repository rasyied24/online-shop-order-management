import { describe, expect, it } from 'vitest'
import { parseIsoDate, parsePage } from './url-params'

describe('parsePage', () => {
  it('membaca nomor halaman yang valid', () => {
    expect(parsePage('3')).toBe(3)
  })

  it.each([null, '', 'abc', '0', '-2', '1.5'])('kembali ke halaman 1 untuk %j', (value) => {
    expect(parsePage(value)).toBe(1)
  })
})

describe('parseIsoDate', () => {
  it('menerima format YYYY-MM-DD', () => {
    expect(parseIsoDate('2026-10-28')).toBe('2026-10-28')
  })

  it.each([null, '', '28-10-2026', '2026/10/28', 'besok'])('mengabaikan %j', (value) => {
    expect(parseIsoDate(value)).toBe('')
  })
})