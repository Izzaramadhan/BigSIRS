import { describe, expect, it } from 'vitest'
import {
  createWardForm,
  formatFloorOption,
  formatWardDescription,
  formatWardFloor,
  getSafeWardErrorMessage,
  toWardPayload,
  validateWardForm,
  WARD_NAME_MAX_LENGTH,
} from '../ward.helpers'

describe('ward.helpers', () => {
  describe('formatWardFloor', () => {
    it('mengutamakan floor_display_name dari server', () => {
      expect(
        formatWardFloor({
          floor_display_name: 'Lantai 4 - RUANG ARJUNA',
          floor: { name: 'Lainnya', building: { name: 'Lainnya' } },
        }),
      ).toBe('Lantai 4 - RUANG ARJUNA')
    })

    it('menyusun nama lantai dan gedung saat floor_display_name tidak tersedia', () => {
      expect(
        formatWardFloor({
          floor: { name: 'Lantai 1', building: { name: 'Gedung Utama' } },
        }),
      ).toBe('Lantai 1 - Gedung Utama')
    })

    it('mengembalikan tanda kosong bila relasi lantai tidak tersedia', () => {
      expect(formatWardFloor({ floor: null })).toBe('—')
      expect(formatWardFloor({})).toBe('—')
    })
  })

  it('formatWardDescription mengembalikan deskripsi trim atau tanda kosong', () => {
    expect(formatWardDescription({ description: '  Perawatan Anak  ' })).toBe('Perawatan Anak')
    expect(formatWardDescription({ description: '   ' })).toBe('—')
    expect(formatWardDescription({ description: null })).toBe('—')
  })

  it('formatFloorOption memformat label Lantai - Gedung dan fallback aman', () => {
    expect(formatFloorOption({ display_name: 'Lantai 1 - Gedung Utama' })).toBe(
      'Lantai 1 - Gedung Utama',
    )
    expect(formatFloorOption({ name: 'Lantai 2', building: { name: 'Gedung B' } })).toBe(
      'Lantai 2 - Gedung B',
    )
    expect(formatFloorOption({})).toBe('Lantai')
  })

  it('createWardForm mengisi form mode edit dan default kosong', () => {
    expect(createWardForm()).toEqual({ floor_id: '', name: '', description: '' })
    expect(
      createWardForm({
        floor: { id: 4 },
        name: 'Bangsal Arjuna A',
        description: null,
      }),
    ).toEqual({
      floor_id: 4,
      name: 'Bangsal Arjuna A',
      description: '',
    })
  })

  describe('validateWardForm', () => {
    it('lolos untuk form yang valid', () => {
      expect(validateWardForm({ floor_id: 1, name: 'Bangsal Melati', description: '' })).toEqual(
        {},
      )
    })

    it('mewajibkan lantai dan nama bangsal (spasi saja dianggap kosong)', () => {
      expect(validateWardForm({ floor_id: '', name: '   ', description: '' })).toEqual({
        floor_id: 'Lantai wajib dipilih.',
        name: 'Nama bangsal wajib diisi.',
      })
    })

    it('membatasi panjang nama bangsal sesuai backend', () => {
      const errors = validateWardForm({
        floor_id: 1,
        name: 'x'.repeat(WARD_NAME_MAX_LENGTH + 1),
        description: '',
      })
      expect(errors.name).toBe(`Nama bangsal maksimal ${WARD_NAME_MAX_LENGTH} karakter.`)
    })
  })

  it('toWardPayload merapikan input dan menormalkan deskripsi kosong ke null', () => {
    expect(toWardPayload({ floor_id: '4', name: '  Bangsal Melati ', description: '   ' })).toEqual(
      {
        floor_id: 4,
        name: 'Bangsal Melati',
        description: null,
      },
    )
  })

  describe('getSafeWardErrorMessage', () => {
    it('meneruskan pesan bisnis 4xx dan menyaring detail teknis atau 5xx', () => {
      expect(
        getSafeWardErrorMessage(
          { status: 422, message: 'Bangsal tidak dapat dihapus karena masih digunakan oleh data kamar.' },
          'Fallback',
        ),
      ).toBe('Bangsal tidak dapat dihapus karena masih digunakan oleh data kamar.')

      expect(
        getSafeWardErrorMessage(
          { status: 500, message: 'SQLSTATE[42S02]: Table not found' },
          'Data bangsal gagal dimuat.',
        ),
      ).toBe('Data bangsal gagal dimuat.')
    })
  })
})
