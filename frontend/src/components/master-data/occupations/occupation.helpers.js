/*
 * Fungsi murni modul Pekerjaan: format tampilan, factory form, validasi,
 * pembentuk payload, dan sanitasi pesan error tanpa ketergantungan Vue/API.
 */

export const EMPTY = '—'
export const OCCUPATION_NAME_MAX_LENGTH = 50

const TECHNICAL_ERROR_PATTERN = /sqlstate|syntax error|stack trace|pdoexception|queryexception/i

/** Format nama pekerjaan atau tanda kosong bila null/spasi. */
export function formatOccupationName(occupation) {
  return occupation?.name?.trim() || EMPTY
}

/** Inisialisasi state reaktif form tambah/edit pekerjaan. */
export function createOccupationForm(occupation = null) {
  return {
    name: occupation?.name ?? '',
  }
}

/**
 * Validasi form pekerjaan sesuai aturan Store/UpdateOccupationRequest (required, max 50).
 *
 * @returns {Record<string, string>} kosong jika valid
 */
export function validateOccupationForm(form) {
  const errors = {}
  const name = String(form?.name ?? '')
    .trim()
    .replace(/\s+/g, ' ')

  if (!name) errors.name = 'Nama pekerjaan wajib diisi.'
  else if (name.length > OCCUPATION_NAME_MAX_LENGTH) {
    errors.name = `Nama pekerjaan maksimal ${OCCUPATION_NAME_MAX_LENGTH} karakter.`
  }

  return errors
}

/** Bentuk payload bersih untuk endpoint POST/PUT `/master-data/occupations`. */
export function toOccupationPayload(form) {
  return {
    name: String(form?.name ?? '')
      .trim()
      .replace(/\s+/g, ' '),
  }
}

/**
 * Pastikan pesan error API (4xx/409/422/5xx) tidak membocorkan detail teknis atau SQL.
 *
 * @param {{ status?: number|null, message?: string } | null | undefined} normalizedError
 * @param {string} fallbackMessage
 */
export function getSafeOccupationErrorMessage(normalizedError, fallbackMessage) {
  const status = normalizedError?.status
  const message = normalizedError?.message?.trim()

  if (!message) return fallbackMessage
  if (typeof status === 'number' && status >= 500) return fallbackMessage
  if (TECHNICAL_ERROR_PATTERN.test(message)) return fallbackMessage

  return message
}
