import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import ActivityTypeFormModal from '../ActivityTypeFormModal.vue'
import { useActivityTypes } from '@/composables/useActivityTypes'

vi.mock('@/composables/useActivityTypes', () => ({
  useActivityTypes: vi.fn()
}))

describe('ActivityTypeFormModal', () => {
  const mockCreate = vi.fn()
  const mockUpdate = vi.fn()
  const mockLookup = vi.fn().mockResolvedValue([])
  const mockLoading = { value: false }

  beforeEach(() => {
    vi.clearAllMocks()
    useActivityTypes.mockReturnValue({
      createActivityType: mockCreate,
      updateActivityType: mockUpdate,
      lookupActivityTypes: mockLookup,
      loading: mockLoading
    })
  })

  it('renders correctly for add new', async () => {
    const wrapper = mount(ActivityTypeFormModal, {
      props: {
        isOpen: true,
        activityType: null
      },
      global: {
        stubs: {
          Teleport: true
        }
      }
    })

    expect(wrapper.find('.modal-title').text()).toBe('Tambah Jenis Kegiatan')
  })

  it('renders correctly for edit', async () => {
    const wrapper = mount(ActivityTypeFormModal, {
      props: {
        isOpen: false,
        activityType: {
          id: 1,
          name: 'Penyakit Dalam'
        }
      },
      global: {
        stubs: {
          Teleport: true
        }
      }
    })

    await wrapper.setProps({ isOpen: true })

    expect(wrapper.find('.modal-title').text()).toBe('Edit Jenis Kegiatan')
    expect(wrapper.find('input#name').element.value).toBe('Penyakit Dalam')
  })

  it('validates empty name', async () => {
    const wrapper = mount(ActivityTypeFormModal, {
      props: {
        isOpen: true,
        activityType: null
      },
      global: {
        stubs: {
          Teleport: true
        }
      }
    })

    await wrapper.find('form').trigger('submit.prevent')

    expect(wrapper.text()).toContain('Nama Jenis Kegiatan wajib diisi')
    expect(mockCreate).not.toHaveBeenCalled()
  })

  it('submits form successfully', async () => {
    mockCreate.mockResolvedValueOnce({ data: { id: 1 } })

    const wrapper = mount(ActivityTypeFormModal, {
      props: {
        isOpen: true,
        activityType: null
      },
      global: {
        stubs: {
          Teleport: true
        }
      }
    })

    await wrapper.find('input#name').setValue('Kesehatan Anak')
    await wrapper.find('form').trigger('submit.prevent')

    expect(mockCreate).toHaveBeenCalledWith({
      name: 'Kesehatan Anak',
      parent_id: null,
      is_active: true
    })

    // wait for promises
    await new Promise(resolve => setTimeout(resolve, 0))

    expect(wrapper.emitted('success')).toBeTruthy()
  })
})
