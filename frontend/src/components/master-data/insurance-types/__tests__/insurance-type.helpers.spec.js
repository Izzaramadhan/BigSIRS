import { describe, expect, it } from 'vitest'
import {
  createInsuranceTypeForm,
  EMPTY,
  formatInacbgId,
  formatInsuranceGovernment,
  formatInsuranceName,
  formatInsuranceSummary,
  getSafeInsuranceErrorMessage,
  INACBG_ID_MAX_LENGTH,
  INSURANCE_NAME_MAX_LENGTH,
  toInsuranceTypePayload,
  validateInsuranceTypeForm,
} from '../insurance-type.helpers'

describe('insurance-type.helpers', () => {
  it('formats display columns cleanly with fallback values', () => {
    expect(formatInsuranceName({ name: 'BPJS KESEHATAN' })).toBe('BPJS KESEHATAN')
    expect(formatInsuranceName({ name: '  ' })).toBe(EMPTY)
    expect(formatInsuranceGovernment({ is_government: true })).toBe('Ya')
    expect(formatInsuranceGovernment({ is_government: false })).toBe('Tidak')
    expect(formatInacbgId({ inacbg_id: '3' })).toBe('3')
    expect(formatInacbgId({ inacbg_id: null })).toBe(EMPTY)
    expect(formatInacbgId({ inacbg_id: '   ' })).toBe(EMPTY)
    expect(formatInsuranceSummary({ name: 'UMUM' })).toBe('UMUM')
    expect(formatInsuranceSummary(null)).toBe('Jenis Asuransi')
  })

  it('creates form state accurately including numeric inacbg_id and nullable fields', () => {
    expect(createInsuranceTypeForm()).toEqual({
      name: '',
      is_government: false,
      inacbg_id: '',
    })

    expect(
      createInsuranceTypeForm({
        id: 2,
        name: 'JKN',
        is_government: 1,
        inacbg_id: 3,
      }),
    ).toEqual({
      name: 'JKN',
      is_government: true,
      inacbg_id: '3',
    })
  })

  it('validates required name, max 150 name, boolean is_government, and max 10 inacbg_id', () => {
    expect(validateInsuranceTypeForm({ name: '  ', is_government: null, inacbg_id: '' })).toEqual({
      name: 'Nama jenis asuransi wajib diisi.',
      is_government: 'Status asuransi pemerintah wajib dipilih.',
    })

    const tooLong = validateInsuranceTypeForm({
      name: 'A'.repeat(INSURANCE_NAME_MAX_LENGTH + 1),
      is_government: false,
      inacbg_id: '1'.repeat(INACBG_ID_MAX_LENGTH + 1),
    })

    expect(tooLong.name).toBe(
      `Nama jenis asuransi maksimal ${INSURANCE_NAME_MAX_LENGTH} karakter.`,
    )
    expect(tooLong.inacbg_id).toBe(`ID INACBG maksimal ${INACBG_ID_MAX_LENGTH} karakter.`)
  })

  it('builds clean payload and preserves legacy code/type on edit only when present', () => {
    expect(
      toInsuranceTypePayload({
        name: '  BPJS   Kesehatan ',
        is_government: true,
        inacbg_id: '  ',
      }),
    ).toEqual({
      name: 'BPJS Kesehatan',
      is_government: true,
      inacbg_id: null,
    })

    expect(
      toInsuranceTypePayload(
        { name: 'Mandiri Inhealth', is_government: false, inacbg_id: '12' },
        { id: 9, code: 'ASR-09', type: 'private_insurance' },
      ),
    ).toEqual({
      name: 'Mandiri Inhealth',
      is_government: false,
      inacbg_id: '12',
      code: 'ASR-09',
      type: 'private_insurance',
    })

    // Legacy record with null code still works without forcing empty string
    expect(
      toInsuranceTypePayload(
        { name: 'Asuransi Legacy Tanpa Kode', is_government: false, inacbg_id: '' },
        { id: 10, code: null, type: null },
      ),
    ).toEqual({
      name: 'Asuransi Legacy Tanpa Kode',
      is_government: false,
      inacbg_id: null,
    })
  })

  it('sanitizes 5xx and technical SQL errors while keeping clean 4xx messages', () => {
    expect(
      getSafeInsuranceErrorMessage(
        {
          status: 422,
          message: 'Penjamin tidak dapat dihapus karena sudah digunakan pada data admisi pasien.',
        },
        'Fallback',
      ),
    ).toBe('Penjamin tidak dapat dihapus karena sudah digunakan pada data admisi pasien.')

    expect(
      getSafeInsuranceErrorMessage(
        { status: 500, message: 'SQLSTATE[42S02]: table error' },
        'Fallback',
      ),
    ).toBe('Fallback')

    expect(
      getSafeInsuranceErrorMessage(
        { status: 400, message: 'PDOException: connection refused' },
        'Fallback',
      ),
    ).toBe('Fallback')
  })
})
