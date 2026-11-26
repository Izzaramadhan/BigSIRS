import { describe, expect, it } from 'vitest'
import {
  createRoomForm,
  formatRoomClass,
  formatRoomDescription,
  formatRoomWard,
  formatWardOption,
  getSafeRoomErrorMessage,
  ROOM_CODE_MAX_LENGTH,
  ROOM_NAME_MAX_LENGTH,
  toRoomClassOption,
  toRoomPayload,
  validateRoomForm,
} from '../room.helpers'

describe('room.helpers', () => {
  describe('formatRoomWard', () => {
    it('menyusun hierarki Nama Bangsal - Nama Lantai - Nama Gedung dari relasi API', () => {
      expect(
        formatRoomWard({
          ward: {
            name: 'Bangsal Maternal Perinatal',
            floor: {
              name: 'Lantai 1',
              building: { name: 'Gedung Utama' },
            },
          },
          ward_display_name: 'Bangsal Maternal Perinatal (Lantai 1 - Gedung Utama)',
        }),
      ).toBe('Bangsal Maternal Perinatal - Lantai 1 - Gedung Utama')
    })

    it('menampilkan informasi parsial secara aman atau fallback ke ward_display_name / —', () => {
      expect(
        formatRoomWard({
          ward: { name: 'Bangsal Melati', floor: { name: 'Lantai 2', building: null } },
        }),
      ).toBe('Bangsal Melati - Lantai 2')
      expect(formatRoomWard({ ward_display_name: 'Bangsal Cadangan' })).toBe('Bangsal Cadangan')
      expect(formatRoomWard({})).toBe('—')
    })
  })

  it('formatRoomClass membaca room_class atau class dengan fallback —', () => {
    expect(formatRoomClass({ room_class: { name: 'Kelas II' } })).toBe('Kelas II')
    expect(formatRoomClass({ class: { name: 'VIP' } })).toBe('VIP')
    expect(formatRoomClass({})).toBe('—')
  })

  it('formatRoomDescription mengembalikan trim atau —', () => {
    expect(formatRoomDescription({ description: '  Kamar bersalin  ' })).toBe('Kamar bersalin')
    expect(formatRoomDescription({ description: '   ' })).toBe('—')
    expect(formatRoomDescription({ description: null })).toBe('—')
  })

  it('formatWardOption menyusun label Bangsal - Lantai - Gedung dan fallback', () => {
    expect(
      formatWardOption({
        name: 'Bangsal Melati',
        floor: { name: 'Lantai 1', building: { name: 'Gedung Utama' } },
      }),
    ).toBe('Bangsal Melati - Lantai 1 - Gedung Utama')
    expect(formatWardOption({ display_name: 'Bangsal Cadangan' })).toBe('Bangsal Cadangan')
    expect(formatWardOption({})).toBe('Bangsal')
  })

  it('toRoomClassOption menormalisasi format opsi kelas', () => {
    expect(toRoomClassOption({ id: 2, code: 'KL2', name: 'Kelas II' })).toEqual({
      id: 2,
      code: 'KL2',
      name: 'Kelas II',
      label: 'Kelas II',
      value: 2,
    })
  })

  it('createRoomForm mengisi form edit dan default kosong', () => {
    expect(createRoomForm()).toEqual({
      ward_id: '',
      code: '',
      name: '',
      room_class_id: '',
      description: '',
    })
    expect(
      createRoomForm({
        ward: { id: 3 },
        code: 'KMR-001',
        name: 'Kamar Melati 1',
        class_id: '2',
        description: null,
      }),
    ).toEqual({
      ward_id: 3,
      code: 'KMR-001',
      name: 'Kamar Melati 1',
      room_class_id: 2,
      description: '',
    })
  })

  describe('validateRoomForm', () => {
    it('lolos untuk form yang valid', () => {
      expect(
        validateRoomForm({
          ward_id: 1,
          code: 'KMR-01',
          name: 'Kamar 1',
          room_class_id: 2,
          description: '',
        }),
      ).toEqual({})
    })

    it('mewajibkan bangsal, kode kamar, nama kamar, dan kelas', () => {
      expect(
        validateRoomForm({
          ward_id: '',
          code: '  ',
          name: '   ',
          room_class_id: '',
          description: '',
        }),
      ).toEqual({
        ward_id: 'Bangsal wajib dipilih.',
        code: 'Kode kamar wajib diisi.',
        name: 'Nama kamar wajib diisi.',
        room_class_id: 'Kelas wajib dipilih.',
      })
    })

    it('membatasi panjang kode (50) dan nama kamar (255) sesuai backend', () => {
      const errors = validateRoomForm({
        ward_id: 1,
        code: 'C'.repeat(ROOM_CODE_MAX_LENGTH + 1),
        name: 'N'.repeat(ROOM_NAME_MAX_LENGTH + 1),
        room_class_id: 2,
      })
      expect(errors.code).toBe(`Kode kamar maksimal ${ROOM_CODE_MAX_LENGTH} karakter.`)
      expect(errors.name).toBe(`Nama kamar maksimal ${ROOM_NAME_MAX_LENGTH} karakter.`)
    })
  })

  it('toRoomPayload mengirim kontrak lengkap dengan room_class_id dan class_id', () => {
    expect(
      toRoomPayload({
        ward_id: '3',
        code: '  KMR-003  ',
        name: '  Kamar Melati 3 ',
        room_class_id: '2',
        description: '   ',
      }),
    ).toEqual({
      ward_id: 3,
      code: 'KMR-003',
      name: 'Kamar Melati 3',
      room_class_id: 2,
      class_id: 2,
      description: null,
    })
  })

  it('getSafeRoomErrorMessage menyaring error >= 500 dan pesan SQL/teknis', () => {
    expect(
      getSafeRoomErrorMessage(
        { status: 422, message: 'Kamar tidak dapat dihapus karena masih digunakan.' },
        'Fallback',
      ),
    ).toBe('Kamar tidak dapat dihapus karena masih digunakan.')

    expect(
      getSafeRoomErrorMessage(
        { status: 500, message: 'SQLSTATE[42S02]: Base table not found' },
        'Data kamar gagal dimuat.',
      ),
    ).toBe('Data kamar gagal dimuat.')
  })
})
