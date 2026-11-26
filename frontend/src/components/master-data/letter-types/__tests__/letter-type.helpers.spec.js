import { describe, expect, it } from 'vitest'
import {
  canPreviewLegacyResource,
  createLetterTypeForm,
  EMPTY,
  formatLetterTypeDescription,
  formatLetterTypeName,
  formatLetterTypeResource,
  formatLetterTypeSummary,
  getSafeLetterTypeErrorMessage,
  LETTER_TYPE_DESCRIPTION_MAX_LENGTH,
  LETTER_TYPE_NAME_MAX_LENGTH,
  LETTER_TYPE_RESOURCE_MAX_LENGTH,
  resolveLegacyResourceUrl,
  toLetterTypePayload,
  validateLetterTypeForm,
} from '../letter-type.helpers'

describe('letter-type.helpers', () => {
  it('formats name, description, legacy_resource, and summary cleanly', () => {
    expect(formatLetterTypeName({ name: '  Surat Sehat  ' })).toBe('Surat Sehat')
    expect(formatLetterTypeName({ name: '  ' })).toBe(EMPTY)
    expect(formatLetterTypeDescription({ description: 'Untuk keperluan kerja' })).toBe(
      'Untuk keperluan kerja',
    )
    expect(formatLetterTypeDescription({ description: null })).toBe(EMPTY)
    expect(formatLetterTypeResource({ legacy_resource: ' surat_sehat.php ' })).toBe(
      'surat_sehat.php',
    )
    expect(formatLetterTypeResource({ legacy_resource: null })).toBe('')
    expect(formatLetterTypeSummary({ name: 'Surat Rujukan' })).toBe('Surat Rujukan')
    expect(formatLetterTypeSummary(null)).toBe('Surat')
  })

  it('validates required name and 50/255 maxlength limits and builds payload', () => {
    expect(createLetterTypeForm()).toEqual({
      name: '',
      description: '',
      legacy_resource: '',
    })

    expect(validateLetterTypeForm({ name: '   ' })).toEqual({
      name: 'Nama surat wajib diisi.',
    })

    expect(
      validateLetterTypeForm({
        name: 'A'.repeat(LETTER_TYPE_NAME_MAX_LENGTH + 1),
        description: 'B'.repeat(LETTER_TYPE_DESCRIPTION_MAX_LENGTH + 1),
        legacy_resource: 'C'.repeat(LETTER_TYPE_RESOURCE_MAX_LENGTH + 1),
      }),
    ).toEqual({
      name: `Nama surat maksimal ${LETTER_TYPE_NAME_MAX_LENGTH} karakter.`,
      description: `Deskripsi maksimal ${LETTER_TYPE_DESCRIPTION_MAX_LENGTH} karakter.`,
      legacy_resource: `Resource maksimal ${LETTER_TYPE_RESOURCE_MAX_LENGTH} karakter.`,
    })

    expect(
      toLetterTypePayload({
        name: '  Surat Sakit  ',
        description: '   ',
        legacy_resource: ' surat_sakit.php ',
      }),
    ).toEqual({
      name: 'Surat Sakit',
      description: null,
      legacy_resource: 'surat_sakit.php',
    })
  })

  it('resolves safe legacy resource paths/URLs and blocks unsafe schemes', () => {
    expect(resolveLegacyResourceUrl('surat_sehat.php')).toBe('/simrs_lama/surat_sehat.php')
    expect(resolveLegacyResourceUrl('/cetak/surat.php?id=1', 'https://legacy.rs.local/app')).toBe(
      'https://legacy.rs.local/app/cetak/surat.php?id=1',
    )
    expect(resolveLegacyResourceUrl('https://legacy.rs.local/surat.pdf')).toBe(
      'https://legacy.rs.local/surat.pdf',
    )

    expect(canPreviewLegacyResource({ legacy_resource: 'surat_sehat.php' })).toBe(true)
    expect(canPreviewLegacyResource({ legacy_resource: '' })).toBe(false)

    expect(resolveLegacyResourceUrl('javascript:alert(1)')).toBeNull()
    expect(resolveLegacyResourceUrl('  java\tscript:alert(1)  ')).toBeNull()
    expect(resolveLegacyResourceUrl('data:text/html,<script>alert(1)</script>')).toBeNull()
    expect(resolveLegacyResourceUrl('vbscript:msgbox(1)')).toBeNull()
    expect(resolveLegacyResourceUrl('//evil.example.com/phish')).toBeNull()
    expect(resolveLegacyResourceUrl('../secret.php')).toBeNull()
    expect(canPreviewLegacyResource({ legacy_resource: 'javascript:alert(1)' })).toBe(false)
  })

  it('formats 403/404 messages clearly and sanitizes 5xx/SQL errors', () => {
    expect(
      getSafeLetterTypeErrorMessage(
        { status: 403, message: 'This action is unauthorized.' },
        'Fallback',
      ),
    ).toBe('Anda tidak memiliki izin untuk mengakses atau mengubah data surat.')

    expect(
      getSafeLetterTypeErrorMessage(
        { status: 404, message: 'No query results for model [App\\Models\\LetterType] 99' },
        'Fallback',
      ),
    ).toBe('Data surat tidak ditemukan atau sudah dihapus.')

    expect(
      getSafeLetterTypeErrorMessage(
        { status: 500, message: 'SQLSTATE[42S02]: Base table not found' },
        'Data surat gagal dimuat.',
      ),
    ).toBe('Data surat gagal dimuat.')
  })
})
