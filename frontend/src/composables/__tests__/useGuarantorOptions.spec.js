import { describe, it, expect, vi, beforeEach } from 'vitest'
import lookupService from '@/services/lookup.service'
import {
  findMatchingGuarantor,
  isSelfPayGuarantor,
  mergeSavedGuarantorOption,
  useGuarantorOptions,
} from '../useGuarantorOptions'

vi.mock('@/services/lookup.service', () => ({
  default: {
    getGuarantors: vi.fn(),
  },
}))

describe('useGuarantorOptions', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('loads guarantors dynamically and exposes loading/empty/error states', async () => {
    lookupService.getGuarantors.mockResolvedValueOnce([
      { id: 2, code: 'UMM', name: 'Bayar Tunai', type: 'self_pay', is_active: true },
      { id: 1, code: 'BPJS', name: 'BPJS Kesehatan', type: 'bpjs', is_active: true },
    ])

    const state = useGuarantorOptions()
    await state.fetchGuarantors()

    expect(state.loading.value).toBe(false)
    expect(state.error.value).toBe('')
    expect(state.isEmpty.value).toBe(false)
    expect(state.guarantors.value).toHaveLength(2)
    expect(state.guarantors.value[0].is_self_pay).toBe(true)

    lookupService.getGuarantors.mockResolvedValueOnce([])
    await state.fetchGuarantors()
    expect(state.isEmpty.value).toBe(true)

    lookupService.getGuarantors.mockRejectedValueOnce(new Error('500'))
    await state.fetchGuarantors()
    expect(state.error.value).toContain('Gagal memuat data penjamin')
  })

  it('preserves historical inactive or deleted guarantor option and matches safely', () => {
    const active = [
      { id: 1, code: 'BPJS', name: 'BPJS Kesehatan', type: 'bpjs', is_active: true },
      { id: 2, code: 'UMM', name: 'UMUM', type: 'self_pay', is_active: true },
    ]

    const merged = mergeSavedGuarantorOption(active, { id: 88, name: 'Asuransi Arsip Lama' })
    expect(merged.some((g) => g.id === '88' && g.is_active === false && g.label.includes('Historis'))).toBe(true)

    const matchedSelfPay = findMatchingGuarantor(merged, { name: 'Pasien Umum / Mandiri' })
    expect(matchedSelfPay?.id).toBe('2')
    expect(isSelfPayGuarantor({ type: 'self_pay', name: 'Bayar Mandiri' })).toBe(true)
  })
})
