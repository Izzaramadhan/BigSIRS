/*
 * Fungsi murni modul Jenis Kegiatan (ActivityType): format tampilan, normalisasi induk,
 * factory form, validasi input sesuai Store/UpdateActivityTypeRequest, pembentuk payload,
 * dan sanitasi pesan error.
 */

export const EMPTY = '—'
export const ACTIVITY_TYPE_NAME_MAX_LENGTH = 100

const TECHNICAL_ERROR_PATTERN = /sqlstate|syntax error|stack trace|pdoexception|queryexception/i

/** Format nama jenis kegiatan atau tanda kosong bila null/spasi. */
export function formatActivityTypeName(activityType) {
  return activityType?.name?.trim() || EMPTY
}

/** Format nama induk jenis kegiatan atau tanda kosong bila tidak memiliki induk. */
export function formatActivityTypeParent(activityType) {
  return activityType?.parent?.name?.trim() || EMPTY
}

/** Ringkasan nama untuk dialog konfirmasi hapus dan aria-label. */
export function formatActivityTypeSummary(activityType) {
  return activityType?.name?.trim() || 'Jenis Kegiatan'
}

/** Menentukan apakah baris jenis kegiatan merupakan turunan (memiliki induk). */
export function hasParentActivityType(activityType) {
  return normalizeActivityTypeParentId(activityType?.parent_id) !== null
}

/** Normalisasi ID induk menjadi number positif atau null. */
export function normalizeActivityTypeParentId(value) {
  if (value === null || value === undefined || value === '') return null
  const num = Number(value)
  return Number.isInteger(num) && num > 0 ? num : null
}

/** Inisialisasi state reaktif form tambah/edit jenis kegiatan. */
export function createActivityTypeForm(activityType = null) {
  return {
    name: activityType?.name ?? '',
    parent_id: normalizeActivityTypeParentId(activityType?.parent_id),
  }
}

/**
 * Validasi client-side form jenis kegiatan sesuai Store/UpdateActivityTypeRequest:
 * - `name`: wajib, maks 100 karakter
 * - `parent_id`: opsional, tidak boleh sama dengan item yang sedang diedit
 *
 * @returns {Record<string, string>} kosong jika valid
 */
export function validateActivityTypeForm(form, editingId = null) {
  const errors = {}
  const name = String(form?.name ?? '').trim()
  const parentId = normalizeActivityTypeParentId(form?.parent_id)
  const currentId = normalizeActivityTypeParentId(editingId)

  if (!name) {
    errors.name = 'Nama jenis kegiatan wajib diisi.'
  } else if (name.length > ACTIVITY_TYPE_NAME_MAX_LENGTH) {
    errors.name = `Nama jenis kegiatan maksimal ${ACTIVITY_TYPE_NAME_MAX_LENGTH} karakter.`
  }

  if (currentId !== null && parentId !== null && parentId === currentId) {
    errors.parent_id = 'Jenis kegiatan tidak boleh menjadi induk untuk dirinya sendiri.'
  }

  return errors
}

/** Bentuk payload bersih untuk endpoint POST/PUT `/master-data/activity-types`. */
export function toActivityTypePayload(form) {
  return {
    name: String(form?.name ?? '').trim(),
    parent_id: normalizeActivityTypeParentId(form?.parent_id),
  }
}

/**
 * Memastikan opsi induk yang tersimpan pada record tetap muncul saat edit walaupun
 * endpoint lookup gagal atau tidak menyertakannya, sekaligus mencegah self-parent.
 */
export function ensureSavedParentOptions(options = [], activityType = null) {
  const selfId = normalizeActivityTypeParentId(activityType?.id)
  const list = Array.isArray(options)
    ? options.filter((opt) => normalizeActivityTypeParentId(opt?.id) !== selfId)
    : []

  const savedParentId = normalizeActivityTypeParentId(activityType?.parent_id)
  if (savedParentId === null || savedParentId === selfId) return list

  const alreadyIncluded = list.some(
    (opt) => normalizeActivityTypeParentId(opt?.id) === savedParentId,
  )
  if (alreadyIncluded) return list

  const fallbackName = activityType?.parent?.name?.trim() || `Induk #${savedParentId}`
  return [{ id: savedParentId, name: fallbackName, original_name: fallbackName }, ...list]
}

/**
 * Pastikan pesan error API (4xx/409/422/5xx) tidak membocorkan detail SQL/trace ke UI.
 *
 * @param {{ status?: number|null, message?: string, fieldErrors?: Record<string, string> } | null | undefined} normalizedError
 * @param {string} fallbackMessage
 */
export function getSafeActivityTypeErrorMessage(normalizedError, fallbackMessage) {
  const status = normalizedError?.status
  if (typeof status === 'number' && status >= 500) return fallbackMessage

  const candidate = (
    normalizedError?.fieldErrors?.id ||
    normalizedError?.message ||
    ''
  ).trim()

  if (!candidate) return fallbackMessage
  if (TECHNICAL_ERROR_PATTERN.test(candidate)) return fallbackMessage

  return candidate
}
