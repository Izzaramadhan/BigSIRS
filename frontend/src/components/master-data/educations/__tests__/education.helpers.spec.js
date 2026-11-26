import { describe, expect, it } from 'vitest'
import {
  createEducationForm,
  EDUCATION_NAME_MAX_LENGTH,
  EMPTY,
  formatEducationName,
  getSafeEducationErrorMessage,
  toEducationPayload,
  validateEducationForm,
} from '../education.helpers'

describe('education.helpers', () => {
  it('formatEducationName merapikan nama atau mengembalikan tanda kosong', () => {
    expect(formatEducationName({ name: '  S1 Kedokteran Umum  ' })).toBe('S1 Kedokteran Umum')
    expect(formatEducationName({ name: '   ' })).toBe(EMPTY)
    expect(formatEducationName(null)).toBe(EMPTY)
  })

  it('createEducationForm mengisi mode tambah dan edit', () => {
    expect(createEducationForm()).toEqual({ name: '' })
    expect(createEducationForm({ name: 'SMA' })).toEqual({ name: 'SMA' })
  })

  describe('validateEducationForm', () => {
    it('lolos untuk nama valid hingga batas 50 karakter', () => {
      expect(validateEducationForm({ name: 'S1 Kedokteran Umum' })).toEqual({})
      expect(validateEducationForm({ name: 'a'.repeat(EDUCATION_NAME_MAX_LENGTH) })).toEqual({})
    })

    it('menolak nama kosong, spasi saja, atau melebihi 50 karakter', () => {
      expect(validateEducationForm({ name: '   ' })).toEqual({
        name: 'Nama pendidikan wajib diisi.',
      })
      expect(validateEducationForm({ name: 'x'.repeat(EDUCATION_NAME_MAX_LENGTH + 1) })).toEqual({
        name: `Nama pendidikan maksimal ${EDUCATION_NAME_MAX_LENGTH} karakter.`,
      })
    })
  })

  it('toEducationPayload menormalkan spasi berlebih sesuai backend', () => {
    expect(toEducationPayload({ name: '  S1   Teknik  Informatika  ' })).toEqual({
      name: 'S1 Teknik Informatika',
    })
  })

  it('getSafeEducationErrorMessage meneruskan error 4xx/409 dan menyaring 5xx atau SQLSTATE', () => {
    expect(
      getSafeEducationErrorMessage(
        {
          status: 409,
          message: 'Pendidikan tidak dapat dihapus karena sedang digunakan oleh data Pegawai/Dokter.',
        },
        'Fallback',
      ),
    ).toBe('Pendidikan tidak dapat dihapus karena sedang digunakan oleh data Pegawai/Dokter.')

    expect(
      getSafeEducationErrorMessage(
        { status: 500, message: 'SQLSTATE[42S02]: Base table not found' },
        'Data pendidikan gagal dimuat.',
      ),
    ).toBe('Data pendidikan gagal dimuat.')
  })
})
