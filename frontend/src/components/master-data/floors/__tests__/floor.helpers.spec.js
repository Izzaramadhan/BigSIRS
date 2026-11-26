import { describe, expect, it } from 'vitest'
import {
  createFloorForm,
  FLOOR_NAME_MAX_LENGTH,
  formatBuildingOption,
  formatFloorBuilding,
  formatFloorDescription,
  getSafeFloorErrorMessage,
  toFloorPayload,
  validateFloorForm,
} from '../floor.helpers'

describe('floor.helpers', () => {
  describe('formatFloorBuilding', () => {
    it('mengembalikan nama gedung yang di-trim bila tersedia', () => {
      expect(formatFloorBuilding({ building: { name: '  Gedung Utama  ' } })).toBe('Gedung Utama')
    })

    it('mengembalikan tanda kosong bila data gedung tidak tersedia', () => {
      expect(formatFloorBuilding({ building: null })).toBe('—')
      expect(formatFloorBuilding({})).toBe('—')
    })
  })

  it('formatFloorDescription mengembalikan deskripsi trim atau tanda kosong', () => {
    expect(formatFloorDescription({ description: '  Lantai Poliklinik  ' })).toBe(
      'Lantai Poliklinik',
    )
    expect(formatFloorDescription({ description: '   ' })).toBe('—')
    expect(formatFloorDescription({ description: null })).toBe('—')
  })

  it('formatBuildingOption memberi label pengganti bila nama gedung kosong', () => {
    expect(formatBuildingOption({ name: '  Gedung Utama  ' })).toBe('Gedung Utama')
    expect(formatBuildingOption({})).toBe('Gedung')
  })

  it('createFloorForm mengisi form mode edit dan default kosong', () => {
    expect(createFloorForm()).toEqual({ building_id: '', name: '', description: '' })
    expect(
      createFloorForm({
        building: { id: 3 },
        name: 'Lantai 2',
        description: null,
      }),
    ).toEqual({
      building_id: 3,
      name: 'Lantai 2',
      description: '',
    })
  })

  describe('validateFloorForm', () => {
    it('lolos untuk form yang valid', () => {
      expect(validateFloorForm({ building_id: 1, name: 'Lantai 1', description: '' })).toEqual({})
    })

    it('mewajibkan gedung dan nama lantai (spasi saja dianggap kosong)', () => {
      expect(validateFloorForm({ building_id: '', name: '   ', description: '' })).toEqual({
        building_id: 'Gedung wajib dipilih.',
        name: 'Nama lantai wajib diisi.',
      })
    })

    it('membatasi panjang nama lantai sesuai backend', () => {
      const errors = validateFloorForm({
        building_id: 1,
        name: 'x'.repeat(FLOOR_NAME_MAX_LENGTH + 1),
        description: '',
      })
      expect(errors.name).toBe(`Nama lantai maksimal ${FLOOR_NAME_MAX_LENGTH} karakter.`)
    })
  })

  it('toFloorPayload merapikan input dan menormalkan deskripsi kosong ke null', () => {
    expect(toFloorPayload({ building_id: '2', name: '  Lantai 1 ', description: '   ' })).toEqual({
      building_id: 2,
      name: 'Lantai 1',
      description: null,
    })
  })

  describe('getSafeFloorErrorMessage', () => {
    it('meneruskan pesan 4xx dan menyaring pesan teknis atau 5xx', () => {
      expect(
        getSafeFloorErrorMessage(
          { status: 422, message: 'Lantai tidak dapat dihapus karena masih digunakan.' },
          'Fallback',
        ),
      ).toBe('Lantai tidak dapat dihapus karena masih digunakan.')

      expect(
        getSafeFloorErrorMessage(
          { status: 500, message: 'SQLSTATE[42S02]: Table not found' },
          'Data lantai gagal dimuat.',
        ),
      ).toBe('Data lantai gagal dimuat.')
    })
  })
})
