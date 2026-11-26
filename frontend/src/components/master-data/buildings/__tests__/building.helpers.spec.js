import { describe, expect, it } from 'vitest'
import {
  BUILDING_NAME_MAX_LENGTH,
  createBuildingForm,
  formatBuildingDescription,
  getSafeBuildingErrorMessage,
  toBuildingPayload,
  validateBuildingForm,
} from '../building.helpers'

describe('building.helpers', () => {
  describe('formatBuildingDescription', () => {
    it('mengembalikan deskripsi yang sudah di-trim bila tersedia', () => {
      expect(formatBuildingDescription({ description: '  Gedung Rawat Inap  ' })).toBe(
        'Gedung Rawat Inap',
      )
    })

    it('mengembalikan tanda kosong bila deskripsi null atau spasi', () => {
      expect(formatBuildingDescription({ description: null })).toBe('—')
      expect(formatBuildingDescription({ description: '   ' })).toBe('—')
      expect(formatBuildingDescription({})).toBe('—')
    })
  })

  it('createBuildingForm mengisi form dari data gedung (mode edit) dan default kosong', () => {
    expect(createBuildingForm()).toEqual({ name: '', description: '' })
    expect(createBuildingForm({ name: 'Gedung Teratai', description: null })).toEqual({
      name: 'Gedung Teratai',
      description: '',
    })
  })

  describe('validateBuildingForm', () => {
    it('lolos untuk form yang valid', () => {
      expect(validateBuildingForm({ name: 'Gedung Mawar', description: '' })).toEqual({})
    })

    it('mewajibkan nama gedung (spasi saja dianggap kosong)', () => {
      expect(validateBuildingForm({ name: '   ', description: '' })).toEqual({
        name: 'Nama gedung wajib diisi.',
      })
    })

    it('membatasi panjang nama sesuai backend', () => {
      const errors = validateBuildingForm({
        name: 'x'.repeat(BUILDING_NAME_MAX_LENGTH + 1),
        description: '',
      })
      expect(errors.name).toBe(`Nama gedung maksimal ${BUILDING_NAME_MAX_LENGTH} karakter.`)
    })
  })

  it('toBuildingPayload merapikan input dan menormalkan deskripsi kosong ke null', () => {
    expect(toBuildingPayload({ name: '  Gedung Teratai  ', description: '   ' })).toEqual({
      name: 'Gedung Teratai',
      description: null,
    })
    expect(toBuildingPayload({ name: 'Gedung A', description: '  Lantai 1-3  ' })).toEqual({
      name: 'Gedung A',
      description: 'Lantai 1-3',
    })
  })

  describe('getSafeBuildingErrorMessage', () => {
    it('meneruskan pesan bisnis yang aman dari API', () => {
      expect(
        getSafeBuildingErrorMessage(
          { status: 422, message: 'Gedung tidak dapat dihapus karena masih digunakan.' },
          'Fallback',
        ),
      ).toBe('Gedung tidak dapat dihapus karena masih digunakan.')
    })

    it('menyembunyikan detail teknis/database atau error server >= 500', () => {
      expect(
        getSafeBuildingErrorMessage(
          { status: 500, message: 'Internal failure details' },
          'Data gedung gagal ditambahkan.',
        ),
      ).toBe('Data gedung gagal ditambahkan.')

      expect(
        getSafeBuildingErrorMessage(
          { status: 400, message: 'SQLSTATE[42S02]: Base table not found' },
          'Data gedung gagal dimuat.',
        ),
      ).toBe('Data gedung gagal dimuat.')
    })
  })
})
