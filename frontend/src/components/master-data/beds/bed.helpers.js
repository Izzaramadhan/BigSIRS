/*
 * Fungsi murni modul Bed: format tampilan, sanitasi pesan error, validasi, dan payload.
 * Tanpa Vue, tanpa API — sehingga mudah diuji (lihat __tests__/bed.helpers.spec.js).
 */

export const EMPTY = '—'
export const BED_NAME_MAX_LENGTH = 100

const TECHNICAL_ERROR_PATTERN = /sqlstate|syntax error|stack trace|pdoexception|queryexception/i

/** "Kamar 1 - Bangsal Melati" untuk tabel & dialog hapus. */
export function formatBedRoom(bed) {
  if (bed?.room_table_display_name) return bed.room_table_display_name
  const room = bed?.room
  if (!room) return EMPTY
  return [room.name, room.ward?.name].filter(Boolean).join(' - ') || EMPTY
}

/** Nama kelas perawatan kamar, mis. "Kelas 3". */
export function formatBedClass(bed) {
  const roomClass = bed?.room?.room_class ?? bed?.room?.class
  return roomClass?.name || EMPTY
}

/** Deskripsi bed atau tanda kosong jika null/spasi. */
export function formatBedDescription(bed) {
  return bed?.description?.trim() || EMPTY
}

/** "Kamar 1 - Bangsal Melati - VIP" untuk pilihan kamar di form. */
export function formatRoomOption(room) {
  if (room?.display_name) return room.display_name
  const roomClass = room?.room_class ?? room?.class
  return [
    room?.name || 'Kamar',
    room?.ward?.name || 'Tanpa Bangsal',
    roomClass?.name || '(Kelas Belum Ditentukan)',
  ].join(' - ')
}

export function createBedForm(bed = null) {
  return {
    room_id: bed?.room_id ?? bed?.room?.id ?? '',
    name: bed?.name ?? '',
    description: bed?.description ?? '',
  }
}

/** @returns {Record<string, string>} kosong jika valid */
export function validateBedForm(form) {
  const errors = {}
  const name = String(form?.name ?? '').trim()

  if (!form?.room_id) errors.room_id = 'Nama kamar wajib dipilih.'

  if (!name) errors.name = 'Nama bed wajib diisi.'
  else if (name.length > BED_NAME_MAX_LENGTH) {
    errors.name = `Nama bed maksimal ${BED_NAME_MAX_LENGTH} karakter.`
  }

  return errors
}

export function toBedPayload(form) {
  const description = String(form?.description ?? '').trim()
  return {
    room_id: Number(form?.room_id),
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
export function getSafeBedErrorMessage(normalizedError, fallbackMessage) {
  const status = normalizedError?.status
  const message = normalizedError?.message?.trim()

  if (!message) return fallbackMessage
  if (typeof status === 'number' && status >= 500) return fallbackMessage
  if (TECHNICAL_ERROR_PATTERN.test(message)) return fallbackMessage

  return message
}
