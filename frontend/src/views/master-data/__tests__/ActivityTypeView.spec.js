import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { ref } from 'vue'
import ActivityTypeView from '../ActivityTypeView.vue'
import { useActivityTypes } from '@/composables/useActivityTypes'

vi.mock('@/composables/useActivityTypes', () => ({
  useActivityTypes: vi.fn()
}))

describe('ActivityTypeView', () => {
  const mockFetch = vi.fn()
  const mockDelete = vi.fn()
  
  beforeEach(() => {
    vi.clearAllMocks()
    useActivityTypes.mockReturnValue({
      activityTypes: ref([
        { id: 1, name: 'Penyakit Dalam' },
        { id: 2, name: 'Kesehatan Anak' }
      ]),
      loading: ref(false),
      error: ref(null),
      meta: ref({ current_page: 1, last_page: 1, total: 2, per_page: 10 }),
      fetchActivityTypes: mockFetch,
      deleteActivityType: mockDelete
    })
  })

  it('renders view correctly', () => {
    const wrapper = mount(ActivityTypeView, {
      global: {
        stubs: {
          ActivityTypeFormModal: true,
          Teleport: true
        }
      }
    })

    expect(wrapper.find('.page-title').text()).toBe('Jenis Kegiatan')
    expect(wrapper.text()).toContain('Penyakit Dalam')
    expect(wrapper.text()).toContain('Kesehatan Anak')
  })

  it('calls fetchActivityTypes on mount', () => {
    mount(ActivityTypeView, {
      global: {
        stubs: {
          ActivityTypeFormModal: true,
          Teleport: true
        }
      }
    })

    expect(mockFetch).toHaveBeenCalled()
  })
})
