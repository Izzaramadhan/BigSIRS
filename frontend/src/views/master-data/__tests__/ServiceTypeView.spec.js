import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { ref } from 'vue'
import ServiceTypeView from '../ServiceTypeView.vue'
import { useServiceTypes } from '@/composables/useServiceTypes'

vi.mock('@/composables/useServiceTypes', () => ({
  useServiceTypes: vi.fn()
}))

describe('ServiceTypeView', () => {
  const mockFetch = vi.fn()
  const mockDelete = vi.fn()
  
  beforeEach(() => {
    vi.clearAllMocks()
    useServiceTypes.mockReturnValue({
      serviceTypes: ref([
        { id: 1, name: 'Penyakit Dalam' },
        { id: 2, name: 'Kesehatan Anak' }
      ]),
      loading: ref(false),
      error: ref(null),
      meta: ref({ current_page: 1, last_page: 1, total: 2, per_page: 10 }),
      fetchServiceTypes: mockFetch,
      deleteServiceType: mockDelete
    })
  })

  it('renders view correctly', () => {
    const wrapper = mount(ServiceTypeView, {
      global: {
        stubs: {
          ServiceTypeFormModal: true,
          Teleport: true
        }
      }
    })

    expect(wrapper.find('.page-title').text()).toBe('Jenis Layanan')
    expect(wrapper.text()).toContain('Penyakit Dalam')
    expect(wrapper.text()).toContain('Kesehatan Anak')
  })

  it('calls fetchServiceTypes on mount', () => {
    mount(ServiceTypeView, {
      global: {
        stubs: {
          ServiceTypeFormModal: true,
          Teleport: true
        }
      }
    })

    expect(mockFetch).toHaveBeenCalled()
  })
})
