import { describe, expect, it } from 'vitest'
import {
  createDietTypeForm,
  DIET_TYPE_DESCRIPTION_MAX_LENGTH,
  DIET_TYPE_NAME_MAX_LENGTH,
  EMPTY,
  formatDietTypeDescription,
  formatDietTypeName,
  formatDietTypeSummary,
  getSafeDietTypeErrorMessage,
  toDietTypePayload,
  validateDietTypeForm,
} from '../diet-type.helpers'

describe('diet-type.helpers', () => {
  it('formats name, description, and summary cleanly with fallbacks', () => {
    expect(formatDietTypeName({ name: 'Diet Diabetes' })).toBe('Diet Diabetes')
    expect(formatDietTypeName({ name: '   ' })).toBe(EMPTY)
    expect(formatDietTypeDescription({ description: 'Rendah gula' })).toBe('Rendah gula')
    expect(formatDietTypeDescription({ description: null })).toBe(EMPTY)
    expect(formatDietTypeDescription({ description: '  ' })).toBe(EMPTY)
    expect(formatDietTypeSummary({ name: 'Diet Jantung' })).toBe('Diet Jantung')
    expect(formatDietTypeSummary(null)).toBe('Asuhan Gizi')
  })

  it('creates initial form state for create and edit modes', () => {
    expect(createDietTypeForm()).toEqual({ name: '', description: '' })
    expect(
      createDietTypeForm({ id: 1, name: 'Diet Lunak', description: null }),
    ).toEqual({ name: 'Diet Lunak', description: '' })
  })

  it('validates required name and backend max lengths (50 / 255)', () => {
    expect(validateDietTypeForm({ name: '  ', description: '' })).toEqual({
      name: 'Nama asuhan gizi wajib diisi.',
    })

    expect(
      validateDietTypeForm({
        name: 'A'.repeat(DIET_TYPE_NAME_MAX_LENGTH),
        description: 'B'.repeat(DIET_TYPE_DESCRIPTION_MAX_LENGTH),
      }),
    ).toEqual({})

    const invalid = validateDietTypeForm({
      name: 'A'.repeat(DIET_TYPE_NAME_MAX_LENGTH + 1),
      description: 'B'.repeat(DIET_TYPE_DESCRIPTION_MAX_LENGTH + 1),
    })

    expect(invalid.name).toBe(
      `Nama asuhan gizi maksimal ${DIET_TYPE_NAME_MAX_LENGTH} karakter.`,
    )
    expect(invalid.description).toBe(
      `Deskripsi maksimal ${DIET_TYPE_DESCRIPTION_MAX_LENGTH} karakter.`,
    )
  })

  it('builds normalized payload excluding unsupported fields', () => {
    const payload = toDietTypePayload({
      id: 9,
      name: '  Diet   Rendah   Garam  ',
      description: '   ',
      is_active: false,
    })

    expect(payload).toEqual({
      name: 'Diet Rendah Garam',
      description: null,
    })
    expect(Object.keys(payload)).toEqual(['name', 'description'])
  })

  it('sanitizes 5xx and technical SQL error messages', () => {
    expect(
      getSafeDietTypeErrorMessage(
        { status: 422, message: 'Nama asuhan gizi sudah digunakan.' },
        'Fallback',
      ),
    ).toBe('Nama asuhan gizi sudah digunakan.')

    expect(
      getSafeDietTypeErrorMessage(
        { status: 500, message: 'SQLSTATE[42S02]: missing table' },
        'Fallback',
      ),
    ).toBe('Fallback')

    expect(
      getSafeDietTypeErrorMessage(
        { status: 400, message: 'PDOException: connection lost' },
        'Fallback',
      ),
    ).toBe('Fallback')
  })
})
