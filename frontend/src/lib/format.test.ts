import { afterEach, describe, expect, it, vi } from 'vitest'
import {
  formatDate,
  formatDateTime,
  formatNumber,
  formatRupiah,
  todayIsoDate,
  toNumber,
} from './format'

describe('toNumber', () => {
  it('mengubah string uang dari server menjadi angka', () => {
    expect(toNumber('12000.00')).toBe(12000)
  })

  it('mengembalikan 0 untuk nilai kosong atau tidak valid', () => {
    expect(toNumber(null)).toBe(0)
    expect(toNumber(undefined)).toBe(0)
    expect(toNumber('abc')).toBe(0)
  })
})

describe('formatRupiah', () => {
  // \s juga cocok dengan spasi tak-putus (U+00A0) yang dipakai Intl.
  it('memformat string dari server tanpa desimal nol', () => {
    expect(formatRupiah('12000.00')).toMatch(/^Rp\s?12\.000$/)
  })

  it('memakai pemisah ribuan titik', () => {
    expect(formatRupiah(1234567)).toMatch(/^Rp\s?1\.234\.567$/)
  })

  it('tetap menampilkan desimal yang bermakna', () => {
    expect(formatRupiah('12500.5')).toMatch(/12\.500,5/)
  })

  it('menganggap nilai kosong sebagai Rp 0', () => {
    expect(formatRupiah(null)).toMatch(/^Rp\s?0$/)
  })
})

describe('formatNumber', () => {
  it('memakai pemisah ribuan titik', () => {
    expect(formatNumber(1234567)).toBe('1.234.567')
  })
})

describe('formatDate', () => {
  it('memformat tanggal tanpa jam dalam bahasa Indonesia', () => {
    expect(formatDate('1995-02-10')).toBe('10 Februari 1995')
  })

  it('tidak menggeser tanggal lahir sehari', () => {
    expect(formatDate('2000-01-01')).toBe('1 Januari 2000')
  })

  it('mengonversi waktu UTC ke tanggal WIB', () => {
    // 23.30 UTC tanggal 27 = 06.30 WIB tanggal 28
    expect(formatDate('2026-10-27T23:30:00Z')).toBe('28 Oktober 2026')
  })

  it('mengembalikan "-" untuk nilai kosong atau tidak valid', () => {
    expect(formatDate(null)).toBe('-')
    expect(formatDate(undefined)).toBe('-')
    expect(formatDate('bukan tanggal')).toBe('-')
  })
})

describe('formatDateTime', () => {
  it('menampilkan jam dalam zona WIB', () => {
    const text = formatDateTime('2026-10-28T06:21:00Z') // = 13.21 WIB
    expect(text).toContain('28 Oktober 2026')
    expect(text).toContain('13.21')
  })

  it('menghormati offset +07:00 dari server', () => {
    const text = formatDateTime('2026-10-28T13:21:00+07:00')
    expect(text).toContain('28 Oktober 2026')
    expect(text).toContain('13.21')
  })

  it('mengembalikan "-" untuk nilai tidak valid', () => {
    expect(formatDateTime('xyz')).toBe('-')
  })
})

describe('todayIsoDate', () => {
  afterEach(() => {
    vi.useRealTimers()
  })

  it('memakai tanggal lokal, bukan UTC', () => {
    vi.useFakeTimers()
    // 28 Okt 2026 pukul 00.05 waktu lokal: toISOString() bisa menghasilkan tanggal 27.
    vi.setSystemTime(new Date(2026, 9, 28, 0, 5, 0))
    expect(todayIsoDate()).toBe('2026-10-28')
  })

  it('selalu berformat YYYY-MM-DD', () => {
    expect(todayIsoDate()).toMatch(/^\d{4}-\d{2}-\d{2}$/)
  })
})