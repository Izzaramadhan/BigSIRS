/*
 * Fungsi murni modul Asuhan Gizi (DietType): format tampilan, factory form, validasi input
 * sesuai Store/UpdateDietTypeRequest, pembentuk payload, dan sanitasi pesan error.
 */

export const EMPTY = '—'
export const DIET_TYPE_NAME_MAX_LENGTH = 50
export const DIET_TYPE_DESCRIPTION_MAX_LENGTH = 255

const TECHNICAL_ERROR_PATTERN = /sqlstate|syntax error|stack trace|pdoexception|queryexception/i

/** Format nama asuhan gizi atau tanda kosong bila null/spasi. */
export function formatDietTypeName(dietType) {
  return dietType?.name?.trim() || EMPTY
}

/** Format deskripsi asuhan gizi atau tanda kosong bila null/spasi. */
export function formatDietTypeDescription(dietType) {
  return dietType?.description?.trim() || EMPTY
}

/** Ringkasan nama untuk dialog konfirmasi hapus dan aria-label. */
export function formatDietTypeSummary(dietType) {
  return dietType?.name?.trim() || 'Asuhan Gizi'
}

/** Inisialisasi state reaktif form tambah/edit asuhan gizi. */
export function createDietTypeForm(dietType = null) {
  return {
    name: dietType?.name ?? '',
    description: dietType?.description ?? '',
  }
}

/**
 * Validasi client-side form asuhan gizi sesuai StoreDietTypeRequest / UpdateDietTypeRequest:
 * - `name`: wajib, maks 50 karakter
 * - `description`: opsional, maks 255 karakter
 *
 * @returns {Record<string, string>} kosong jika valid
 */
export function validateDietTypeForm(form) {
  const errors = {}
  const name = String(form?.name ?? '')
    .trim()
    .replace(/\s+/g, ' ')
  const description = String(form?.description ?? '').trim()

  if (!name) errors.name = 'Nama asuhan gizi wajib diisi.'
  else if (name.length > DIET_TYPE_NAME_MAX_LENGTH) {
    errors.name = `Nama asuhan gizi maksimal ${DIET_TYPE_NAME_MAX_LENGTH} karakter.`
  }

  if (description.length > DIET_TYPE_DESCRIPTION_MAX_LENGTH) {
    errors.description = `Deskripsi maksimal ${DIET_TYPE_DESCRIPTION_MAX_LENGTH} karakter.`
  }

  return errors
}

/** Bentuk payload bersih untuk endpoint POST/PUT `/master-data/diet-types`. */
export function toDietTypePayload(form) {
  const description = String(form?.description ?? '').trim()
  return {
    name: String(form?.name ?? '')
      .trim()
      .replace(/\s+/g, ' '),
    description: description || null,
  }
}

/**
 * Pastikan pesan error API (4xx/409/422/5xx) tidak membocorkan detail SQL/trace ke UI.
 *
 * @param {{ status?: number|null, message?: string } | null | undefined} normalizedError
 * @param {string} fallbackMessage
 */
export function getSafeDietTypeErrorMessage(normalizedError, fallbackMessage) {
  const status = normalizedError?.status
  const message = normalizedError?.message?.trim()

  if (!message) return fallbackMessage
  if (typeof status === 'number' && status >= 500) return fallbackMessage
  if (TECHNICAL_ERROR_PATTERN.test(message)) return fallbackMessage

  return message
}
