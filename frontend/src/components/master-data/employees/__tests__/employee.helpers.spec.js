import { describe, expect, it } from 'vitest'
import {
  createEmployeeForm,
  EMPTY,
  formatBirthDateForInput,
  formatEmployeeCode,
  formatEmployeeName,
  formatEmployeePosition,
  getSafeEmployeeErrorMessage,
  resolveEmployeeDisplayName,
  resolveEmployeePositionId,
  SIGNATURE_MAX_BYTES,
  toEmployeePayload,
  validateEmployeeForm,
  validateSignatureFile,
} from '../employee.helpers'

describe('employee.helpers', () => {
  it('memformat kode, nama, jabatan, dan fallback tanda kosong dengan benar', () => {
    expect(formatEmployeeCode({ code: '19900101' })).toBe('19900101')
    expect(formatEmployeeCode({ nip: '19880202' })).toBe('19880202')
    expect(formatEmployeeCode({ code: '   ' })).toBe(EMPTY)

    expect(formatEmployeeName({ name: 'Budi Santoso' })).toBe('Budi Santoso')
    expect(formatEmployeeName({ nama: 'Siti Aminah' })).toBe('Siti Aminah')
    expect(formatEmployeeName(null)).toBe(EMPTY)

    expect(formatEmployeePosition({ position: { name: 'Perawat' } })).toBe('Perawat')
    expect(formatEmployeePosition({ position: null })).toBe(EMPTY)

    expect(resolveEmployeeDisplayName({ name: 'Budi', code: '001' })).toBe('Budi')
    expect(resolveEmployeeDisplayName({ code: '001' })).toBe('001')
    expect(resolveEmployeeDisplayName({})).toBe('pegawai ini')
  })

  it('menghandle resolusi position_id dari field internal, legacy id_jabatan, dan legacy_id opsi', () => {
    const options = [
      { id: 10, legacy_id: 4, name: 'Dokter Umum' },
      { id: 11, legacy_id: 7, name: 'Perawat' },
    ]

    expect(resolveEmployeePositionId({ position_id: 11 }, options)).toBe(11)
    expect(resolveEmployeePositionId({ id_jabatan: 4 }, options)).toBe(10)
    expect(resolveEmployeePositionId({ id_jabatan: 11 }, options)).toBe(11)
    expect(resolveEmployeePositionId({ position: { id: 7 } }, options)).toBe(11)
    expect(resolveEmployeePositionId({ position_id: '' }, options)).toBe('')
    expect(resolveEmployeePositionId(null, options)).toBe('')
  })

  it('memformat tanggal lahir ISO dan menginisialisasi form dari relasi legacy person', () => {
    expect(formatBirthDateForInput('1992-08-17T00:00:00.000000Z')).toBe('1992-08-17')
    expect(formatBirthDateForInput('')).toBe('')

    const form = createEmployeeForm(
      {
        nip: '19920817202001',
        nama: 'dr. Adi Putra',
        id_jabatan: 4,
        person: {
          no_ktp: '3578011708920001',
          tmp_lahir: 'Surabaya',
          tgl_lahir: '1992-08-17T00:00:00.000000Z',
          gol_darah: 'O',
          agama: 'Islam',
          stts_nikah: 'Kawin',
          kewarganegaraan: 'WNI',
          alergi: 'Tidak ada',
          id_pendidikan: 3,
          id_pekerjaan: 5,
          alamat: 'Jl. Dharmawangsa No. 1',
          id_prov: 35,
          id_kab: 3578,
          id_kec: 357801,
          id_kel: 3578011001,
          kode_pos: '60286',
          no_hp: '08123456789',
        },
        signature_url: '/storage/signatures/adi.png',
      },
      [{ id: 10, legacy_id: 4, name: 'Dokter Umum' }],
    )

    expect(form).toMatchObject({
      national_id: '3578011708920001',
      name: 'dr. Adi Putra',
      birth_place: 'Surabaya',
      birth_date: '1992-08-17',
      blood_type: 'O',
      education_id: 3,
      occupation_id: 5,
      province_id: 35,
      regency_id: 3578,
      district_id: 357801,
      village_id: 3578011001,
      postal_code: '60286',
      phone: '08123456789',
      code: '19920817202001',
      position_id: 10,
      signature_url: '/storage/signatures/adi.png',
      signature: null,
      remove_signature: false,
    })
  })

  it('memvalidasi file tanda tangan dan field wajib form pegawai', () => {
    const validPng = new File(['abc'], 'ttd.png', { type: 'image/png' })
    const invalidPdf = new File(['abc'], 'doc.pdf', { type: 'application/pdf' })
    const oversized = new File(['abc'], 'big.jpg', { type: 'image/jpeg' })
    Object.defineProperty(oversized, 'size', { value: SIGNATURE_MAX_BYTES + 1 })

    expect(validateSignatureFile(validPng)).toBe('')
    expect(validateSignatureFile(invalidPdf)).toContain('PNG atau JPG')
    expect(validateSignatureFile(oversized)).toContain('maksimal 2MB')

    expect(validateEmployeeForm(createEmployeeForm())).toEqual({
      national_id: 'Nomor KTP (NIK) wajib diisi.',
      name: 'Nama lengkap wajib diisi.',
      code: 'NIP wajib diisi.',
      position_id: 'Jabatan wajib dipilih.',
    })

    const longInputForm = {
      ...createEmployeeForm(),
      national_id: '1'.repeat(21),
      name: 'Budi Santoso',
      code: '1990',
      position_id: 1,
      postal_code: '12345678901',
      phone: '0'.repeat(21),
      signature: invalidPdf,
    }
    const longErrors = validateEmployeeForm(longInputForm)
    expect(longErrors.national_id).toContain('maksimal 20')
    expect(longErrors.postal_code).toContain('maksimal 10')
    expect(longErrors.phone).toContain('maksimal 20')
    expect(longErrors.signature).toContain('PNG atau JPG')
  })

  it('membentuk payload bersih tanpa signature_url dan menyamarkan pesan error teknis', () => {
    const file = new File(['sig'], 'sig.png', { type: 'image/png' })
    const payload = toEmployeePayload({
      national_id: ' 357801 ',
      name: '  Budi   Santoso ',
      code: ' 1990 ',
      position_id: 2,
      signature: file,
      signature_url: 'https://example.com/old.png',
      remove_signature: false,
    })

    expect(payload.national_id).toBe('357801')
    expect(payload.name).toBe('Budi Santoso')
    expect(payload.code).toBe('1990')
    expect(payload.signature).toBe(file)
    expect(payload).not.toHaveProperty('signature_url')

    expect(
      getSafeEmployeeErrorMessage(
        { status: 500, message: 'SQLSTATE[23000]: Integrity constraint violation' },
        'Gagal menyimpan pegawai.',
      ),
    ).toBe('Gagal menyimpan pegawai.')
    expect(
      getSafeEmployeeErrorMessage(
        { status: 422, message: 'NIK sudah terdaftar.' },
        'Gagal menyimpan pegawai.',
      ),
    ).toBe('NIK sudah terdaftar.')
  })
})
