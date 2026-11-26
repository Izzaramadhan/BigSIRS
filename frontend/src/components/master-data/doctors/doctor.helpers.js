/*
 * Fungsi murni modul Dokter: format tampilan, resolusi ID spesialisasi/relasi pegawai,
 * factory form, validasi input & file tanda tangan, pembentuk payload nested, dan sanitasi error.
 */

export const EMPTY = '—'

export const DOCTOR_NATIONAL_ID_MAX_LENGTH = 20
export const DOCTOR_NAME_MAX_LENGTH = 255
export const DOCTOR_IHS_MAX_LENGTH = 255
export const DOCTOR_POSTAL_CODE_MAX_LENGTH = 10
export const DOCTOR_PHONE_MAX_LENGTH = 20
export const DOCTOR_STR_MAX_LENGTH = 50
export const DOCTOR_SIP_MAX_LENGTH = 255
export const DOCTOR_DPJP_MAX_LENGTH = 50
export const SIGNATURE_MAX_BYTES = 2 * 1024 * 1024
export const SIGNATURE_ALLOWED_TYPES = ['image/png', 'image/jpeg', 'image/jpg']

export const GENDER_OPTIONS = [
  { value: 'L', label: 'Laki-laki' },
  { value: 'P', label: 'Perempuan' },
]
export const BLOOD_TYPE_OPTIONS = ['Unknown', 'A', 'B', 'AB', 'O']
export const RELIGION_OPTIONS = ['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Khonghucu']
export const MARITAL_STATUS_OPTIONS = ['Belum Kawin', 'Kawin', 'Cerai Hidup', 'Cerai Mati']
export const NATIONALITY_OPTIONS = ['WNI', 'WNA']

const TECHNICAL_ERROR_PATTERN = /sqlstate|syntax error|stack trace|pdoexception|queryexception/i
const SIGNATURE_EXTENSION_PATTERN = /\.(png|jpe?g)$/i

/** Format nama dokter atau tanda kosong bila null/spasi. */
export function formatDoctorName(doctor) {
  const value = String(
    doctor?.name ??
      doctor?.full_name ??
      doctor?.employee?.name ??
      doctor?.person?.name ??
      doctor?.person?.nama ??
      '',
  ).trim()
  return value || EMPTY
}

/** Format nama spesialisasi dokter atau tanda kosong bila belum ada. */
export function formatDoctorSpecialization(doctor) {
  const raw =
    typeof doctor?.specialization === 'string'
      ? doctor.specialization
      : doctor?.specialization?.name ?? doctor?.specialization_name ?? ''
  const value = String(raw).trim()
  return value || EMPTY
}

/** Format label jenis kelamin dari kode backend (`L`/`P`). */
export function formatDoctorGender(gender) {
  if (gender === 'L') return 'Laki-laki'
  if (gender === 'P') return 'Perempuan'
  return EMPTY
}

/** Format tanggal ke format lokal Indonesia atau tanda kosong jika invalid/kosong. */
export function formatDoctorDate(dateString) {
  if (!dateString) return EMPTY
  const date = new Date(dateString)
  if (Number.isNaN(date.getTime())) return String(dateString)
  return new Intl.DateTimeFormat('id-ID', {
    day: '2-digit',
    month: 'long',
    year: 'numeric',
  }).format(date)
}

/** Format teks tanggal ISO ke `YYYY-MM-DD` untuk kontrol `<input type="date">`. */
export function formatDateForInput(value) {
  if (!value) return ''
  const text = String(value).trim()
  if (!text) return ''
  const match = text.match(/^(\d{4}-\d{2}-\d{2})/)
  return match ? match[1] : ''
}

function coerceSelectId(value) {
  if (value === null || value === undefined || value === '') return ''
  const num = Number(value)
  return Number.isNaN(num) ? String(value) : num
}

/**
 * Resolusi `specialization_id` dari resource internal maupun legacy `id_spesialisasi` / `legacy_id`.
 */
export function resolveDoctorSpecializationId(detail, specializationOptions = []) {
  if (!detail) return ''
  const rawId =
    detail.specialization_id ??
    detail.id_spesialisasi ??
    detail.professional?.specialization_id ??
    detail.specialization?.id ??
    ''
  if (rawId === null || rawId === undefined || rawId === '') return ''

  if (Array.isArray(specializationOptions) && specializationOptions.length > 0) {
    const byId = specializationOptions.find((opt) => String(opt.id) === String(rawId))
    if (byId) return coerceSelectId(byId.id)

    const byLegacyId = specializationOptions.find(
      (opt) =>
        opt.legacy_id !== null &&
        opt.legacy_id !== undefined &&
        String(opt.legacy_id) === String(rawId),
    )
    if (byLegacyId) return coerceSelectId(byLegacyId.id)
  }

  return coerceSelectId(rawId)
}

