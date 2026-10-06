import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import DietTypeFormModal from '../DietTypeFormModal.vue'
import { useDietTypes } from '@/composables/useDietTypes'

vi.mock('@/composables/useDietTypes', () => ({
  useDietTypes: vi.fn()
}))

describe('DietTypeFormModal', () => {
  const mockCreate = vi.fn()
  const mockUpdate = vi.fn()
  const mockLoading = { value: false }

  beforeEach(() => {
    vi.clearAllMocks()
    useDietTypes.mockReturnValue({
      createDietType: mockCreate,
      updateDietType: mockUpdate,
      loading: mockLoading
    })
  })

  it('renders correctly for add new', async () => {
    const wrapper = mount(DietTypeFormModal, {
      props: {
        isOpen: true,
        DietType: null
      },
      global: {
        stubs: {
          Teleport: true
        }
      }
    })

    expect(wrapper.find('.modal-title').text()).toBe('Tambah Asuhan Gizi')
  })

  it('renders correctly for edit', async () => {
    const wrapper = mount(DietTypeFormModal, {
      props: {
        isOpen: false,
        DietType: {
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

    expect(wrapper.find('.modal-title').text()).toBe('Edit Asuhan Gizi')
    expect(wrapper.find('input#name').element.value).toBe('Penyakit Dalam')
  })

  it('validates empty name', async () => {
    const wrapper = mount(DietTypeFormModal, {
      props: {
        isOpen: true,
        DietType: null
      },
      global: {
        stubs: {
          Teleport: true
        }
      }
    })

    await wrapper.find('form').trigger('submit.prevent')

    expect(wrapper.text()).toContain('Nama Asuhan Gizi wajib diisi')
    expect(mockCreate).not.toHaveBeenCalled()
  })

  it('submits form successfully', async () => {
    mockCreate.mockResolvedValueOnce({ data: { id: 1 } })

    const wrapper = mount(DietTypeFormModal, {
      props: {
        isOpen: true,
        DietType: null
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
      description: '',
      is_active: true
    })

    // wait for promises
    await new Promise(resolve => setTimeout(resolve, 0))

    expect(wrapper.emitted('success')).toBeTruthy()
  })
})
