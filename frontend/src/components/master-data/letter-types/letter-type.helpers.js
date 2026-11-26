/*
 * Fungsi murni modul Surat-Surat (LetterType): format tampilan, factory form,
 * validasi sesuai Store/UpdateLetterTypeRequest, pembentuk payload, validasi aman
 * URL/path `legacy_resource`, serta sanitasi error API termasuk 403/404/422/5xx.
 */

export const EMPTY = '—'
export const LETTER_TYPE_NAME_MAX_LENGTH = 50
export const LETTER_TYPE_DESCRIPTION_MAX_LENGTH = 255
export const LETTER_TYPE_RESOURCE_MAX_LENGTH = 255
export const DEFAULT_LEGACY_BASE_URL = '/simrs_lama/'

const TECHNICAL_ERROR_PATTERN = /sqlstate|syntax error|stack trace|pdoexception|queryexception/i
const SCHEME_PREFIX_PATTERN = /^[a-zA-Z][a-zA-Z0-9+.-]*:/
const BLOCKED_COMPACT_SCHEME_PATTERN = /^(javascript|data|vbscript|file|blob):/i

function hasControlCharacters(value) {
  for (let i = 0; i < value.length; i += 1) {
    const code = value.charCodeAt(i)
    if (code <= 31 || code === 127) return true
  }
  return false
}

/** Format nama jenis surat atau tanda kosong bila null/spasi. */
export function formatLetterTypeName(letterType) {
  return letterType?.name?.trim() || EMPTY
}

/** Format deskripsi surat atau tanda kosong bila null/spasi. */
export function formatLetterTypeDescription(letterType) {
  return letterType?.description?.trim() || EMPTY
}

/** Format resource legacy atau teks kosong bila belum diisi. */
export function formatLetterTypeResource(letterType) {
  return letterType?.legacy_resource?.trim() || ''
}

/** Ringkasan nama untuk dialog konfirmasi hapus dan aria-label. */
export function formatLetterTypeSummary(letterType) {
  return letterType?.name?.trim() || 'Surat'
}

/** Inisialisasi state reaktif form tambah/edit surat. */
export function createLetterTypeForm(letterType = null) {
  return {
    name: letterType?.name ?? '',
    description: letterType?.description ?? '',
    legacy_resource: letterType?.legacy_resource ?? '',
  }
}

/**
 * Validasi client-side form surat sesuai StoreLetterTypeRequest / UpdateLetterTypeRequest:
 * - `name`: wajib, maks 50 karakter
 * - `description`: opsional, maks 255 karakter
 * - `legacy_resource`: opsional, maks 255 karakter
 *
 * @returns {Record<string, string>} kosong jika valid
 */
export function validateLetterTypeForm(form) {
  const errors = {}
  const name = String(form?.name ?? '').trim()
  const description = String(form?.description ?? '').trim()
  const legacyResource = String(form?.legacy_resource ?? '').trim()

  if (!name) {
    errors.name = 'Nama surat wajib diisi.'
  } else if (name.length > LETTER_TYPE_NAME_MAX_LENGTH) {
    errors.name = `Nama surat maksimal ${LETTER_TYPE_NAME_MAX_LENGTH} karakter.`
  }

  if (description.length > LETTER_TYPE_DESCRIPTION_MAX_LENGTH) {
    errors.description = `Deskripsi maksimal ${LETTER_TYPE_DESCRIPTION_MAX_LENGTH} karakter.`
  }

  if (legacyResource.length > LETTER_TYPE_RESOURCE_MAX_LENGTH) {
    errors.legacy_resource = `Resource maksimal ${LETTER_TYPE_RESOURCE_MAX_LENGTH} karakter.`
  }

  return errors
}

/** Bentuk payload bersih untuk endpoint POST/PUT `/master-data/letter-types`. */
export function toLetterTypePayload(form) {
  const description = String(form?.description ?? '').trim()
  const legacyResource = String(form?.legacy_resource ?? '').trim()

  return {
    name: String(form?.name ?? '').trim(),
    description: description || null,
    legacy_resource: legacyResource || null,
  }
}

/**
 * Validasi dan bentuk URL aman untuk aksi "Lihat Format Lama":
 * - Mengizinkan URL absolut `http:` / `https:` yang valid
 * - Mengizinkan path/file relatif sistem lama (mis. `surat_sehat.php`) digabung `baseUrl`
 * - Menolak skema berbahaya (`javascript:`, `data:`, `vbscript:`, dll.), protocol-relative `//`,
 *   karakter kontrol, backslash, maupun traversal `..`
 */
export function resolveLegacyResourceUrl(resource, baseUrl = DEFAULT_LEGACY_BASE_URL) {
  const raw = String(resource ?? '').trim()
  if (!raw || hasControlCharacters(raw) || raw.includes('\\') || raw.startsWith('//')) {
    return null
  }

  const compact = raw.replace(/\s+/g, '')
  if (BLOCKED_COMPACT_SCHEME_PATTERN.test(compact)) {
    return null
  }

  if (SCHEME_PREFIX_PATTERN.test(raw)) {
    try {
      const parsed = new URL(raw)
      if (parsed.protocol === 'http:' || parsed.protocol === 'https:') {
        return parsed.href
      }
    } catch {
      return null
    }
    return null
  }

  if (/(^|\/)\.\.(\/|$)/.test(raw)) {
    return null
  }

  const base = String(baseUrl || DEFAULT_LEGACY_BASE_URL).trim() || DEFAULT_LEGACY_BASE_URL
  if (hasControlCharacters(base) || BLOCKED_COMPACT_SCHEME_PATTERN.test(base.replace(/\s+/g, ''))) {
    return null
  }

  const normalizedBase = base.endsWith('/') ? base : `${base}/`
  const cleanPath = raw.replace(/^\/+/, '')
  return cleanPath ? `${normalizedBase}${cleanPath}` : null
}

/** Menentukan apakah item memiliki `legacy_resource` yang aman untuk dibuka. */
export function canPreviewLegacyResource(letterType, baseUrl = DEFAULT_LEGACY_BASE_URL) {
  return Boolean(resolveLegacyResourceUrl(letterType?.legacy_resource, baseUrl))
}

/**
 * Sanitasi pesan error API agar error 403/404/5xx jelas dan tidak membocorkan SQL/trace ke UI.
 *
 * @param {{ status?: number|null, message?: string } | null | undefined} normalizedError
 * @param {string} fallbackMessage
 */
export function getSafeLetterTypeErrorMessage(normalizedError, fallbackMessage) {
  const status = normalizedError?.status
  const rawMessage = normalizedError?.message?.trim()

  if (status === 403) {
    if (
      !rawMessage ||
      /^this action is unauthorized\.?$/i.test(rawMessage) ||
      TECHNICAL_ERROR_PATTERN.test(rawMessage)
    ) {
      return 'Anda tidak memiliki izin untuk mengakses atau mengubah data surat.'
    }
    return rawMessage
  }

  if (status === 404) {
    if (
      !rawMessage ||
      /^no query results/i.test(rawMessage) ||
      TECHNICAL_ERROR_PATTERN.test(rawMessage)
    ) {
      return 'Data surat tidak ditemukan atau sudah dihapus.'
    }
    return rawMessage
  }

  if (typeof status === 'number' && status >= 500) return fallbackMessage
  if (!rawMessage || TECHNICAL_ERROR_PATTERN.test(rawMessage)) return fallbackMessage

  return rawMessage
}
