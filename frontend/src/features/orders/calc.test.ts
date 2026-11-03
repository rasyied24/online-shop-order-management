import { describe, expect, it } from 'vitest'
import { centsToRupiah, lineSubtotalCents, toCents } from './calc'

describe('toCents', () => {
  it('mengubah string uang menjadi sen (bilangan bulat)', () => {
    expect(toCents('12000.00')).toBe(1_200_000)
    expect(toCents('0.10')).toBe(10)
  })
})

describe('lineSubtotalCents', () => {
  it('mengalikan harga satuan dengan jumlah', () => {
    expect(lineSubtotalCents('12000.00', '2')).toBe(2_400_000)
  })

  it('bebas galat floating-point', () => {
    // Dalam float biasa: 0.1 * 3 = 0.30000000000000004
    expect(0.1 * 3).not.toBe(0.3)
    expect(lineSubtotalCents('0.10', '3')).toBe(30)
  })

  it('menganggap jumlah tidak valid sebagai 0', () => {
    expect(lineSubtotalCents('12000.00', '')).toBe(0)
    expect(lineSubtotalCents('12000.00', 'abc')).toBe(0)
    expect(lineSubtotalCents('12000.00', '-1')).toBe(0)
    expect(lineSubtotalCents('12000.00', '1.5')).toBe(0)
  })

  it('mengabaikan spasi di sekitar jumlah', () => {
    expect(lineSubtotalCents('1000.00', ' 3 ')).toBe(300_000)
  })
})

describe('centsToRupiah', () => {
  it('memformat sen menjadi Rupiah', () => {
    expect(centsToRupiah(3_600_000)).toMatch(/^Rp\s?36\.000$/)
  })

  it('total beberapa baris dijumlahkan dalam sen', () => {
    const total = lineSubtotalCents('12000.00', '2') + lineSubtotalCents('12000.00', '1')
    expect(centsToRupiah(total)).toMatch(/^Rp\s?36\.000$/)
  })
})