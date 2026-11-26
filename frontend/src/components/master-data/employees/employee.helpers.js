/*
 * Fungsi murni modul Pegawai: format tampilan, resolusi ID jabatan (legacy vs internal),
 * factory form, validasi input & file tanda tangan, pembentuk payload, dan sanitasi error.
 */

export const EMPTY = '—'

export const EMPLOYEE_NATIONAL_ID_MAX_LENGTH = 20
export const EMPLOYEE_NAME_MAX_LENGTH = 255
export const EMPLOYEE_CODE_MAX_LENGTH = 255
export const EMPLOYEE_POSTAL_CODE_MAX_LENGTH = 10
export const EMPLOYEE_PHONE_MAX_LENGTH = 20
export const SIGNATURE_MAX_BYTES = 2 * 1024 * 1024
export const SIGNATURE_ALLOWED_TYPES = ['image/png', 'image/jpeg', 'image/jpg']

export const BLOOD_TYPE_OPTIONS = ['Unknown', 'A', 'B', 'AB', 'O']
export const RELIGION_OPTIONS = ['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Khonghucu']
export const MARITAL_STATUS_OPTIONS = ['Belum Kawin', 'Kawin', 'Cerai Hidup', 'Cerai Mati']
export const NATIONALITY_OPTIONS = ['WNI', 'WNA']

const TECHNICAL_ERROR_PATTERN = /sqlstate|syntax error|stack trace|pdoexception|queryexception/i
const SIGNATURE_EXTENSION_PATTERN = /\.(png|jpe?g)$/i

/** Format NIP / ID pegawai atau tanda kosong bila null/spasi. */
export function formatEmployeeCode(employee) {
  const value = String(employee?.code ?? employee?.nip ?? '').trim()
  return value || EMPTY
}

/** Format nama pegawai atau tanda kosong bila null/spasi. */
export function formatEmployeeName(employee) {
  const value = String(employee?.name ?? employee?.nama ?? employee?.person?.name ?? '').trim()
  return value || EMPTY
}

/** Format jabatan pegawai atau tanda kosong bila belum ada jabatan. */
export function formatEmployeePosition(employee) {
  const value = String(employee?.position?.name ?? employee?.position_name ?? '').trim()
  return value || EMPTY
}

/** Label singkat pegawai untuk aria-label tombol dan dialog konfirmasi hapus. */
export function resolveEmployeeDisplayName(employee) {
  const name = String(employee?.name ?? employee?.nama ?? '').trim()
  if (name) return name
  const code = String(employee?.code ?? employee?.nip ?? '').trim()
  return code || 'pegawai ini'
}

function coerceSelectId(value) {
  if (value === null || value === undefined || value === '') return ''
  const num = Number(value)
  return Number.isNaN(num) ? String(value) : num
}

/**
 * Normalisasi tanggal lahir ke format `YYYY-MM-DD` untuk input `<input type="date">`.
 */
export function formatBirthDateForInput(value) {
  if (!value) return ''
  const text = String(value).trim()
  if (!text) return ''
  const isoMatch = text.match(/^(\d{4}-\d{2}-\d{2})/)
  return isoMatch ? isoMatch[1] : ''
}

/**
 * Resolusi `position_id` pegawai dari detail API (internal DB maupun legacy `ref_pegawai.id_jabatan`).
 * Jika daftar opsi jabatan tersedia, mencocokkan berdasarkan `opt.id` lalu fallback `opt.legacy_id`.
 */
export function resolveEmployeePositionId(data, positionOptions = []) {
  if (!data) return ''
  const rawId = data.position_id ?? data.id_jabatan ?? data.position?.id ?? ''
  if (rawId === null || rawId === undefined || rawId === '') return ''

  if (Array.isArray(positionOptions) && positionOptions.length > 0) {
    const byId = positionOptions.find((opt) => String(opt.id) === String(rawId))
    if (byId) return coerceSelectId(byId.id)

    const byLegacyId = positionOptions.find(
      (opt) => opt.legacy_id !== null && opt.legacy_id !== undefined && String(opt.legacy_id) === String(rawId),
    )
    if (byLegacyId) return coerceSelectId(byLegacyId.id)
  }

  return coerceSelectId(rawId)
}

/** Inisialisasi state form tambah/edit pegawai dari response API maupun fallback relasi `person`. */
export function createEmployeeForm(detail = null, positionOptions = []) {
  const person = detail?.person ?? {}
  return {
    national_id: detail?.national_id ?? person.national_id ?? person.no_ktp ?? '',
    name: detail?.name ?? detail?.nama ?? person.name ?? person.nama ?? '',
    birth_place: detail?.birth_place ?? person.tmp_lahir ?? '',
    birth_date: formatBirthDateForInput(detail?.birth_date ?? person.tgl_lahir),
    blood_type: detail?.blood_type ?? person.gol_darah ?? '',
    religion: detail?.religion ?? person.agama ?? '',
    marital_status: detail?.marital_status ?? person.stts_nikah ?? '',
    nationality: detail?.nationality ?? person.kewarganegaraan ?? 'WNI',
    allergies: detail?.allergies ?? person.alergi ?? '',
    education_id: coerceSelectId(detail?.education_id ?? person.id_pendidikan),
    occupation_id: coerceSelectId(detail?.occupation_id ?? person.id_pekerjaan),
    address: detail?.address ?? person.alamat ?? '',
    province_id: coerceSelectId(detail?.province_id ?? person.id_prov),
    regency_id: coerceSelectId(detail?.regency_id ?? person.id_kab),
    district_id: coerceSelectId(detail?.district_id ?? person.id_kec),
    village_id: coerceSelectId(detail?.village_id ?? person.id_kel),
    postal_code: detail?.postal_code ?? person.kode_pos ?? '',
    phone: detail?.phone ?? person.phone ?? person.no_hp ?? '',
    code: detail?.code ?? detail?.nip ?? '',
    position_id: resolveEmployeePositionId(detail, positionOptions),
    signature: null,
    signature_url: detail?.signature_url ?? '',
    remove_signature: false,
  }
}

