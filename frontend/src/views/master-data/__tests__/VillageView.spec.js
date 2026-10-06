import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import VillageView from '../VillageView.vue'
import LookupService from '@/services/lookup.service'

vi.mock('@/services/lookup.service')
vi.mock('@/composables/useVillages', () => ({
  useVillages: () => ({
    villages: [],
    meta: { last_page: 1, total: 0 },
    loading: false,
    error: null,
    fetchVillages: vi.fn(),
    deleteVillage: vi.fn()
  })
}))

describe('VillageView.vue', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    LookupService.getRegencies.mockResolvedValue([])
    LookupService.getDistricts.mockResolvedValue([])
  })

  it('renders correctly', () => {
    const wrapper = mount(VillageView)
    expect(wrapper.find('h1.page-title').text()).toBe('Kelurahan')
  })

  it('shows empty state when no data', () => {
    const wrapper = mount(VillageView)
    expect(wrapper.text()).toContain('Data tidak ditemukan')
  })
})
