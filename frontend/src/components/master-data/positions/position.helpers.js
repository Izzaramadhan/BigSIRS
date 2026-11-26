/*
 * Fungsi murni modul Jabatan: format tampilan, factory form, validasi,
 * pembentuk payload, dan sanitasi pesan error tanpa ketergantungan Vue/API.
 */

export const EMPTY = '—'
export const POSITION_NAME_MAX_LENGTH = 50
export const POSITION_DESCRIPTION_MAX_LENGTH = 50

const TECHNICAL_ERROR_PATTERN = /sqlstate|syntax error|stack trace|pdoexception|queryexception/i

/** Format nama jabatan atau tanda kosong bila null/spasi. */
export function formatPositionName(position) {
  return position?.name?.trim() || EMPTY
}

/** Format deskripsi jabatan atau tanda kosong bila null/spasi. */
export function formatPositionDescription(position) {
  return position?.description?.trim() || EMPTY
}

/** Inisialisasi state reaktif form tambah/edit jabatan. */
export function createPositionForm(position = null) {
  return {
    name: position?.name ?? '',
    description: position?.description ?? '',
  }
}

/**
 * Validasi form jabatan sesuai Store/UpdatePositionRequest (name wajib max 50, description opsional max 50).
 *
 * @returns {Record<string, string>} kosong jika valid
 */
export function validatePositionForm(form) {
  const errors = {}
  const name = String(form?.name ?? '')
    .trim()
    .replace(/\s+/g, ' ')
  const description = String(form?.description ?? '').trim()

  if (!name) errors.name = 'Nama jabatan wajib diisi.'
  else if (name.length > POSITION_NAME_MAX_LENGTH) {
    errors.name = `Nama jabatan maksimal ${POSITION_NAME_MAX_LENGTH} karakter.`
  }

  if (description.length > POSITION_DESCRIPTION_MAX_LENGTH) {
    errors.description = `Deskripsi maksimal ${POSITION_DESCRIPTION_MAX_LENGTH} karakter.`
  }

  return errors
}

/** Bentuk payload bersih untuk endpoint POST/PUT `/master-data/positions`. */
export function toPositionPayload(form) {
  const description = String(form?.description ?? '').trim()
  return {
    name: String(form?.name ?? '')
      .trim()
      .replace(/\s+/g, ' '),
    description: description || null,
  }
}

/**
 * Pastikan pesan error API (4xx/409/422/5xx) tidak membocorkan detail teknis atau SQL.
 *
 * @param {{ status?: number|null, message?: string } | null | undefined} normalizedError
 * @param {string} fallbackMessage
 */
export function getSafePositionErrorMessage(normalizedError, fallbackMessage) {
  const status = normalizedError?.status
  const message = normalizedError?.message?.trim()

  if (!message) return fallbackMessage
  if (typeof status === 'number' && status >= 500) return fallbackMessage
  if (TECHNICAL_ERROR_PATTERN.test(message)) return fallbackMessage

  return message
}
