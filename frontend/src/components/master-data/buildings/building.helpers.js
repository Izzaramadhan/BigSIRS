/*
 * Fungsi murni modul Gedung: format tampilan, sanitasi pesan error, validasi, dan payload.
 * Tanpa Vue, tanpa API — sehingga mudah diuji (lihat __tests__/building.helpers.spec.js).
 */

export const EMPTY = '—'
export const BUILDING_NAME_MAX_LENGTH = 255

const TECHNICAL_ERROR_PATTERN = /sqlstate|syntax error|stack trace|pdoexception|queryexception/i

export function formatBuildingDescription(building) {
  return building?.description?.trim() || EMPTY
}

export function createBuildingForm(building = null) {
  return {
    name: building?.name ?? '',
    description: building?.description ?? '',
  }
}

/** @returns {Record<string, string>} kosong jika valid */
export function validateBuildingForm(form) {
  const errors = {}
  const name = String(form?.name ?? '').trim()

  if (!name) {
    errors.name = 'Nama gedung wajib diisi.'
  } else if (name.length > BUILDING_NAME_MAX_LENGTH) {
    errors.name = `Nama gedung maksimal ${BUILDING_NAME_MAX_LENGTH} karakter.`
  }

  return errors
}

export function toBuildingPayload(form) {
  const description = String(form?.description ?? '').trim()
  return {
    name: String(form?.name ?? '').trim(),
    description: description || null,
  }
}

/**
 * Pastikan pesan error API tidak membocorkan detail teknis atau database ke pengguna.
 *
 * @param {{ status?: number|null, message?: string } | null | undefined} normalizedError
 * @param {string} fallbackMessage
 */
export function getSafeBuildingErrorMessage(normalizedError, fallbackMessage) {
  const status = normalizedError?.status
  const message = normalizedError?.message?.trim()

  if (!message) return fallbackMessage
  if (typeof status === 'number' && status >= 500) return fallbackMessage
  if (TECHNICAL_ERROR_PATTERN.test(message)) return fallbackMessage

  return message
}
