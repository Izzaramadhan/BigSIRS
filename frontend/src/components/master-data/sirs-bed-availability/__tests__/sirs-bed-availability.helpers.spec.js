import { describe, expect, it } from 'vitest'
import {
  EMPTY,
  filterSirsRows,
  formatBedCount,
  formatSirsLastUpdate,
  formatTextCell,
  getSafeSirsErrorMessage,
  PER_PAGE_OPTIONS,
} from '../sirs-bed-availability.helpers'

describe('sirs-bed-availability.helpers', () => {
  it('PER_PAGE_OPTIONS menyediakan pilihan entri standar 10, 25, 50, 100', () => {
    expect(PER_PAGE_OPTIONS).toEqual([10, 25, 50, 100])
  })

  it('formatBedCount memformat angka termasuk 0 dan fallback tanda kosong', () => {
    expect(formatBedCount(10)).toBe('10')
    expect(formatBedCount(0)).toBe('0')
    expect(formatBedCount('4')).toBe('4')
    expect(formatBedCount(null)).toBe(EMPTY)
    expect(formatBedCount(undefined)).toBe(EMPTY)
    expect(formatBedCount('')).toBe(EMPTY)
    expect(formatBedCount('abc')).toBe(EMPTY)
  })

  it('formatTextCell merapikan string dan fallback ke tanda kosong', () => {
    expect(formatTextCell('  Kelas 1  ')).toBe('Kelas 1')
    expect(formatTextCell('   ')).toBe(EMPTY)
    expect(formatTextCell(null)).toBe(EMPTY)
  })

  it('formatSirsLastUpdate memformat tanggal Indonesia dan menangani nilai kosong/tidak valid', () => {
    const formatted = formatSirsLastUpdate('2026-10-22T09:30:00')
    expect(formatted).toContain('22 Oktober 2026')
    expect(formatted).toContain('09.30')

    expect(formatSirsLastUpdate(null)).toBe(EMPTY)
    expect(formatSirsLastUpdate('')).toBe(EMPTY)
    expect(formatSirsLastUpdate('invalid-date')).toBe(EMPTY)
  })

  it('filterSirsRows menyaring berdasarkan className atau roomName secara case-insensitive', () => {
    const rows = [
      { id: 1, className: 'VIP', roomName: 'Ruang Anggrek 1' },
      { id: 2, className: 'Kelas 3', roomName: 'Ruang Mawar 201' },
    ]

    expect(filterSirsRows(rows, '')).toEqual(rows)
    expect(filterSirsRows(rows, 'vip')).toEqual([rows[0]])
    expect(filterSirsRows(rows, 'mawar')).toEqual([rows[1]])
    expect(filterSirsRows(rows, 'tidak-ada')).toEqual([])
    expect(filterSirsRows(null, 'vip')).toEqual([])
  })

  it('getSafeSirsErrorMessage menyaring error teknis atau 5xx', () => {
    expect(
      getSafeSirsErrorMessage({ status: 500, message: 'SQLSTATE[42S02]: Table missing' }, 'Fallback'),
    ).toBe('Fallback')
    expect(
      getSafeSirsErrorMessage({ status: 422, message: 'Data SIRS belum siap.' }, 'Fallback'),
    ).toBe('Data SIRS belum siap.')
    expect(getSafeSirsErrorMessage(null, 'Fallback')).toBe('')
  })
})
