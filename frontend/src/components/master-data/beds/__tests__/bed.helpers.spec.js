import { describe, expect, it } from 'vitest'
import {
  BED_NAME_MAX_LENGTH,
  createBedForm,
  formatBedClass,
  formatBedDescription,
  formatBedRoom,
  formatRoomOption,
  getSafeBedErrorMessage,
  toBedPayload,
  validateBedForm,
} from '../bed.helpers'

describe('bed.helpers', () => {
  describe('formatBedRoom', () => {
    it('mengutamakan nama tampilan dari server', () => {
      expect(formatBedRoom({ room_table_display_name: 'Kamar 1 - Melati' })).toBe(
        'Kamar 1 - Melati',
      )
    })

    it('menyusun nama kamar dan bangsal', () => {
      expect(formatBedRoom({ room: { name: 'Kamar 2', ward: { name: 'Melati' } } })).toBe(
        'Kamar 2 - Melati',
      )
    })

    it('mengembalikan tanda kosong bila tidak ada kamar', () => {
      expect(formatBedRoom({})).toBe('—')
    })
  })

  it('formatBedClass membaca room_class atau class', () => {
    expect(formatBedClass({ room: { room_class: { name: 'VIP' } } })).toBe('VIP')
    expect(formatBedClass({ room: { class: { name: 'Kelas 3' } } })).toBe('Kelas 3')
    expect(formatBedClass({ room: {} })).toBe('—')
  })

  it('formatBedDescription mengembalikan teks trim atau tanda kosong', () => {
    expect(formatBedDescription({ description: '  Dekat jendela  ' })).toBe('Dekat jendela')
    expect(formatBedDescription({ description: '   ' })).toBe('—')
    expect(formatBedDescription({ description: null })).toBe('—')
  })

  it('formatRoomOption memberi label pengganti untuk data yang belum lengkap', () => {
    expect(formatRoomOption({ display_name: 'Kamar 1 - Melati - VIP' })).toBe(
      'Kamar 1 - Melati - VIP',
    )
    expect(formatRoomOption({ name: 'Kamar 5' })).toBe(
      'Kamar 5 - Tanpa Bangsal - (Kelas Belum Ditentukan)',
    )
  })

  describe('validateBedForm', () => {
    it('lolos untuk form yang lengkap', () => {
      expect(validateBedForm({ room_id: 1, name: 'Bed 1', description: '' })).toEqual({})
    })

    it('mewajibkan kamar dan nama (spasi saja dianggap kosong)', () => {
      expect(validateBedForm({ room_id: '', name: '   ', description: '' })).toEqual({
        room_id: 'Nama kamar wajib dipilih.',
        name: 'Nama bed wajib diisi.',
      })
    })

    it('membatasi panjang nama sesuai backend', () => {
      const errors = validateBedForm({
        room_id: 1,
        name: 'x'.repeat(BED_NAME_MAX_LENGTH + 1),
        description: '',
      })
      expect(errors.name).toBe(`Nama bed maksimal ${BED_NAME_MAX_LENGTH} karakter.`)
    })
  })

  it('toBedPayload merapikan input sebelum dikirim', () => {
    expect(toBedPayload({ room_id: '3', name: '  Bed 1 ', description: '   ' })).toEqual({
      room_id: 3,
      name: 'Bed 1',
      description: null,
    })
  })

  it('createBedForm mengisi form dari data bed (mode edit)', () => {
    expect(createBedForm({ room: { id: 4 }, name: 'Bed 4', description: null })).toEqual({
      room_id: 4,
      name: 'Bed 4',
      description: '',
    })
  })

  describe('getSafeBedErrorMessage', () => {
    it('meneruskan pesan bisnis 4xx dan menyaring pesan teknis/database', () => {
      expect(
        getSafeBedErrorMessage(
          { status: 422, message: 'Nama bed sudah digunakan pada kamar yang dipilih.' },
          'Fallback',
        ),
      ).toBe('Nama bed sudah digunakan pada kamar yang dipilih.')

      expect(
        getSafeBedErrorMessage(
          { status: 500, message: 'SQLSTATE[42S02]: Table not found' },
          'Data bed gagal dimuat.',
        ),
      ).toBe('Data bed gagal dimuat.')
    })
  })
})
