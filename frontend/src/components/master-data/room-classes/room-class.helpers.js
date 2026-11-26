/*
 * Fungsi murni modul Kelas (Room Class): format tampilan Rupiah/kode/deskripsi,
 * input harga, validasi form sesuai backend, payload builder, dan sanitasi pesan error.
 * Tanpa Vue, tanpa API — mudah diuji secara terisolasi.
 */

export const EMPTY = '—'
export const ROOM_CLASS_NAME_MAX_LENGTH = 255
export const ROOM_CLASS_CODE_MAX_LENGTH = 50
export const ROOM_CLASS_DESCRIPTION_MAX_LENGTH = 1000
export const ROOM_CLASS_APLICARE_CODE_MAX_LENGTH = 50
export const ROOM_CLASS_SIRS_CODE_MAX_LENGTH = 50

const TECHNICAL_ERROR_PATTERN = /sqlstate|syntax error|stack trace|pdoexception|queryexception/i

/** Format harga kelas ke format Rupiah Indonesia ("Rp150.000", default "Rp0"). */
export function formatRoomClassPrice(roomClass) {
  const raw = roomClass?.price
  if (raw === null || raw === undefined || raw === '') return 'Rp0'
  const num = Number(raw)
  if (!Number.isFinite(num)) return 'Rp0'
  return `Rp${num.toLocaleString('id-ID')}`
}

/** Format deskripsi kelas atau tanda kosong bila null/spasi. */
export function formatRoomClassDescription(roomClass) {
  return roomClass?.description?.trim() || EMPTY
}

/** Format kode eksternal (BPJS/Aplicare atau SIRS) atau tanda kosong. */
export function formatRoomClassExternalCode(value) {
  if (value === null || value === undefined) return EMPTY
  return String(value).trim() || EMPTY
}

/** Format input angka mentah menjadi string ribuan Indonesia (mis. "150000" -> "150.000"). */
export function formatPriceDigits(value) {
  const digits = String(value ?? '').replace(/\D/g, '')
  if (!digits) return ''
  return Number(digits).toLocaleString('id-ID')
}

function formatInitialPrice(raw) {
  if (raw === null || raw === undefined || raw === '') return ''
  if (typeof raw === 'number') {
    return Number.isFinite(raw) ? Math.max(0, Math.round(raw)).toLocaleString('id-ID') : ''
  }
  const trimmed = String(raw).trim()
  if (/^\d+(\.\d+)?$/.test(trimmed)) {
    const num = Number(trimmed)
    return Number.isFinite(num) ? Math.round(num).toLocaleString('id-ID') : ''
  }
  return formatPriceDigits(trimmed)
}

/** Ubah string input harga terformat ("150.000") menjadi angka bulat atau null bila kosong. */
export function parseRoomClassPrice(value) {
  if (value === null || value === undefined) return null
  const digits = String(value).replace(/\D/g, '')
  if (!digits) return null
  const parsed = Number(digits)
  return Number.isFinite(parsed) ? parsed : null
}

/** Inisialisasi state reaktif form tambah/edit kelas. */
export function createRoomClassForm(roomClass = null) {
  return {
    name: roomClass?.name ?? '',
    code: roomClass?.code ?? '',
    price: formatInitialPrice(roomClass?.price),
    description: roomClass?.description ?? '',
    aplicare_code: roomClass?.aplicare_code ?? '',
    sirs_code: roomClass?.sirs_code ?? '',
  }
}

/**
 * Validasi form kelas sebelum dikirim ke server (diselaraskan dengan Store/UpdateRoomClassRequest).
 *
 * @returns {Record<string, string>} kosong jika valid
 */
export function validateRoomClassForm(form) {
  const errors = {}
  const name = String(form?.name ?? '')
    .trim()
    .replace(/\s+/g, ' ')
  const code = String(form?.code ?? '')
    .trim()
    .replace(/\s+/g, ' ')
  const price = parseRoomClassPrice(form?.price)
  const description = String(form?.description ?? '').trim()
  const aplicareCode = String(form?.aplicare_code ?? '').trim()
  const sirsCode = String(form?.sirs_code ?? '').trim()

  if (!name) errors.name = 'Nama kelas wajib diisi.'
  else if (name.length > ROOM_CLASS_NAME_MAX_LENGTH) {
    errors.name = `Nama kelas maksimal ${ROOM_CLASS_NAME_MAX_LENGTH} karakter.`
  }

  if (!code) errors.code = 'Kode kelas wajib diisi.'
  else if (code.length > ROOM_CLASS_CODE_MAX_LENGTH) {
    errors.code = `Kode kelas maksimal ${ROOM_CLASS_CODE_MAX_LENGTH} karakter.`
  }

  if (price === null) errors.price = 'Harga kelas wajib diisi.'
  else if (price < 0) errors.price = 'Harga kelas tidak boleh bernilai negatif.'

  if (description.length > ROOM_CLASS_DESCRIPTION_MAX_LENGTH) {
    errors.description = `Deskripsi kelas maksimal ${ROOM_CLASS_DESCRIPTION_MAX_LENGTH} karakter.`
  }

  if (aplicareCode.length > ROOM_CLASS_APLICARE_CODE_MAX_LENGTH) {
    errors.aplicare_code = `Kode Aplicare maksimal ${ROOM_CLASS_APLICARE_CODE_MAX_LENGTH} karakter.`
  }

  if (sirsCode.length > ROOM_CLASS_SIRS_CODE_MAX_LENGTH) {
    errors.sirs_code = `Kode SIRS maksimal ${ROOM_CLASS_SIRS_CODE_MAX_LENGTH} karakter.`
  }

  return errors
}

/** Bentuk payload bersih untuk endpoint POST/PUT `/master-data/room-classes`. */
export function toRoomClassPayload(form) {
  const name = String(form?.name ?? '')
    .trim()
    .replace(/\s+/g, ' ')
  const code = String(form?.code ?? '')
    .trim()
    .replace(/\s+/g, ' ')
  const price = parseRoomClassPrice(form?.price)
  const description = String(form?.description ?? '').trim()
  const aplicareCode = String(form?.aplicare_code ?? '').trim()
  const sirsCode = String(form?.sirs_code ?? '').trim()

  return {
    name,
    code,
    price: price ?? 0,
    description: description || null,
    aplicare_code: aplicareCode || null,
    sirs_code: sirsCode || null,
  }
}

/**
 * Pastikan pesan error API tidak membocorkan detail teknis atau database ke pengguna.
 *
 * @param {{ status?: number|null, message?: string } | null | undefined} normalizedError
 * @param {string} fallbackMessage
 */
export function getSafeRoomClassErrorMessage(normalizedError, fallbackMessage) {
  const status = normalizedError?.status
  const message = normalizedError?.message?.trim()

  if (!message) return fallbackMessage
  if (typeof status === 'number' && status >= 500) return fallbackMessage
  if (TECHNICAL_ERROR_PATTERN.test(message)) return fallbackMessage

  return message
}