/** Ekstrak struktur biodata person/employee dari detail dokter internal maupun legacy. */
export function extractDoctorPerson(detail) {
  if (!detail) return {}
  return detail.person || detail.employee?.person || detail.employee || {}
}

/** Periksa apakah objek dokter sudah membawa payload detail penuh (bukan row tabel ringkas). */
export function hasDoctorDetailPayload(doctor) {
  if (!doctor || typeof doctor !== 'object') return false
  return Boolean(
    doctor.person ||
      doctor.employee ||
      doctor.professional ||
      'str_number' in doctor ||
      'sip_number' in doctor ||
      'employee_id' in doctor,
  )
}

/** Inisialisasi state form dokter (tambah maupun edit) tanpa menghilangkan field existing. */
export function createDoctorForm(detail = null, specializationOptions = []) {
  const person = extractDoctorPerson(detail)
  const professional = detail?.professional ?? detail ?? {}

  return {
    employee_id: detail?.employee_id ?? detail?.employee?.id ?? null,
    person: {
      national_id: person.national_id ?? person.no_ktp ?? '',
      name: person.name ?? person.nama ?? detail?.name ?? '',
      ihs_number: person.ihs_number ?? detail?.ihs_number ?? '',
      birth_place: person.birth_place ?? person.tmp_lahir ?? person.tempat_lahir ?? '',
      birth_date: formatDateForInput(person.birth_date ?? person.tgl_lahir),
      gender: person.gender ?? '',
      nationality: person.nationality ?? person.kewarganegaraan ?? 'WNI',
      blood_type: person.blood_type ?? person.gol_darah ?? '',
      allergies: person.allergies ?? person.alergi ?? '',
      religion: person.religion ?? person.agama ?? '',
      marital_status: person.marital_status ?? person.stts_nikah ?? '',
      address: person.address ?? person.alamat ?? '',
      postal_code: person.postal_code ?? person.kode_pos ?? '',
      province_id: coerceSelectId(person.province_id ?? person.id_prov),
      regency_id: coerceSelectId(person.regency_id ?? person.id_kab),
      district_id: coerceSelectId(person.district_id ?? person.id_kec),
      village_id: coerceSelectId(person.village_id ?? person.id_kel),
      phone: person.phone ?? person.no_hp ?? '',
      education_id: coerceSelectId(person.education_id ?? person.id_pendidikan),
      occupation_id: coerceSelectId(person.occupation_id ?? person.id_pekerjaan),
    },
    professional: {
      specialization_id: resolveDoctorSpecializationId(detail, specializationOptions),
      str_number: professional.str_number ?? detail?.no_str ?? '',
      sip_number: professional.sip_number ?? detail?.sip ?? '',
      sip_valid_until: formatDateForInput(
        professional.sip_valid_until ?? detail?.masa_berlaku,
      ),
      bpjs_dpjp_code: professional.bpjs_dpjp_code ?? detail?.kode_dpjp ?? '',
      is_active: professional.is_active !== false && detail?.is_active !== false,
    },
    signature: null,
    signature_url: detail?.signature_url ?? '',
    remove_signature: false,
  }
}

