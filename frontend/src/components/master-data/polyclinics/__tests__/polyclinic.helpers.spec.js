import { describe, expect, it } from 'vitest'
import {
  createPolyclinicForm,
  EMPTY,
  ensureSavedWarehouseOptions,
  formatPolyclinicCode,
  formatPolyclinicName,
  formatPolyclinicQuota,
  formatPolyclinicServiceType,
  formatPolyclinicSummary,
  formatPolyclinicWarehouse,
  getSafePolyclinicErrorMessage,
  POLYCLINIC_BPJS_CODE_MAX_LENGTH,
  POLYCLINIC_CODE_MAX_LENGTH,
  POLYCLINIC_DESCRIPTION_MAX_LENGTH,
  POLYCLINIC_NAME_MAX_LENGTH,
  toPolyclinicPayload,
  validatePolyclinicForm,
} from '../polyclinic.helpers'

describe('polyclinic.helpers', () => {
  it('formats display fields cleanly with fallback values', () => {
    expect(formatPolyclinicCode({ code: 'POLI-01' })).toBe('POLI-01')
    expect(formatPolyclinicCode({ code: '  ' })).toBe(EMPTY)
    expect(formatPolyclinicName({ name: 'Poli Umum' })).toBe('Poli Umum')
    expect(formatPolyclinicName(null)).toBe(EMPTY)
    expect(
      formatPolyclinicServiceType({ service_type: 'rawat-jalan', service_type_label: 'Rawat Jalan' }),
    ).toBe('Rawat Jalan')
    expect(formatPolyclinicServiceType({ service_type: 'farmasi' })).toBe('farmasi')
    expect(formatPolyclinicServiceType({})).toBe(EMPTY)
    expect(formatPolyclinicWarehouse({ warehouse: { code: 'W1', name: 'Gudang 1' } })).toBe(
      'W1 — Gudang 1',
    )
    expect(formatPolyclinicWarehouse({ warehouse: { name: 'Gudang Farmasi' } })).toBe(
      'Gudang Farmasi',
    )
    expect(formatPolyclinicWarehouse({})).toBe('Belum ditentukan')
    expect(formatPolyclinicQuota(20)).toBe('20')
    expect(formatPolyclinicQuota(null)).toBe('0')
    expect(formatPolyclinicSummary({ code: 'POLI-01', name: 'Poli Umum' })).toBe(
      'POLI-01 - Poli Umum',
    )
  })

  it('keeps saved warehouse option when absent from active lookup list', () => {
    const merged = ensureSavedWarehouseOptions(
      [{ id: 1, code: 'W1', name: 'Gudang 1' }],
      { warehouse_id: 99, warehouse: { id: 99, code: 'W99', name: 'Gudang Lama' } },
    )
    expect(merged).toHaveLength(2)
    expect(merged[0]).toEqual({ id: 99, code: 'W99', name: 'Gudang Lama' })
  })

  it('creates clean initial form state for create and edit modes', () => {
    expect(createPolyclinicForm()).toEqual({
      code: '',
      name: '',
      service_type: '',
      description: '',
      is_visible: true,
      is_online_visible: false,
      quota: 0,
      jkn_quota: 0,
      warehouse_id: '',
      bpjs_code: '',
    })

    expect(
      createPolyclinicForm({
        code: 'POLI-02',
        name: 'Gigi',
        service_type: 'rawat-jalan',
        is_visible: false,
        is_online_visible: true,
        quota: 15,
        jkn_quota: 5,
        warehouse_id: 3,
        bpjs_code: 'GIG',
      }),
    ).toEqual({
      code: 'POLI-02',
      name: 'Gigi',
      service_type: 'rawat-jalan',
      description: '',
      is_visible: false,
      is_online_visible: true,
      quota: 15,
      jkn_quota: 5,
      warehouse_id: 3,
      bpjs_code: 'GIG',
    })
  })

  it('validates required fields, max lengths, and non-negative integer quotas', () => {
    expect(validatePolyclinicForm({ code: '', name: '', service_type: '' })).toEqual({
      code: 'Kode poliklinik wajib diisi.',
      name: 'Nama poliklinik wajib diisi.',
      service_type: 'Jenis layanan wajib dipilih.',
    })

    const tooLong = validatePolyclinicForm({
      code: 'A'.repeat(POLYCLINIC_CODE_MAX_LENGTH + 1),
      name: 'B'.repeat(POLYCLINIC_NAME_MAX_LENGTH + 1),
      service_type: 'rawat-jalan',
      description: 'C'.repeat(POLYCLINIC_DESCRIPTION_MAX_LENGTH + 1),
      bpjs_code: 'D'.repeat(POLYCLINIC_BPJS_CODE_MAX_LENGTH + 1),
      quota: -1,
      jkn_quota: 1.5,
    })

    expect(tooLong.code).toContain('maksimal')
    expect(tooLong.name).toContain('maksimal')
    expect(tooLong.description).toContain('maksimal')
    expect(tooLong.bpjs_code).toContain('maksimal')
    expect(tooLong.quota).toContain('minimal 0')
    expect(tooLong.jkn_quota).toContain('minimal 0')
  })

  it('builds normalized payload without parent_id or satusehat_code', () => {
    const payload = toPolyclinicPayload({
      code: ' poli-test ',
      name: '  Poli   Penyakit   Dalam ',
      service_type: 'rawat-jalan',
      description: '   ',
      is_visible: true,
      is_online_visible: false,
      quota: '12',
      jkn_quota: '',
      warehouse_id: '',
      bpjs_code: '  INT  ',
      parent_id: 99,
      satusehat_code: 'SS-1',
    })

    expect(payload).toEqual({
      code: 'POLI-TEST',
      name: 'Poli Penyakit Dalam',
      service_type: 'rawat-jalan',
      description: null,
      is_visible: true,
      is_online_visible: false,
      quota: 12,
      jkn_quota: 0,
      warehouse_id: null,
      bpjs_code: 'INT',
    })
    expect(payload).not.toHaveProperty('parent_id')
    expect(payload).not.toHaveProperty('satusehat_code')
  })

  it('sanitizes 5xx and technical SQL error messages', () => {
    expect(
      getSafePolyclinicErrorMessage({ status: 409, message: 'Poliklinik masih dipakai' }, 'Fallback'),
    ).toBe('Poliklinik masih dipakai')
    expect(
      getSafePolyclinicErrorMessage({ status: 500, message: 'SQLSTATE[42S02]: table error' }, 'Fallback'),
    ).toBe('Fallback')
    expect(
      getSafePolyclinicErrorMessage({ status: 400, message: 'PDOException: connection refused' }, 'Fallback'),
    ).toBe('Fallback')
  })
})
