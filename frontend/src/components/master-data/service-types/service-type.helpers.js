/*
 * Fungsi murni modul Jenis Layanan: format tampilan, factory form, validasi input,
 * pembentuk payload, serta sanitasi pesan error tanpa ketergantungan Vue/API.
 */

export const EMPTY = '—'
export const SERVICE_TYPE_NAME_MAX_LENGTH = 45

const TECHNICAL_ERROR_PATTERN = /sqlstate|syntax error|stack trace|pdoexception|queryexception/i

/** Format nama jenis layanan atau tanda kosong bila null/spasi. */
export function formatServiceTypeName(serviceType) {
  return serviceType?.name?.trim() || EMPTY
}

/** Inisialisasi state reaktif form tambah/edit jenis layanan. */
export function createServiceTypeForm(serviceType = null) {
  return {
    name: serviceType?.name ?? '',
  }
}

/**
 * Validasi client-side form jenis layanan sesuai ServiceTypeRequest (`name` wajib, max 45).
 *
 * @returns {Record<string, string>} kosong jika valid
 */
export function validateServiceTypeForm(form) {
  const errors = {}
  const name = String(form?.name ?? '')
    .trim()
    .replace(/\s+/g, ' ')

  if (!name) errors.name = 'Nama jenis layanan wajib diisi.'
  else if (name.length > SERVICE_TYPE_NAME_MAX_LENGTH) {
    errors.name = `Nama jenis layanan maksimal ${SERVICE_TYPE_NAME_MAX_LENGTH} karakter.`
  }

  return errors
}

/** Bentuk payload bersih untuk endpoint POST/PUT `/master-data/service-types`. */
export function toServiceTypePayload(form) {
  return {
    name: String(form?.name ?? '')
      .trim()
      .replace(/\s+/g, ' '),
  }
}

/**
 * Pastikan pesan error API (4xx/409/422/5xx) tidak membocorkan detail SQL/trace ke UI.
 *
 * @param {{ status?: number|null, message?: string, fieldErrors?: Record<string, string> } | null | undefined} normalizedError
 * @param {string} fallbackMessage
 */
export function getSafeServiceTypeErrorMessage(normalizedError, fallbackMessage) {
  const status = normalizedError?.status
  const rawMessage = normalizedError?.fieldErrors?.id || normalizedError?.message
  const message = rawMessage?.trim()

  if (!message) return fallbackMessage
  if (typeof status === 'number' && status >= 500) return fallbackMessage
  if (TECHNICAL_ERROR_PATTERN.test(message)) return fallbackMessage

  return message
}
