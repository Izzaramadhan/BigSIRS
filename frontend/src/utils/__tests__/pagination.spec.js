import { describe, expect, it } from 'vitest'
import { buildPageRange, ELLIPSIS } from '../pagination'

describe('buildPageRange', () => {
  it('menampilkan semua halaman jika jumlahnya sedikit', () => {
    expect(buildPageRange(1, 3)).toEqual([1, 2, 3])
  })

  it('menyisipkan elipsis di kedua sisi halaman aktif', () => {
    expect(buildPageRange(5, 10)).toEqual([1, ELLIPSIS, 4, 5, 6, ELLIPSIS, 10])
  })

  it('menampilkan halaman langsung bila celahnya hanya satu', () => {
    expect(buildPageRange(3, 10)).toEqual([1, 2, 3, 4, ELLIPSIS, 10])
  })

  it('mengembalikan array kosong bila tidak ada halaman', () => {
    expect(buildPageRange(1, 0)).toEqual([])
  })
})
