import { describe, expect, it } from 'vitest'
import {
  createPositionForm,
  EMPTY,
  formatPositionDescription,
  formatPositionName,
  getSafePositionErrorMessage,
  POSITION_DESCRIPTION_MAX_LENGTH,
  POSITION_NAME_MAX_LENGTH,
  toPositionPayload,
  validatePositionForm,
} from '../position.helpers'

describe('position.helpers', () => {
  it('formatPositionName dan formatPositionDescription mengembalikan teks trim atau tanda kosong', () => {
    expect(formatPositionName({ name: '  Kepala Ruangan  ' })).toBe('Kepala Ruangan')
    expect(formatPositionName({ name: '   ' })).toBe(EMPTY)
    expect(formatPositionName(null)).toBe(EMPTY)

    expect(formatPositionDescription({ description: '  Koordinator unit  ' })).toBe('Koordinator unit')
    expect(formatPositionDescription({ description: '' })).toBe(EMPTY)
    expect(formatPositionDescription({ description: null })).toBe(EMPTY)
  })

  it('createPositionForm mengisi mode tambah dan edit', () => {
    expect(createPositionForm()).toEqual({ name: '', description: '' })
    expect(
      createPositionForm({ name: 'Administrator', description: 'Pengelola sistem' }),
    ).toEqual({
      name: 'Administrator',
      description: 'Pengelola sistem',
    })
    expect(createPositionForm({ name: 'Perawat', description: null })).toEqual({
      name: 'Perawat',
      description: '',
    })
  })

  describe('validatePositionForm', () => {
    it('lolos untuk nama valid dan deskripsi opsional hingga batas 50 karakter', () => {
      expect(validatePositionForm({ name: 'Dokter Umum', description: '' })).toEqual({})
      expect(
        validatePositionForm({
          name: 'a'.repeat(POSITION_NAME_MAX_LENGTH),
          description: 'b'.repeat(POSITION_DESCRIPTION_MAX_LENGTH),
        }),
      ).toEqual({})
    })

    it('menolak nama kosong dan panjang melebihi 50 karakter untuk nama maupun deskripsi', () => {
      expect(validatePositionForm({ name: '   ', description: '' })).toEqual({
        name: 'Nama jabatan wajib diisi.',
      })

      expect(
        validatePositionForm({
          name: 'x'.repeat(POSITION_NAME_MAX_LENGTH + 1),
          description: 'y'.repeat(POSITION_DESCRIPTION_MAX_LENGTH + 1),
        }),
      ).toEqual({
        name: `Nama jabatan maksimal ${POSITION_NAME_MAX_LENGTH} karakter.`,
        description: `Deskripsi maksimal ${POSITION_DESCRIPTION_MAX_LENGTH} karakter.`,
      })
    })
  })

  it('toPositionPayload merapikan spasi nama dan menormalkan deskripsi kosong ke null', () => {
    expect(
      toPositionPayload({
        name: '  Kepala   Instalasi  ',
        description: '   ',
      }),
    ).toEqual({
      name: 'Kepala Instalasi',
      description: null,
    })

    expect(
      toPositionPayload({
        name: 'Kepala Instalasi',
        description: '  Penanggung jawab unit  ',
      }),
    ).toEqual({
      name: 'Kepala Instalasi',
      description: 'Penanggung jawab unit',
    })
  })

  it('getSafePositionErrorMessage meneruskan error bisnis 422 dan menyaring 5xx atau SQLSTATE', () => {
    expect(
      getSafePositionErrorMessage(
        {
          status: 422,
          message: 'Data jabatan tidak dapat dihapus karena sedang digunakan oleh data pegawai.',
        },
        'Fallback',
      ),
    ).toBe('Data jabatan tidak dapat dihapus karena sedang digunakan oleh data pegawai.')

    expect(
      getSafePositionErrorMessage(
        { status: 500, message: 'SQLSTATE[42S02]: Table not found' },
        'Data jabatan gagal dimuat.',
      ),
    ).toBe('Data jabatan gagal dimuat.')
  })
})
