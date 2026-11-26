/*
 * Fungsi murni modul Poliklinik: format tampilan tabel & ringkasan, factory form,
 * normalisasi opsi gudang tersimpan, validasi input, pembentuk payload, dan sanitasi error.
 */

export const EMPTY = '—'
export const POLYCLINIC_CODE_MAX_LENGTH = 30
export const POLYCLINIC_NAME_MAX_LENGTH = 150
export const POLYCLINIC_DESCRIPTION_MAX_LENGTH = 255
export const POLYCLINIC_BPJS_CODE_MAX_LENGTH = 50

const TECHNICAL_ERROR_PATTERN = /sqlstate|syntax error|stack trace|pdoexception|queryexception/i

/** Format kode poliklinik atau tanda kosong bila null/spasi. */
export function formatPolyclinicCode(polyclinic) {
  return polyclinic?.code?.trim() || EMPTY
}

/** Format nama poliklinik atau tanda kosong bila null/spasi. */
export function formatPolyclinicName(polyclinic) {
  return polyclinic?.name?.trim() || EMPTY
}

/** Format label jenis layanan poliklinik. */
export function formatPolyclinicServiceType(polyclinic) {
  return polyclinic?.service_type_label || polyclinic?.service_type || EMPTY
}

/** Format nama gudang default poliklinik sesuai konvensi modul Poliklinik. */
export function formatPolyclinicWarehouse(polyclinic) {
  const warehouse = polyclinic?.warehouse
  if (!warehouse?.name) return 'Belum ditentukan'
  return warehouse.code ? `${warehouse.code} — ${warehouse.name}` : warehouse.name
}

/** Format angka kuota agar selalu terbaca rapi. */
export function formatPolyclinicQuota(value) {
  if (value === null || value === undefined || value === '') return '0'
  const numeric = Number(value)
  return Number.isFinite(numeric) ? String(numeric) : '0'
}

/** Ringkasan nama & kode untuk dialog konfirmasi pengarsipan dan aria-label. */
export function formatPolyclinicSummary(polyclinic) {
  const code = polyclinic?.code?.trim()
  const name = polyclinic?.name?.trim()
  if (code && name) return `${code} - ${name}`
  return name || code || 'Poliklinik'
}

/** Pastikan opsi gudang yang sedang tersimpan tetap tampil walau tidak masuk daftar aktif. */
export function ensureSavedWarehouseOptions(warehouses = [], detail = null) {
  const list = Array.isArray(warehouses) ? [...warehouses] : []
  const savedWarehouse = detail?.warehouse
  const savedId = detail?.warehouse_id ?? savedWarehouse?.id

  if (
    savedId &&
    savedWarehouse?.name &&
    !list.some((item) => Number(item.id) === Number(savedId))
  ) {
    list.unshift({
      id: savedId,
      code: savedWarehouse.code ?? '',
      name: savedWarehouse.name,
    })
  }

  return list
}

/** Inisialisasi state reaktif form tambah/edit poliklinik. */
export function createPolyclinicForm(polyclinic = null) {
  return {
    code: polyclinic?.code ?? '',
    name: polyclinic?.name ?? '',
    service_type: polyclinic?.service_type ?? '',
    description: polyclinic?.description ?? '',
    is_visible: polyclinic?.is_visible !== undefined ? Boolean(polyclinic.is_visible) : true,
    is_online_visible:
      polyclinic?.is_online_visible !== undefined ? Boolean(polyclinic.is_online_visible) : false,
    quota: polyclinic?.quota ?? 0,
    jkn_quota: polyclinic?.jkn_quota ?? 0,
    warehouse_id: polyclinic?.warehouse_id ?? '',
    bpjs_code: polyclinic?.bpjs_code ?? '',
  }
}

function parseNonNegativeInteger(value) {
  if (value === '' || value === null || value === undefined) return 0
  const numeric = Number(value)
  if (!Number.isFinite(numeric) || !Number.isInteger(numeric)) return null
  return numeric
}

