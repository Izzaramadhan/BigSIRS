/*
 * Fungsi murni halaman prototipe Ketersediaan Bed SIRS:
 * pemformatan angka kapasitas/tersedia, tanggal update Indonesia, filter pencarian lokal,
 * dan sanitasi pesan error tanpa ketergantungan Vue/API.
 */

export const EMPTY = '—'
export const PER_PAGE_OPTIONS = [10, 25, 50, 100]

const TECHNICAL_ERROR_PATTERN = /sqlstate|syntax error|stack trace|pdoexception|queryexception/i

/** Format angka kapasitas/bed tersedia atau tanda kosong bila null/NaN. */
export function formatBedCount(value) {
  if (value === null || value === undefined || value === '') return EMPTY
  const num = Number(value)
  return Number.isFinite(num) ? String(num) : EMPTY
}

/** Format nilai teks sel atau tanda kosong bila null/spasi. */
export function formatTextCell(value) {
  if (value === null || value === undefined) return EMPTY
  return String(value).trim() || EMPTY
}

/**
 * Format timestamp pembaruan ke format Indonesia: "22 Oktober 2026, 09.30".
 */
export function formatSirsLastUpdate(dateVal) {
  if (!dateVal) return EMPTY
  const date = new Date(dateVal)
  if (Number.isNaN(date.getTime())) return EMPTY

  try {
    const datePart = date.toLocaleDateString('id-ID', {
      day: 'numeric',
      month: 'long',
      year: 'numeric',
    })
    const hours = String(date.getHours()).padStart(2, '0')
    const minutes = String(date.getMinutes()).padStart(2, '0')
    return `${datePart}, ${hours}.${minutes}`
  } catch {
    return EMPTY
  }
}

/** Saring baris ketersediaan berdasarkan nama kelas atau nama ruang secara case-insensitive. */
export function filterSirsRows(rows, search) {
  const list = Array.isArray(rows) ? rows : []
  const query = String(search ?? '')
    .trim()
    .toLowerCase()
  if (!query) return list

  return list.filter((item) => {
    const className = String(item?.className ?? '').toLowerCase()
    const roomName = String(item?.roomName ?? '').toLowerCase()
    return className.includes(query) || roomName.includes(query)
  })
}

/** Pastikan pesan error tidak membocorkan detail teknis atau database. */
export function getSafeSirsErrorMessage(rawError, fallbackMessage) {
  if (!rawError) return ''
  const message =
    typeof rawError === 'string' ? rawError.trim() : String(rawError?.message ?? '').trim()
  const status = typeof rawError === 'object' ? rawError?.status : null

  if (!message) return fallbackMessage
  if (typeof status === 'number' && status >= 500) return fallbackMessage
  if (TECHNICAL_ERROR_PATTERN.test(message)) return fallbackMessage

  return message
}
