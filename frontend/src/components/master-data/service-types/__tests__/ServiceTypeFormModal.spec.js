import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import ServiceTypeFormModal from '../ServiceTypeFormModal.vue'
import { useServiceTypes } from '@/composables/useServiceTypes'

vi.mock('@/composables/useServiceTypes', () => ({
  useServiceTypes: vi.fn()
}))

describe('ServiceTypeFormModal', () => {
  const mockCreate = vi.fn()
  const mockUpdate = vi.fn()
  const mockLoading = { value: false }

  beforeEach(() => {
    vi.clearAllMocks()
    useServiceTypes.mockReturnValue({
      createServiceType: mockCreate,
      updateServiceType: mockUpdate,
      loading: mockLoading
    })
  })

  it('renders correctly for add new', async () => {
    const wrapper = mount(ServiceTypeFormModal, {
      props: {
        isOpen: true,
        serviceType: null
      },
      global: {
        stubs: {
          Teleport: true
        }
      }
    })

    expect(wrapper.find('.modal-title').text()).toBe('Tambah Jenis Layanan')
  })

  it('renders correctly for edit', async () => {
    const wrapper = mount(ServiceTypeFormModal, {
      props: {
        isOpen: false,
        serviceType: {
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

    expect(wrapper.find('.modal-title').text()).toBe('Edit Jenis Layanan')
    expect(wrapper.find('input#name').element.value).toBe('Penyakit Dalam')
  })

  it('validates empty name', async () => {
    const wrapper = mount(ServiceTypeFormModal, {
      props: {
        isOpen: true,
        serviceType: null
      },
      global: {
        stubs: {
          Teleport: true
        }
      }
    })

    await wrapper.find('form').trigger('submit.prevent')

    expect(wrapper.text()).toContain('Nama jenis layanan wajib diisi')
    expect(mockCreate).not.toHaveBeenCalled()
  })

  it('submits form successfully', async () => {
    mockCreate.mockResolvedValueOnce({ data: { id: 1 } })

    const wrapper = mount(ServiceTypeFormModal, {
      props: {
        isOpen: true,
        serviceType: null
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
      is_active: true
    })

    // wait for promises
    await new Promise(resolve => setTimeout(resolve, 0))

    expect(wrapper.emitted('success')).toBeTruthy()
  })
})
