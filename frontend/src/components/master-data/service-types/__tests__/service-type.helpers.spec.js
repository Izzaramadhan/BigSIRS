import { describe, expect, it } from 'vitest'
import {
  createServiceTypeForm,
  EMPTY,
  formatServiceTypeName,
  getSafeServiceTypeErrorMessage,
  SERVICE_TYPE_NAME_MAX_LENGTH,
  toServiceTypePayload,
  validateServiceTypeForm,
} from '../service-type.helpers'

describe('service-type.helpers', () => {
  it('formats service type name with fallback dash when missing or blank', () => {
    expect(formatServiceTypeName({ name: 'Rawat Jalan' })).toBe('Rawat Jalan')
    expect(formatServiceTypeName({ name: '   ' })).toBe(EMPTY)
    expect(formatServiceTypeName(null)).toBe(EMPTY)
  })

  it('creates form state for create and edit modes', () => {
    expect(createServiceTypeForm()).toEqual({ name: '' })
    expect(createServiceTypeForm({ id: 2, name: 'IGD' })).toEqual({ name: 'IGD' })
  })

  it('validates required and max 45 character limit', () => {
    expect(validateServiceTypeForm({ name: '' })).toEqual({
      name: 'Nama jenis layanan wajib diisi.',
    })
    expect(validateServiceTypeForm({ name: '   ' })).toEqual({
      name: 'Nama jenis layanan wajib diisi.',
    })

    const validBoundary = 'A'.repeat(SERVICE_TYPE_NAME_MAX_LENGTH)
    expect(validateServiceTypeForm({ name: validBoundary })).toEqual({})

    const tooLong = 'A'.repeat(SERVICE_TYPE_NAME_MAX_LENGTH + 1)
    expect(validateServiceTypeForm({ name: tooLong })).toEqual({
      name: `Nama jenis layanan maksimal ${SERVICE_TYPE_NAME_MAX_LENGTH} karakter.`,
    })
  })

  it('builds clean normalized payload containing only supported fields', () => {
    const payload = toServiceTypePayload({
      id: 99,
      name: '  Rawat   Inap  Utama ',
      is_active: true,
    })

    expect(payload).toEqual({ name: 'Rawat Inap Utama' })
    expect(Object.keys(payload)).toEqual(['name'])
  })

  it('returns business error messages and redacts 5xx/technical SQL errors', () => {
    expect(
      getSafeServiceTypeErrorMessage(
        {
          status: 422,
          message: 'The given data was invalid.',
          fieldErrors: {
            id: 'Jenis layanan tidak dapat dihapus karena sudah digunakan pada transaksi kunjungan.',
          },
        },
        'Fallback',
      ),
    ).toBe('Jenis layanan tidak dapat dihapus karena sudah digunakan pada transaksi kunjungan.')

    expect(
      getSafeServiceTypeErrorMessage(
        { status: 500, message: 'SQLSTATE[42S02]: table missing' },
        'Fallback',
      ),
    ).toBe('Fallback')

    expect(
      getSafeServiceTypeErrorMessage(
        { status: 400, message: 'PDOException: connection timed out' },
        'Fallback',
      ),
    ).toBe('Fallback')
  })
})