/** Validasi file gambar tanda tangan dokter (PNG/JPG/JPEG, maksimal 2MB). */
export function validateDoctorSignatureFile(file) {
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
 * Validasi client-side form dokter sesuai Store/UpdateDoctorRequest.
 * Keys mengikuti konvensi Laravel nested (`person.name`, `person.gender`, `professional.specialization_id`).
 *
 * @returns {Record<string, string>} kosong jika valid
 */
export function validateDoctorForm(form) {
  const errors = {}
  const person = form?.person ?? {}
  const professional = form?.professional ?? {}

  const nationalId = String(person.national_id ?? '').trim()
  const name = String(person.name ?? '')
    .trim()
    .replace(/\s+/g, ' ')
  const gender = String(person.gender ?? '').trim()
  const postalCode = String(person.postal_code ?? '').trim()
  const phone = String(person.phone ?? '').trim()
  const strNumber = String(professional.str_number ?? '').trim()
  const sipNumber = String(professional.sip_number ?? '').trim()
  const dpjpCode = String(professional.bpjs_dpjp_code ?? '').trim()

  if (nationalId.length > DOCTOR_NATIONAL_ID_MAX_LENGTH) {
    errors['person.national_id'] = `Nomor KTP (NIK) maksimal ${DOCTOR_NATIONAL_ID_MAX_LENGTH} karakter.`
  }

  if (!name) {
    errors['person.name'] = 'Nama lengkap wajib diisi.'
  } else if (name.length > DOCTOR_NAME_MAX_LENGTH) {
    errors['person.name'] = `Nama lengkap maksimal ${DOCTOR_NAME_MAX_LENGTH} karakter.`
  }

  if (!['L', 'P'].includes(gender)) {
    errors['person.gender'] = 'Jenis kelamin wajib dipilih.'
  }

  if (postalCode.length > DOCTOR_POSTAL_CODE_MAX_LENGTH) {
    errors['person.postal_code'] = `Kode pos maksimal ${DOCTOR_POSTAL_CODE_MAX_LENGTH} karakter.`
  }

  if (phone.length > DOCTOR_PHONE_MAX_LENGTH) {
    errors['person.phone'] = `Nomor HP maksimal ${DOCTOR_PHONE_MAX_LENGTH} karakter.`
  }

  if (
    professional.specialization_id === '' ||
    professional.specialization_id === null ||
    professional.specialization_id === undefined
  ) {
    errors['professional.specialization_id'] = 'Spesialisasi wajib dipilih.'
  }

  if (strNumber.length > DOCTOR_STR_MAX_LENGTH) {
    errors['professional.str_number'] = `Nomor STR maksimal ${DOCTOR_STR_MAX_LENGTH} karakter.`
  }

  if (sipNumber.length > DOCTOR_SIP_MAX_LENGTH) {
    errors['professional.sip_number'] = `Nomor SIP maksimal ${DOCTOR_SIP_MAX_LENGTH} karakter.`
  }

  if (dpjpCode.length > DOCTOR_DPJP_MAX_LENGTH) {
    errors['professional.bpjs_dpjp_code'] =
      `Kode BPJS DPJP maksimal ${DOCTOR_DPJP_MAX_LENGTH} karakter.`
  }

  if (form?.signature) {
    const signatureError = validateDoctorSignatureFile(form.signature)
    if (signatureError) errors.signature = signatureError
  }

  return errors
}

const normalizeNullableString = (val) => {
  if (val === null || val === undefined) return null
  const text = String(val).trim()
  return text === '' ? null : text
}

const toNullableNumber = (val) => {
  if (val === null || val === undefined || val === '') return null
  const num = Number(val)
  return Number.isNaN(num) ? null : num
}

/** Bentuk payload terstruktur `person` + `professional` + file tanda tangan untuk POST/PUT dokter. */
export function toDoctorPayload(form) {
  const person = form?.person ?? {}
  const professional = form?.professional ?? {}

  return {
    employee_id: toNullableNumber(form?.employee_id),
    person: {
      national_id: normalizeNullableString(person.national_id),
      name: normalizeNullableString(String(person.name ?? '').replace(/\s+/g, ' ')),
      ihs_number: normalizeNullableString(person.ihs_number),
      birth_place: normalizeNullableString(person.birth_place),
      birth_date: person.birth_date || null,
      gender: person.gender || null,
      nationality: person.nationality || null,
      blood_type: person.blood_type || null,
      allergies: normalizeNullableString(person.allergies),
      religion: person.religion || null,
      marital_status: person.marital_status || null,
      address: normalizeNullableString(person.address),
      postal_code: normalizeNullableString(person.postal_code),
      province_id: toNullableNumber(person.province_id),
      regency_id: toNullableNumber(person.regency_id),
      district_id: toNullableNumber(person.district_id),
      village_id: toNullableNumber(person.village_id),
      phone: normalizeNullableString(person.phone),
      education_id: toNullableNumber(person.education_id),
      occupation_id: toNullableNumber(person.occupation_id),
    },
    professional: {
      specialization_id: toNullableNumber(professional.specialization_id),
      str_number: normalizeNullableString(professional.str_number),
      sip_number: normalizeNullableString(professional.sip_number),
      sip_valid_until: professional.sip_valid_until || null,
      bpjs_dpjp_code: normalizeNullableString(professional.bpjs_dpjp_code),
      is_active: professional.is_active !== false,
    },
    signature: form?.signature ?? null,
    remove_signature: Boolean(form?.remove_signature),
  }
}

/** Ambil pesan error field dari map flat maupun nested (`person.*` / `professional.*`). */
export function resolveDoctorFieldError(errorsMap, ...keys) {
  for (const key of keys) {
    const val = errorsMap?.[key]
    if (Array.isArray(val) && val.length > 0) return String(val[0])
    if (typeof val === 'string' && val.trim() !== '') return val
  }
  return ''
}

/**
 * Pastikan pesan error API (4xx/409/422/5xx) tidak membocorkan detail teknis atau SQL.
 */
export function getSafeDoctorErrorMessage(normalizedError, fallbackMessage) {
  const status = normalizedError?.status
  const message = normalizedError?.message?.trim()

  if (!message) return fallbackMessage
  if (typeof status === 'number' && status >= 500) return fallbackMessage
  if (TECHNICAL_ERROR_PATTERN.test(message)) return fallbackMessage

  return message
}
