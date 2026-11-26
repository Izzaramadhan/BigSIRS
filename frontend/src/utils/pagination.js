export const ELLIPSIS = '…'

/**
 * Daftar nomor halaman dengan elipsis, mis. [1, '…', 4, 5, 6, '…', 10].
 *
 * @param {number} current halaman aktif (mulai 1)
 * @param {number} last    halaman terakhir
 * @param {number} [siblings] jumlah halaman di kiri/kanan halaman aktif
 * @returns {(number|string)[]}
 */
export function buildPageRange(current, last, siblings = 1) {
  if (last <= 0) return []

  const pages = []
  for (let page = 1; page <= last; page++) {
    const isEdge = page === 1 || page === last
    const isNearCurrent = Math.abs(page - current) <= siblings
    if (isEdge || isNearCurrent) pages.push(page)
  }

  const result = []
  pages.forEach((page, i) => {
    const previous = pages[i - 1]
    if (previous !== undefined) {
      const gap = page - previous
      // Celah satu halaman lebih baik ditampilkan langsung daripada elipsis.
      if (gap === 2) result.push(previous + 1)
      else if (gap > 2) result.push(ELLIPSIS)
    }
    result.push(page)
  })
  return result
}
