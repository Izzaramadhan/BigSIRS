import { describe, expect, it } from 'vitest'
import {
  createDoctorForm,
  EMPTY,
  formatDateForInput,
  formatDoctorDate,
  formatDoctorGender,
  formatDoctorName,
  formatDoctorSpecialization,
  getSafeDoctorErrorMessage,
  hasDoctorDetailPayload,
  resolveDoctorFieldError,
  resolveDoctorSpecializationId,
  SIGNATURE_MAX_BYTES,
  toDoctorPayload,
  validateDoctorForm,
  validateDoctorSignatureFile,
} from '../doctor.helpers'

describe('doctor.helpers', () => {
  it('memformat nama, spesialisasi, gender, dan tanggal dengan fallback tanda kosong', () => {
    expect(formatDoctorName({ name: 'dr. Budi, Sp.A' })).toBe('dr. Budi, Sp.A')
    expect(formatDoctorName({ employee: { name: 'dr. Adi' } })).toBe('dr. Adi')
    expect(formatDoctorName(null)).toBe(EMPTY)

    expect(formatDoctorSpecialization({ specialization: { name: 'Anak' } })).toBe('Anak')
    expect(formatDoctorSpecialization({ specialization: null })).toBe(EMPTY)

    expect(formatDoctorGender('L')).toBe('Laki-laki')
    expect(formatDoctorGender('P')).toBe('Perempuan')
    expect(formatDoctorGender('')).toBe(EMPTY)

    expect(formatDateForInput('2028-12-31T00:00:00.000000Z')).toBe('2028-12-31')
    expect(formatDoctorDate('')).toBe(EMPTY)
  })

  it('menangani resolusi specialization_id internal maupun legacy id_spesialisasi dan legacy_id', () => {
    const options = [
      { id: 20, legacy_id: 3, name: 'Spesialis Anak' },
      { id: 21, legacy_id: 8, name: 'Penyakit Dalam' },
    ]

    expect(resolveDoctorSpecializationId({ specialization_id: 21 }, options)).toBe(21)
    expect(resolveDoctorSpecializationId({ id_spesialisasi: 3 }, options)).toBe(20)
    expect(resolveDoctorSpecializationId({ specialization: { id: 8 } }, options)).toBe(21)
    expect(resolveDoctorSpecializationId({}, options)).toBe('')
  })

  it('menginisialisasi form edit dokter termasuk Nomor HP, Alergi, relasi pegawai, dan wilayah legacy', () => {
    expect(hasDoctorDetailPayload({ id: 1, name: 'dr. A', specialization: null })).toBe(false)
    expect(hasDoctorDetailPayload({ id: 1, employee_id: 5 })).toBe(true)

    const form = createDoctorForm(
      {
        id: 12,
        employee_id: 5,
        id_spesialisasi: 3,
        no_str: 'STR-123',
        sip: 'SIP-456',
        masa_berlaku: '2029-05-01T00:00:00.000000Z',
        kode_dpjp: '12345',
        is_active: true,
        signature_url: '/storage/signatures/doc.png',
        person: {
          no_ktp: '3578010101800001',
          nama: 'dr. Ahmad, Sp.A',
          gender: 'L',
          no_hp: '081234567890',
          alergi: 'Penisilin',
          ihs_number: '10002000',
          id_prov: 35,
          id_kab: 3578,
          id_kec: 357801,
          id_kel: 3578011,
        },
      },
      [{ id: 20, legacy_id: 3, name: 'Spesialis Anak' }],
    )

    expect(form.employee_id).toBe(5)
    expect(form.person).toMatchObject({
      national_id: '3578010101800001',
      name: 'dr. Ahmad, Sp.A',
      gender: 'L',
      phone: '081234567890',
      allergies: 'Penisilin',
      ihs_number: '10002000',
      province_id: 35,
      regency_id: 3578,
      district_id: 357801,
      village_id: 3578011,
    })
    expect(form.professional).toMatchObject({
      specialization_id: 20,
      str_number: 'STR-123',
      sip_number: 'SIP-456',
      sip_valid_until: '2029-05-01',
      bpjs_dpjp_code: '12345',
      is_active: true,
    })
    expect(form.signature_url).toBe('/storage/signatures/doc.png')
  })

  it('memvalidasi field wajib, batas panjang, dan file tanda tangan', () => {
    const invalidPdf = new File(['x'], 'doc.pdf', { type: 'application/pdf' })
    const oversized = new File(['x'], 'big.png', { type: 'image/png' })
    Object.defineProperty(oversized, 'size', { value: SIGNATURE_MAX_BYTES + 1 })

    expect(validateDoctorSignatureFile(invalidPdf)).toContain('PNG atau JPG')
    expect(validateDoctorSignatureFile(oversized)).toContain('maksimal 2MB')

    expect(validateDoctorForm(createDoctorForm())).toEqual({
      'person.name': 'Nama lengkap wajib diisi.',
      'person.gender': 'Jenis kelamin wajib dipilih.',
      'professional.specialization_id': 'Spesialisasi wajib dipilih.',
    })

    const form = createDoctorForm()
    form.person.name = 'dr. Budi'
    form.person.gender = 'L'
    form.person.phone = '0'.repeat(21)
    form.professional.specialization_id = 1
    form.signature = invalidPdf

    const err = validateDoctorForm(form)
    expect(err['person.phone']).toContain('maksimal 20')
    expect(err.signature).toContain('PNG atau JPG')
  })

  it('membentuk payload nested person/professional bersih dan menyamarkan error teknis', () => {
    const form = createDoctorForm()
    form.employee_id = 9
    form.person.name = ' dr. Sari   Wijaya '
    form.person.gender = 'P'
    form.person.phone = ' 08111222333 '
    form.person.allergies = ' Debu '
    form.professional.specialization_id = 3

    const payload = toDoctorPayload(form)
    expect(payload.employee_id).toBe(9)
    expect(payload.person.name).toBe('dr. Sari Wijaya')
    expect(payload.person.phone).toBe('08111222333')
    expect(payload.person.allergies).toBe('Debu')
    expect(payload.professional.specialization_id).toBe(3)
    expect(payload).not.toHaveProperty('signature_url')

    expect(
      resolveDoctorFieldError({ 'person.gender': ['Jenis kelamin wajib dipilih.'] }, 'person.gender', 'gender'),
    ).toBe('Jenis kelamin wajib dipilih.')

    expect(
      getSafeDoctorErrorMessage(
        { status: 500, message: 'SQLSTATE[42S22]: Column not found' },
        'Data dokter gagal disimpan.',
      ),
    ).toBe('Data dokter gagal disimpan.')
  })
})
