/*
 * Fungsi murni modul Kamar: format hierarki bangsal, kelas, deskripsi, validasi, dan payload.
 * Tanpa Vue, tanpa API — sehingga mudah diuji (lihat __tests__/room.helpers.spec.js).
 */

export const EMPTY = '—'
export const ROOM_CODE_MAX_LENGTH = 50
export const ROOM_NAME_MAX_LENGTH = 255
export const ROOM_CLASS_NAME_MAX_LENGTH = 255

const TECHNICAL_ERROR_PATTERN = /sqlstate|syntax error|stack trace|pdoexception|queryexception/i

/**
 * Format hierarki "Nama Bangsal - Nama Lantai - Nama Gedung" dari relasi API.
 * Fallback ke bagian yang tersedia atau ward_display_name jika objek relasi tidak dikirim.
 */
export function formatRoomWard(room) {
  const ward = room?.ward
  if (ward) {
    const parts = [ward.name, ward.floor?.name, ward.floor?.building?.name]
      .map((part) => (typeof part === 'string' ? part.trim() : ''))
      .filter(Boolean)
    if (parts.length > 0) return parts.join(' - ')
  }

  return room?.ward_display_name?.trim() || EMPTY
}

/** Nama kelas perawatan kamar, mis. "Kelas II". */
export function formatRoomClass(room) {
  const roomClass = room?.room_class ?? room?.class
  return roomClass?.name?.trim() || EMPTY
}

/** Deskripsi kamar atau tanda kosong bila null/spasi. */
export function formatRoomDescription(room) {
  return room?.description?.trim() || EMPTY
}

/**
 * Format label dropdown bangsal: "Nama Bangsal - Nama Lantai - Nama Gedung".
 */
export function formatWardOption(ward) {
  const parts = [ward?.name, ward?.floor?.name, ward?.floor?.building?.name]
    .map((part) => (typeof part === 'string' ? part.trim() : ''))
    .filter(Boolean)
  if (parts.length > 0) return parts.join(' - ')

  return ward?.ward_display_name?.trim() || ward?.display_name?.trim() || 'Bangsal'
}

/**
 * Normalisasi item kelas perawatan untuk SearchableSelect.
 */
export function toRoomClassOption(roomClass) {
  return {
    id: roomClass.id,
    name: roomClass.name,
    code: roomClass.code,
    label: roomClass.name,
    value: roomClass.id,
  }
}

export function createRoomForm(room = null) {
  const classVal = room?.room_class_id ?? room?.class_id ?? room?.room_class?.id ?? room?.class?.id ?? ''
  return {
    ward_id: room?.ward_id ?? room?.ward?.id ?? '',
    code: room?.code ?? '',
    name: room?.name ?? '',
    room_class_id: classVal ? Number(classVal) : '',
    description: room?.description ?? '',
  }
}

/** @returns {Record<string, string>} kosong jika valid */
export function validateRoomForm(form) {
  const errors = {}
  const code = String(form?.code ?? '').trim()
  const name = String(form?.name ?? '').trim()

  if (!form?.ward_id) errors.ward_id = 'Bangsal wajib dipilih.'

  if (!code) errors.code = 'Kode kamar wajib diisi.'
  else if (code.length > ROOM_CODE_MAX_LENGTH) {
    errors.code = `Kode kamar maksimal ${ROOM_CODE_MAX_LENGTH} karakter.`
  }

  if (!name) errors.name = 'Nama kamar wajib diisi.'
  else if (name.length > ROOM_NAME_MAX_LENGTH) {
    errors.name = `Nama kamar maksimal ${ROOM_NAME_MAX_LENGTH} karakter.`
  }

  if (!form?.room_class_id) errors.room_class_id = 'Kelas wajib dipilih.'

  return errors
}

export function toRoomPayload(form) {
  const classId = Number(form?.room_class_id)
  const description = String(form?.description ?? '').trim()
  return {
    ward_id: Number(form?.ward_id),
    code: String(form?.code ?? '').trim(),
    name: String(form?.name ?? '').trim(),
    room_class_id: classId,
    class_id: classId,
    description: description || null,
  }
}

/**
 * Pastikan pesan error API tidak membocorkan detail teknis atau database ke pengguna.
 *
 * @param {{ status?: number|null, message?: string } | null | undefined} normalizedError
 * @param {string} fallbackMessage
 */
export function getSafeRoomErrorMessage(normalizedError, fallbackMessage) {
  const status = normalizedError?.status
  const message = normalizedError?.message?.trim()

  if (!message) return fallbackMessage
  if (typeof status === 'number' && status >= 500) return fallbackMessage
  if (TECHNICAL_ERROR_PATTERN.test(message)) return fallbackMessage

  return message
}
