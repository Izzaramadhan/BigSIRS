import { describe, expect, it } from 'vitest'
import {
  createOccupationForm,
  EMPTY,
  formatOccupationName,
  getSafeOccupationErrorMessage,
  OCCUPATION_NAME_MAX_LENGTH,
  toOccupationPayload,
  validateOccupationForm,
} from '../occupation.helpers'

describe('occupation.helpers', () => {
  it('formatOccupationName merapikan nama atau mengembalikan tanda kosong', () => {
    expect(formatOccupationName({ name: '  Pegawai Negeri Sipil  ' })).toBe('Pegawai Negeri Sipil')
    expect(formatOccupationName({ name: '   ' })).toBe(EMPTY)
    expect(formatOccupationName(null)).toBe(EMPTY)
  })

  it('createOccupationForm mengisi mode tambah dan edit', () => {
    expect(createOccupationForm()).toEqual({ name: '' })
    expect(createOccupationForm({ name: 'Wiraswasta' })).toEqual({ name: 'Wiraswasta' })
  })

  describe('validateOccupationForm', () => {
    it('lolos untuk nama valid hingga batas 50 karakter', () => {
      expect(validateOccupationForm({ name: 'PNS' })).toEqual({})
      expect(validateOccupationForm({ name: 'a'.repeat(OCCUPATION_NAME_MAX_LENGTH) })).toEqual({})
    })

    it('menolak nama kosong, spasi saja, atau melebihi 50 karakter', () => {
      expect(validateOccupationForm({ name: '   ' })).toEqual({
        name: 'Nama pekerjaan wajib diisi.',
      })
      expect(validateOccupationForm({ name: 'x'.repeat(OCCUPATION_NAME_MAX_LENGTH + 1) })).toEqual({
        name: `Nama pekerjaan maksimal ${OCCUPATION_NAME_MAX_LENGTH} karakter.`,
      })
    })
  })

  it('toOccupationPayload menormalkan spasi berlebih sesuai backend', () => {
    expect(toOccupationPayload({ name: '  Pegawai   Negeri   Sipil  ' })).toEqual({
      name: 'Pegawai Negeri Sipil',
    })
  })

  it('getSafeOccupationErrorMessage meneruskan error 4xx/409 dan menyaring 5xx atau SQLSTATE', () => {
    expect(
      getSafeOccupationErrorMessage(
        {
          status: 409,
          message: 'Pekerjaan tidak dapat dihapus karena sedang digunakan oleh data Pegawai/Dokter.',
        },
        'Fallback',
      ),
    ).toBe('Pekerjaan tidak dapat dihapus karena sedang digunakan oleh data Pegawai/Dokter.')

    expect(
      getSafeOccupationErrorMessage(
        { status: 500, message: 'SQLSTATE[42S02]: Base table not found' },
        'Data pekerjaan gagal dimuat.',
      ),
    ).toBe('Data pekerjaan gagal dimuat.')
  })
})
