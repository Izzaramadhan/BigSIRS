/*
 * Fungsi murni modul Bangsal: format tampilan, sanitasi pesan error, validasi, dan payload.
 * Tanpa Vue, tanpa API — sehingga mudah diuji (lihat __tests__/ward.helpers.spec.js).
 */

export const EMPTY = '—'
export const WARD_NAME_MAX_LENGTH = 255

const TECHNICAL_ERROR_PATTERN = /sqlstate|syntax error|stack trace|pdoexception|queryexception/i

/** Format gabungan "Lantai 1 - Gedung Utama" untuk tabel dan dialog hapus. */
export function formatWardFloor(ward) {
  if (ward?.floor_display_name?.trim()) return ward.floor_display_name.trim()
  const floor = ward?.floor
  if (!floor) return EMPTY
  return [floor.name, floor.building?.name].filter(Boolean).join(' - ') || EMPTY
}

/** Deskripsi bangsal atau tanda kosong bila null/spasi. */
export function formatWardDescription(ward) {
  return ward?.description?.trim() || EMPTY
}

/** Format label "Lantai 1 - Gedung Utama" untuk dropdown Lantai di form. */
export function formatFloorOption(floor) {
  if (floor?.display_name?.trim()) return floor.display_name.trim()
  return [floor?.name, floor?.building?.name].filter(Boolean).join(' - ') || 'Lantai'
}

export function createWardForm(ward = null) {
  return {
    floor_id: ward?.floor_id ?? ward?.floor?.id ?? '',
    name: ward?.name ?? '',
    description: ward?.description ?? '',
  }
}

/** @returns {Record<string, string>} kosong jika valid */
export function validateWardForm(form) {
  const errors = {}
  const name = String(form?.name ?? '').trim()

  if (!form?.floor_id) errors.floor_id = 'Lantai wajib dipilih.'

  if (!name) errors.name = 'Nama bangsal wajib diisi.'
  else if (name.length > WARD_NAME_MAX_LENGTH) {
    errors.name = `Nama bangsal maksimal ${WARD_NAME_MAX_LENGTH} karakter.`
  }

  return errors
}

export function toWardPayload(form) {
  const description = String(form?.description ?? '').trim()
  return {
    floor_id: Number(form?.floor_id),
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
export function getSafeWardErrorMessage(normalizedError, fallbackMessage) {
  const status = normalizedError?.status
  const message = normalizedError?.message?.trim()

  if (!message) return fallbackMessage
  if (typeof status === 'number' && status >= 500) return fallbackMessage
  if (TECHNICAL_ERROR_PATTERN.test(message)) return fallbackMessage

  return message
}
