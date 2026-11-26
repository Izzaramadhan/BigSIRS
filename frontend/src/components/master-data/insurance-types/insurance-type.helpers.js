/*
 * Fungsi murni modul Jenis Asuransi / Penjamin: format tampilan tabel & ringkasan,
 * factory form, validasi input sesuai Store/UpdateGuarantorRequest, pembentuk payload
 * (mempertahankan code/type legacy saat Edit), dan sanitasi pesan error.
 */

export const EMPTY = '—'
export const INSURANCE_NAME_MAX_LENGTH = 150
export const INACBG_ID_MAX_LENGTH = 10

const TECHNICAL_ERROR_PATTERN = /sqlstate|syntax error|stack trace|pdoexception|queryexception/i

/** Format nama jenis asuransi atau tanda kosong bila null/spasi. */
export function formatInsuranceName(item) {
  return item?.name?.trim() || EMPTY
}

/** Format status Asuransi Pemerintah (`Ya` / `Tidak`). */
export function formatInsuranceGovernment(item) {
  return item?.is_government ? 'Ya' : 'Tidak'
}

/** Format ID INACBG dengan fallback `—` bila null atau string kosong. */
export function formatInacbgId(item) {
  const raw = item?.inacbg_id
  if (raw === null || raw === undefined) return EMPTY
  const text = String(raw).trim()
  return text || EMPTY
}

/** Ringkasan nama untuk dialog konfirmasi hapus dan aria-label. */
export function formatInsuranceSummary(item) {
  return item?.name?.trim() || 'Jenis Asuransi'
}

/** Inisialisasi state reaktif form tambah/edit jenis asuransi. */
export function createInsuranceTypeForm(insuranceType = null) {
  const rawInacbg = insuranceType?.inacbg_id
  return {
    name: insuranceType?.name ?? '',
    is_government: Boolean(insuranceType?.is_government),
    inacbg_id:
      rawInacbg !== null && rawInacbg !== undefined ? String(rawInacbg) : '',
  }
}

/**
 * Validasi client-side form jenis asuransi sesuai StoreGuarantorRequest / UpdateGuarantorRequest:
 * - `name`: wajib, maks 150 karakter
 * - `is_government`: wajib boolean
 * - `inacbg_id`: opsional, maks 10 karakter
 *
 * @returns {Record<string, string>} kosong jika valid
 */
export function validateInsuranceTypeForm(form) {
  const errors = {}
  const name = String(form?.name ?? '')
    .trim()
    .replace(/\s+/g, ' ')
  const inacbgId = String(form?.inacbg_id ?? '').trim()

  if (!name) errors.name = 'Nama jenis asuransi wajib diisi.'
  else if (name.length > INSURANCE_NAME_MAX_LENGTH) {
    errors.name = `Nama jenis asuransi maksimal ${INSURANCE_NAME_MAX_LENGTH} karakter.`
  }

  if (typeof form?.is_government !== 'boolean') {
    errors.is_government = 'Status asuransi pemerintah wajib dipilih.'
  }

  if (inacbgId.length > INACBG_ID_MAX_LENGTH) {
    errors.inacbg_id = `ID INACBG maksimal ${INACBG_ID_MAX_LENGTH} karakter.`
  }

  return errors
}

/**
 * Bentuk payload bersih untuk endpoint POST/PUT `/master-data/guarantors`.
 * Pada mode Edit, mempertahankan `code` dan `type` yang tersimpan bila ada agar
 * data legacy tidak ter-override nilai default baru.
 */
export function toInsuranceTypePayload(form, existingInsuranceType = null) {
  const inacbgId = String(form?.inacbg_id ?? '').trim()
  const payload = {
    name: String(form?.name ?? '')
      .trim()
      .replace(/\s+/g, ' '),
    is_government: Boolean(form?.is_government),
    inacbg_id: inacbgId || null,
  }

  if (existingInsuranceType) {
    if (existingInsuranceType.code) payload.code = existingInsuranceType.code
    if (existingInsuranceType.type) payload.type = existingInsuranceType.type
  }

  return payload
}

/**
 * Pastikan pesan error API (4xx/409/422/5xx) tidak membocorkan detail SQL/trace ke UI.
 *
 * @param {{ status?: number|null, message?: string } | null | undefined} normalizedError
 * @param {string} fallbackMessage
 */
export function getSafeInsuranceErrorMessage(normalizedError, fallbackMessage) {
  const status = normalizedError?.status
  const message = normalizedError?.message?.trim()

  if (!message) return fallbackMessage
  if (typeof status === 'number' && status >= 500) return fallbackMessage
  if (TECHNICAL_ERROR_PATTERN.test(message)) return fallbackMessage

  return message
}
