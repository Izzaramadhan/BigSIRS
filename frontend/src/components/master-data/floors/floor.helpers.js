/*
 * Fungsi murni modul Lantai: format tampilan, sanitasi pesan error, validasi, dan payload.
 * Tanpa Vue, tanpa API — sehingga mudah diuji (lihat __tests__/floor.helpers.spec.js).
 */

export const EMPTY = '—'
export const FLOOR_NAME_MAX_LENGTH = 255

const TECHNICAL_ERROR_PATTERN = /sqlstate|syntax error|stack trace|pdoexception|queryexception/i

/** Nama gedung pemilik lantai untuk tabel, atau tanda kosong. */
export function formatFloorBuilding(floor) {
  return floor?.building?.name?.trim() || EMPTY
}

/** Deskripsi lantai atau tanda kosong bila null/spasi. */
export function formatFloorDescription(floor) {
  return floor?.description?.trim() || EMPTY
}

/** Label pilihan gedung di dropdown form. */
export function formatBuildingOption(building) {
  return building?.name?.trim() || 'Gedung'
}

export function createFloorForm(floor = null) {
  return {
    building_id: floor?.building_id ?? floor?.building?.id ?? '',
    name: floor?.name ?? '',
    description: floor?.description ?? '',
  }
}

/** @returns {Record<string, string>} kosong jika valid */
export function validateFloorForm(form) {
  const errors = {}
  const name = String(form?.name ?? '').trim()

  if (!form?.building_id) errors.building_id = 'Gedung wajib dipilih.'

  if (!name) errors.name = 'Nama lantai wajib diisi.'
  else if (name.length > FLOOR_NAME_MAX_LENGTH) {
    errors.name = `Nama lantai maksimal ${FLOOR_NAME_MAX_LENGTH} karakter.`
  }

  return errors
}

export function toFloorPayload(form) {
  const description = String(form?.description ?? '').trim()
  return {
    building_id: Number(form?.building_id),
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
export function getSafeFloorErrorMessage(normalizedError, fallbackMessage) {
  const status = normalizedError?.status
  const message = normalizedError?.message?.trim()

  if (!message) return fallbackMessage
  if (typeof status === 'number' && status >= 500) return fallbackMessage
  if (TECHNICAL_ERROR_PATTERN.test(message)) return fallbackMessage

  return message
}
