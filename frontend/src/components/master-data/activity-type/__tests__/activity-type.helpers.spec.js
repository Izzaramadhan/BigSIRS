import { describe, expect, it } from 'vitest'
import {
  ACTIVITY_TYPE_NAME_MAX_LENGTH,
  createActivityTypeForm,
  EMPTY,
  ensureSavedParentOptions,
  formatActivityTypeName,
  formatActivityTypeParent,
  formatActivityTypeSummary,
  getSafeActivityTypeErrorMessage,
  hasParentActivityType,
  normalizeActivityTypeParentId,
  toActivityTypePayload,
  validateActivityTypeForm,
} from '../activity-type.helpers'

describe('activity-type.helpers', () => {
  it('formats name, parent, summary, and parent presence safely', () => {
    expect(formatActivityTypeName({ name: '  Rawat Jalan  ' })).toBe('Rawat Jalan')
    expect(formatActivityTypeName({ name: '   ' })).toBe(EMPTY)
    expect(formatActivityTypeParent({ parent: { name: 'Pelayanan Medis' } })).toBe('Pelayanan Medis')
    expect(formatActivityTypeParent({ parent: null })).toBe(EMPTY)
    expect(formatActivityTypeSummary({ name: 'Poli Umum' })).toBe('Poli Umum')
    expect(formatActivityTypeSummary(null)).toBe('Jenis Kegiatan')
    expect(hasParentActivityType({ parent_id: 2 })).toBe(true)
    expect(hasParentActivityType({ parent_id: null })).toBe(false)
  })

  it('normalizes parent_id and builds clean form state and payload', () => {
    expect(normalizeActivityTypeParentId('')).toBeNull()
    expect(normalizeActivityTypeParentId('5')).toBe(5)
    expect(normalizeActivityTypeParentId(-1)).toBeNull()

    expect(createActivityTypeForm({ name: 'Sub Kegiatan', parent_id: '3' })).toEqual({
      name: 'Sub Kegiatan',
      parent_id: 3,
    })

    expect(toActivityTypePayload({ name: '  Kegiatan Baru  ', parent_id: '7' })).toEqual({
      name: 'Kegiatan Baru',
      parent_id: 7,
    })
    expect(toActivityTypePayload({ name: 'Induk', parent_id: '' })).toEqual({
      name: 'Induk',
      parent_id: null,
    })
  })

  it('validates required name, max length 100, and self-parent prevention', () => {
    expect(validateActivityTypeForm({ name: '   ', parent_id: null })).toEqual({
      name: 'Nama jenis kegiatan wajib diisi.',
    })
    expect(
      validateActivityTypeForm(
        { name: 'A'.repeat(ACTIVITY_TYPE_NAME_MAX_LENGTH + 1), parent_id: null },
        null,
      ),
    ).toEqual({
      name: `Nama jenis kegiatan maksimal ${ACTIVITY_TYPE_NAME_MAX_LENGTH} karakter.`,
    })
    expect(validateActivityTypeForm({ name: 'Valid', parent_id: 12 }, 12)).toEqual({
      parent_id: 'Jenis kegiatan tidak boleh menjadi induk untuk dirinya sendiri.',
    })
    expect(validateActivityTypeForm({ name: 'Valid', parent_id: 2 }, 12)).toEqual({})
  })

  it('ensures saved parent option exists during edit and filters out self', () => {
    const options = [
      { id: 1, name: 'Diri Sendiri' },
      { id: 2, name: 'Pelayanan Utama' },
    ]
    const editingItem = { id: 1, name: 'Diri Sendiri', parent_id: 99, parent: { id: 99, name: 'Induk Arsip' } }

    expect(ensureSavedParentOptions(options, editingItem)).toEqual([
      { id: 99, name: 'Induk Arsip', original_name: 'Induk Arsip' },
      { id: 2, name: 'Pelayanan Utama' },
    ])
  })

  it('extracts fieldErrors.id on 422 delete guard and sanitizes 5xx/SQL errors', () => {
    expect(
      getSafeActivityTypeErrorMessage(
        {
          status: 422,
          message: 'The given data was invalid.',
          fieldErrors: { id: 'Jenis kegiatan ini masih digunakan sebagai induk.' },
        },
        'Fallback',
      ),
    ).toBe('Jenis kegiatan ini masih digunakan sebagai induk.')

    expect(
      getSafeActivityTypeErrorMessage(
        { status: 500, message: 'SQLSTATE[42S02]: table missing' },
        'Gagal memuat data.',
      ),
    ).toBe('Gagal memuat data.')

    expect(
      getSafeActivityTypeErrorMessage(
        { status: 400, message: 'PDOException: connection refused' },
        'Permintaan tidak valid.',
      ),
    ).toBe('Permintaan tidak valid.')
  })
})
