/*
 * Fungsi murni modul Pendidikan: format tampilan, factory form, validasi,
 * pembentuk payload, dan sanitasi pesan error tanpa ketergantungan Vue/API.
 */

export const EMPTY = '—'
export const EDUCATION_NAME_MAX_LENGTH = 50

const TECHNICAL_ERROR_PATTERN = /sqlstate|syntax error|stack trace|pdoexception|queryexception/i

/** Format nama pendidikan atau tanda kosong bila null/spasi. */
export function formatEducationName(education) {
  return education?.name?.trim() || EMPTY
}

/** Inisialisasi state reaktif form tambah/edit pendidikan. */
export function createEducationForm(education = null) {
  return {
    name: education?.name ?? '',
  }
}

/**
 * Validasi form pendidikan sesuai aturan Store/UpdateEducationRequest (required, max 50).
 *
 * @returns {Record<string, string>} kosong jika valid
 */
export function validateEducationForm(form) {
  const errors = {}
  const name = String(form?.name ?? '')
    .trim()
    .replace(/\s+/g, ' ')

  if (!name) errors.name = 'Nama pendidikan wajib diisi.'
  else if (name.length > EDUCATION_NAME_MAX_LENGTH) {
    errors.name = `Nama pendidikan maksimal ${EDUCATION_NAME_MAX_LENGTH} karakter.`
  }

  return errors
}

/** Bentuk payload bersih untuk endpoint POST/PUT `/master-data/educations`. */
export function toEducationPayload(form) {
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
export function getSafeEducationErrorMessage(normalizedError, fallbackMessage) {
  const status = normalizedError?.status
  const message = normalizedError?.message?.trim()

  if (!message) return fallbackMessage
  if (typeof status === 'number' && status >= 500) return fallbackMessage
  if (TECHNICAL_ERROR_PATTERN.test(message)) return fallbackMessage

  return message
}
