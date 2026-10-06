import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import VillageFormModal from '../VillageFormModal.vue'
import LookupService from '@/services/lookup.service'

vi.mock('@/services/lookup.service')
vi.mock('@/composables/useVillages', () => ({
  useVillages: () => ({
    createVillage: vi.fn(),
    updateVillage: vi.fn(),
    loading: false
  })
}))

describe('VillageFormModal.vue', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    LookupService.getRegencies.mockResolvedValue([{ id: 1, name: 'Regency A' }])
    LookupService.getDistricts.mockResolvedValue([{ id: 10, name: 'District A' }])
  })

  it('renders correctly when open', async () => {
    const wrapper = mount(VillageFormModal, {
      props: {
        isOpen: true
      },
      global: {
        stubs: {
          Teleport: true
        }
      }
    })
    
    // Check if it renders
    expect(wrapper.find('.modal-content').exists()).toBe(true)
    expect(wrapper.find('.modal-title').text()).toBe('Tambah Kelurahan')
  })

  it('loads regencies on open', async () => {
    const wrapper = mount(VillageFormModal, {
      props: {
        isOpen: false
      }
    })
    
    await wrapper.setProps({ isOpen: true })
    expect(LookupService.getRegencies).toHaveBeenCalled()
  })

  it('validates required fields', async () => {
    const wrapper = mount(VillageFormModal, {
      props: { isOpen: true },
      global: {
        stubs: {
          Teleport: true
        }
      }
    })

    await wrapper.find('form').trigger('submit.prevent')
    
    const errors = wrapper.findAll('.error-text')
    expect(errors.length).toBeGreaterThan(0)
  })
})
