import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { ref } from 'vue'
import DietTypeView from '../DietTypeView.vue'
import { useDietTypes } from '@/composables/useDietTypes'

vi.mock('@/composables/useDietTypes', () => ({
  useDietTypes: vi.fn()
}))

describe('DietTypeView', () => {
  const mockFetch = vi.fn()
  const mockDelete = vi.fn()
  
  beforeEach(() => {
    vi.clearAllMocks()
    useDietTypes.mockReturnValue({
      dietTypes: ref([
        { id: 1, name: 'Penyakit Dalam' },
        { id: 2, name: 'Kesehatan Anak' }
      ]),
      loading: ref(false),
      error: ref(null),
      meta: ref({ current_page: 1, last_page: 1, total: 2, per_page: 10 }),
      fetchDietTypes: mockFetch,
      deleteDietType: mockDelete
    })
  })

  it('renders view correctly', () => {
    const wrapper = mount(DietTypeView, {
      global: {
        stubs: {
          DietTypeFormModal: true,
          Teleport: true
        }
      }
    })

    expect(wrapper.find('.page-title').text()).toBe('Asuhan Gizi')
    expect(wrapper.text()).toContain('Penyakit Dalam')
    expect(wrapper.text()).toContain('Kesehatan Anak')
  })

  it('calls fetchDietTypes on mount', () => {
    mount(DietTypeView, {
      global: {
        stubs: {
          DietTypeFormModal: true,
          Teleport: true
        }
      }
    })

    expect(mockFetch).toHaveBeenCalled()
  })
})