/** Validasi file gambar tanda tangan (PNG/JPG/JPEG, maksimal 2MB). */
export function validateSignatureFile(file) {
  if (!file) return ''
  const mime = String(file.type ?? '').toLowerCase()
  const name = String(file.name ?? '')
  const validMime = mime ? SIGNATURE_ALLOWED_TYPES.includes(mime) : false
  const validExt = SIGNATURE_EXTENSION_PATTERN.test(name)

  if (!validMime && !(mime === '' && validExt)) {
    return 'Format file tanda tangan harus PNG atau JPG.'
  }
  if (typeof file.size === 'number' && file.size > SIGNATURE_MAX_BYTES) {
    return 'Ukuran file tanda tangan maksimal 2MB.'
  }
  return ''
}

/**
 * Validasi client-side form pegawai sebelum submit ke API.
 *
 * @returns {Record<string, string>} kosong jika valid
 */
export function validateEmployeeForm(form) {
  const errors = {}
  const nationalId = String(form?.national_id ?? '').trim()
  const name = String(form?.name ?? '')
    .trim()
    .replace(/\s+/g, ' ')
  const code = String(form?.code ?? '').trim()
  const postalCode = String(form?.postal_code ?? '').trim()
  const phone = String(form?.phone ?? '').trim()

  if (!nationalId) {
    errors.national_id = 'Nomor KTP (NIK) wajib diisi.'
  } else if (nationalId.length > EMPLOYEE_NATIONAL_ID_MAX_LENGTH) {
    errors.national_id = `Nomor KTP (NIK) maksimal ${EMPLOYEE_NATIONAL_ID_MAX_LENGTH} karakter.`
  }

  if (!name) {
    errors.name = 'Nama lengkap wajib diisi.'
  } else if (name.length > EMPLOYEE_NAME_MAX_LENGTH) {
    errors.name = `Nama lengkap maksimal ${EMPLOYEE_NAME_MAX_LENGTH} karakter.`
  }

  if (!code) {
    errors.code = 'NIP wajib diisi.'
  } else if (code.length > EMPLOYEE_CODE_MAX_LENGTH) {
    errors.code = `NIP maksimal ${EMPLOYEE_CODE_MAX_LENGTH} karakter.`
  }

  if (form?.position_id === '' || form?.position_id === null || form?.position_id === undefined) {
    errors.position_id = 'Jabatan wajib dipilih.'
  }

  if (postalCode.length > EMPLOYEE_POSTAL_CODE_MAX_LENGTH) {
    errors.postal_code = `Kode pos maksimal ${EMPLOYEE_POSTAL_CODE_MAX_LENGTH} karakter.`
  }

  if (phone.length > EMPLOYEE_PHONE_MAX_LENGTH) {
    errors.phone = `Nomor telepon maksimal ${EMPLOYEE_PHONE_MAX_LENGTH} karakter.`
  }

  if (form?.signature) {
    const signatureError = validateSignatureFile(form.signature)
    if (signatureError) errors.signature = signatureError
  }

  return errors
}

/** Bentuk payload bersih untuk endpoint POST/PUT `/master-data/employees`. */
export function toEmployeePayload(form) {
  return {
    national_id: String(form?.national_id ?? '').trim(),
    name: String(form?.name ?? '')
      .trim()
      .replace(/\s+/g, ' '),
    birth_place: String(form?.birth_place ?? '').trim(),
    birth_date: form?.birth_date || '',
    blood_type: form?.blood_type || '',
    religion: form?.religion || '',
    marital_status: form?.marital_status || '',
    nationality: form?.nationality || 'WNI',
    allergies: String(form?.allergies ?? '').trim(),
    education_id: form?.education_id ?? '',
    occupation_id: form?.occupation_id ?? '',
    address: String(form?.address ?? '').trim(),
    province_id: form?.province_id ?? '',
    regency_id: form?.regency_id ?? '',
    district_id: form?.district_id ?? '',
    village_id: form?.village_id ?? '',
    postal_code: String(form?.postal_code ?? '').trim(),
    phone: String(form?.phone ?? '').trim(),
    code: String(form?.code ?? '').trim(),
    position_id: form?.position_id ?? '',
    signature: form?.signature ?? null,
    remove_signature: Boolean(form?.remove_signature),
  }
}

/**
 * Pastikan pesan error API (4xx/409/422/5xx) tidak membocorkan detail teknis atau SQL.
 */
export function getSafeEmployeeErrorMessage(normalizedError, fallbackMessage) {
  const status = normalizedError?.status
  const message = normalizedError?.message?.trim()

  if (!message) return fallbackMessage
  if (typeof status === 'number' && status >= 500) return fallbackMessage
  if (TECHNICAL_ERROR_PATTERN.test(message)) return fallbackMessage

  return message
}
