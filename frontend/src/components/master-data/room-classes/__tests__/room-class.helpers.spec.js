import { describe, expect, it } from 'vitest'
import {
  createRoomClassForm,
  formatPriceDigits,
  formatRoomClassDescription,
  formatRoomClassExternalCode,
  formatRoomClassPrice,
  getSafeRoomClassErrorMessage,
  parseRoomClassPrice,
  ROOM_CLASS_APLICARE_CODE_MAX_LENGTH,
  ROOM_CLASS_CODE_MAX_LENGTH,
  ROOM_CLASS_DESCRIPTION_MAX_LENGTH,
  ROOM_CLASS_NAME_MAX_LENGTH,
  ROOM_CLASS_SIRS_CODE_MAX_LENGTH,
  toRoomClassPayload,
  validateRoomClassForm,
} from '../room-class.helpers'

describe('room-class.helpers', () => {
  describe('formatRoomClassPrice', () => {
    it('memformat harga numerik menjadi Rupiah Indonesia dan fallback Rp0', () => {
      expect(formatRoomClassPrice({ price: 150000 })).toBe('Rp150.000')
      expect(formatRoomClassPrice({ price: '250000.00' })).toBe('Rp250.000')
      expect(formatRoomClassPrice({ price: 0 })).toBe('Rp0')
      expect(formatRoomClassPrice({ price: null })).toBe('Rp0')
      expect(formatRoomClassPrice({})).toBe('Rp0')
    })
  })

  it('formatRoomClassDescription dan formatRoomClassExternalCode mengembalikan tanda kosong bila kosong', () => {
    expect(formatRoomClassDescription({ description: '  Kamar ber-AC  ' })).toBe('Kamar ber-AC')
    expect(formatRoomClassDescription({ description: '   ' })).toBe('—')
    expect(formatRoomClassDescription({ description: null })).toBe('—')

    expect(formatRoomClassExternalCode(' KL1 ')).toBe('KL1')
    expect(formatRoomClassExternalCode('')).toBe('—')
    expect(formatRoomClassExternalCode(null)).toBe('—')
  })

  it('formatPriceDigits dan parseRoomClassPrice menangani digit, 0, dan input kosong', () => {
    expect(formatPriceDigits('250000')).toBe('250.000')
    expect(formatPriceDigits('Rp 1.500.000abc')).toBe('1.500.000')
    expect(formatPriceDigits('')).toBe('')

    expect(parseRoomClassPrice('250.000')).toBe(250000)
    expect(parseRoomClassPrice('0')).toBe(0)
    expect(parseRoomClassPrice('')).toBeNull()
    expect(parseRoomClassPrice(null)).toBeNull()
  })

  it('createRoomClassForm mengisi mode tambah dan edit termasuk harga decimal string dari server', () => {
    expect(createRoomClassForm()).toEqual({
      name: '',
      code: '',
      price: '',
      description: '',
      aplicare_code: '',
      sirs_code: '',
    })

    expect(
      createRoomClassForm({
        name: 'Kelas 1',
        code: 'KLS-1',
        price: 150000,
        description: null,
        aplicare_code: 'KL1',
        sirs_code: null,
      }),
    ).toEqual({
      name: 'Kelas 1',
      code: 'KLS-1',
      price: '150.000',
      description: '',
      aplicare_code: 'KL1',
      sirs_code: '',
    })

    expect(createRoomClassForm({ price: '300000.00' }).price).toBe('300.000')
  })

  describe('validateRoomClassForm', () => {
    it('lolos untuk payload valid termasuk harga 0', () => {
      expect(
        validateRoomClassForm({
          name: 'Kelas 3',
          code: 'KLS-3',
          price: '0',
          description: '',
          aplicare_code: '',
          sirs_code: '',
        }),
      ).toEqual({})
    })

    it('mewajibkan nama kelas, kode kelas, dan harga kelas', () => {
      expect(
        validateRoomClassForm({
          name: '   ',
          code: '',
          price: '',
        }),
      ).toEqual({
        name: 'Nama kelas wajib diisi.',
        code: 'Kode kelas wajib diisi.',
        price: 'Harga kelas wajib diisi.',
      })
    })

    it('memvalidasi batas panjang semua kolom teks sesuai backend', () => {
      const errors = validateRoomClassForm({
        name: 'a'.repeat(ROOM_CLASS_NAME_MAX_LENGTH + 1),
        code: 'b'.repeat(ROOM_CLASS_CODE_MAX_LENGTH + 1),
        price: '1000',
        description: 'c'.repeat(ROOM_CLASS_DESCRIPTION_MAX_LENGTH + 1),
        aplicare_code: 'd'.repeat(ROOM_CLASS_APLICARE_CODE_MAX_LENGTH + 1),
        sirs_code: 'e'.repeat(ROOM_CLASS_SIRS_CODE_MAX_LENGTH + 1),
      })

      expect(errors).toEqual({
        name: `Nama kelas maksimal ${ROOM_CLASS_NAME_MAX_LENGTH} karakter.`,
        code: `Kode kelas maksimal ${ROOM_CLASS_CODE_MAX_LENGTH} karakter.`,
        description: `Deskripsi kelas maksimal ${ROOM_CLASS_DESCRIPTION_MAX_LENGTH} karakter.`,
        aplicare_code: `Kode Aplicare maksimal ${ROOM_CLASS_APLICARE_CODE_MAX_LENGTH} karakter.`,
        sirs_code: `Kode SIRS maksimal ${ROOM_CLASS_SIRS_CODE_MAX_LENGTH} karakter.`,
      })
    })
  })

  it('toRoomClassPayload merapikan spasi, mengonversi harga terformat ke angka, dan menormalkan field opsional kosong ke null', () => {
    expect(
      toRoomClassPayload({
        name: '  Kelas   VIP ',
        code: ' VIP-1 ',
        price: '750.000',
        description: '   ',
        aplicare_code: '',
        sirs_code: ' VIP ',
      }),
    ).toEqual({
      name: 'Kelas VIP',
      code: 'VIP-1',
      price: 750000,
      description: null,
      aplicare_code: null,
      sirs_code: 'VIP',
    })
  })

  describe('getSafeRoomClassErrorMessage', () => {
    it('meneruskan pesan 4xx dan menyaring pesan 5xx atau SQLSTATE', () => {
      expect(
        getSafeRoomClassErrorMessage(
          {
            status: 422,
            message: 'Data kelas tidak dapat dihapus karena masih digunakan oleh data kamar.',
          },
          'Fallback',
        ),
      ).toBe('Data kelas tidak dapat dihapus karena masih digunakan oleh data kamar.')

      expect(
        getSafeRoomClassErrorMessage(
          { status: 500, message: 'SQLSTATE[42S02]: Table not found' },
          'Data kelas gagal dimuat.',
        ),
      ).toBe('Data kelas gagal dimuat.')
    })
  })
})