/**
 * Validasi client-side form poliklinik sesuai StorePolyclinicRequest / UpdatePolyclinicRequest.
 *
 * @returns {Record<string, string>} kosong jika valid
 */
export function validatePolyclinicForm(form) {
  const errors = {}

  const code = String(form?.code ?? '').trim()
  const name = String(form?.name ?? '')
    .trim()
    .replace(/\s+/g, ' ')
  const serviceType = String(form?.service_type ?? '').trim()
  const description = String(form?.description ?? '').trim()
  const bpjsCode = String(form?.bpjs_code ?? '').trim()

  if (!code) errors.code = 'Kode poliklinik wajib diisi.'
  else if (code.length > POLYCLINIC_CODE_MAX_LENGTH) {
    errors.code = `Kode poliklinik maksimal ${POLYCLINIC_CODE_MAX_LENGTH} karakter.`
  }

  if (!name) errors.name = 'Nama poliklinik wajib diisi.'
  else if (name.length > POLYCLINIC_NAME_MAX_LENGTH) {
    errors.name = `Nama poliklinik maksimal ${POLYCLINIC_NAME_MAX_LENGTH} karakter.`
  }

  if (!serviceType) errors.service_type = 'Jenis layanan wajib dipilih.'

  if (description.length > POLYCLINIC_DESCRIPTION_MAX_LENGTH) {
    errors.description = `Deskripsi maksimal ${POLYCLINIC_DESCRIPTION_MAX_LENGTH} karakter.`
  }

  if (bpjsCode.length > POLYCLINIC_BPJS_CODE_MAX_LENGTH) {
    errors.bpjs_code = `Kode BPJS maksimal ${POLYCLINIC_BPJS_CODE_MAX_LENGTH} karakter.`
  }

  const quota = parseNonNegativeInteger(form?.quota)
  if (quota === null || quota < 0) {
    errors.quota = 'Kuota harus berupa bilangan bulat minimal 0.'
  }

  const jknQuota = parseNonNegativeInteger(form?.jkn_quota)
  if (jknQuota === null || jknQuota < 0) {
    errors.jkn_quota = 'Kuota JKN harus berupa bilangan bulat minimal 0.'
  }

  return errors
}

/**
 * Bentuk payload bersih untuk endpoint POST/PUT `/master-data/polyclinics`.
 * Secara eksplisit TIDAK menyertakan `parent_id` maupun `satusehat_code`.
 */
export function toPolyclinicPayload(form) {
  const description = String(form?.description ?? '').trim()
  const bpjsCode = String(form?.bpjs_code ?? '').trim()
  const quota = parseNonNegativeInteger(form?.quota)
  const jknQuota = parseNonNegativeInteger(form?.jkn_quota)
  const rawWarehouse = form?.warehouse_id

  return {
    code: String(form?.code ?? '')
      .trim()
      .toUpperCase(),
    name: String(form?.name ?? '')
      .trim()
      .replace(/\s+/g, ' '),
    service_type: String(form?.service_type ?? '').trim(),
    description: description || null,
    is_visible: Boolean(form?.is_visible),
    is_online_visible: Boolean(form?.is_online_visible),
    quota: quota !== null && quota >= 0 ? quota : 0,
    jkn_quota: jknQuota !== null && jknQuota >= 0 ? jknQuota : 0,
    warehouse_id:
      rawWarehouse === '' || rawWarehouse === null || rawWarehouse === undefined
        ? null
        : rawWarehouse,
    bpjs_code: bpjsCode || null,
  }
}

/**
 * Pastikan pesan error API tidak membocorkan detail SQL/trace ke UI.
 *
 * @param {{ status?: number|null, message?: string } | null | undefined} normalizedError
 * @param {string} fallbackMessage
 */
export function getSafePolyclinicErrorMessage(normalizedError, fallbackMessage) {
  const status = normalizedError?.status
  const message = normalizedError?.message?.trim()

  if (!message) return fallbackMessage
  if (typeof status === 'number' && status >= 500) return fallbackMessage
  if (TECHNICAL_ERROR_PATTERN.test(message)) return fallbackMessage

  return message
}
