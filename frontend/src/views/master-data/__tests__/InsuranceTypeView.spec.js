import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { ref } from 'vue'
import InsuranceTypeView from '../InsuranceTypeView.vue'
import { useGuarantors } from '@/composables/useGuarantors'

vi.mock('@/composables/useGuarantors', () => ({
  useGuarantors: vi.fn()
}))

describe('InsuranceTypeView', () => {
  const mockFetch = vi.fn()
  const mockCreate = vi.fn()
  const mockUpdate = vi.fn()
  const mockDelete = vi.fn()

  beforeEach(() => {
    vi.clearAllMocks()
    useGuarantors.mockReturnValue({
      guarantors: ref([
        { id: 1, name: 'UMUM', is_government: false, inacbg_id: null },
        { id: 2, name: 'JKN', is_government: true, inacbg_id: '3' }
      ]),
      loading: ref(false),
      error: ref(null),
      meta: ref({ current_page: 1, last_page: 1, total: 2, per_page: 10, from: 1, to: 2 }),
      fetchGuarantors: mockFetch,
      createGuarantor: mockCreate,
      updateGuarantor: mockUpdate,
      deleteGuarantor: mockDelete
    })
  })

  it('renders view correctly with required columns and data', () => {
    const wrapper = mount(InsuranceTypeView, {
      global: {
        stubs: {
          InsuranceTypeFormModal: true,
          Teleport: true
        }
      }
    })

    expect(wrapper.find('.page-title').text()).toBe('Jenis Asuransi')
    
    // Check column headers
    const headers = wrapper.findAll('th').map(th => th.text().trim())
    expect(headers).toContain('No')
    expect(headers).toContain('Jenis Asuransi')
    expect(headers).toContain('Asuransi Pemerintah')
    expect(headers).toContain('ID INACBG')
    expect(headers).toContain('Aksi')

    // Confirm Status column is NOT present
    expect(headers).not.toContain('Status')
    expect(headers).not.toContain('Status Aktif')

    // Check data rows
    expect(wrapper.text()).toContain('UMUM')
    expect(wrapper.text()).toContain('Tidak')
    expect(wrapper.text()).toContain('JKN')
    expect(wrapper.text()).toContain('Ya')
    expect(wrapper.text()).toContain('3')
  })

  it('calls fetchGuarantors on mount', () => {
    mount(InsuranceTypeView, {
      global: {
        stubs: {
          InsuranceTypeFormModal: true,
          Teleport: true
        }
      }
    })

    expect(mockFetch).toHaveBeenCalled()
  })

  it('opens add modal when Tambah button is clicked', async () => {
    const wrapper = mount(InsuranceTypeView, {
      global: {
        stubs: {
          InsuranceTypeFormModal: true,
          Teleport: true
        }
      }
    })

    const addBtn = wrapper.find('.btn-primary')
    await addBtn.trigger('click')

    const modal = wrapper.findComponent({ name: 'InsuranceTypeFormModal' })
    expect(modal.props('isOpen')).toBe(true)
    expect(modal.props('insuranceType')).toBeNull()
  })

  it('opens edit modal with selected data when edit action is clicked', async () => {
    const wrapper = mount(InsuranceTypeView, {
      global: {
        stubs: {
          InsuranceTypeFormModal: true,
          Teleport: true
        }
      }
    })

    const editBtns = wrapper.findAll('.btn-action.edit')
    await editBtns[0].trigger('click')

    const modal = wrapper.findComponent({ name: 'InsuranceTypeFormModal' })
    expect(modal.props('isOpen')).toBe(true)
    expect(modal.props('insuranceType')).toEqual({
      id: 1,
      name: 'UMUM',
      is_government: false,
      inacbg_id: null
    })
  })

  it('renders search input and refresh button in filters toolbar', async () => {
    const wrapper = mount(InsuranceTypeView, {
      global: {
        stubs: {
          InsuranceTypeFormModal: true,
          Teleport: true
        }
      }
    })

    const searchInput = wrapper.find('.search-input')
    expect(searchInput.exists()).toBe(true)

    const refreshBtn = wrapper.find('.btn-icon')
    expect(refreshBtn.exists()).toBe(true)

    mockFetch.mockClear()
    await refreshBtn.trigger('click')
    expect(mockFetch).toHaveBeenCalled()
  })

  it('renders pagination at the bottom with correct info and buttons', async () => {
    useGuarantors.mockReturnValue({
      guarantors: ref(
        Array.from({ length: 10 }, (_, i) => ({
          id: i + 1,
          name: `Asuransi ${i + 1}`,
          is_government: false,
          inacbg_id: null
        }))
      ),
      loading: ref(false),
      error: ref(null),
      meta: ref({ current_page: 1, last_page: 2, total: 18, per_page: 10, from: 1, to: 10 }),
      fetchGuarantors: mockFetch,
      createGuarantor: mockCreate,
      updateGuarantor: mockUpdate,
      deleteGuarantor: mockDelete
    })

    const wrapper = mount(InsuranceTypeView, {
      global: {
        stubs: {
          InsuranceTypeFormModal: true,
          Teleport: true
        }
      }
    })

    const pagination = wrapper.findComponent({ name: 'MasterDataPagination' })
    expect(pagination.exists()).toBe(true)
    expect(pagination.text()).toContain('Menampilkan 1')
    expect(pagination.text()).toContain('sampai 10')
    expect(pagination.text()).toContain('dari 18 entri')
    expect(pagination.text()).toContain('Sebelumnya')
    expect(pagination.text()).toContain('1')
    expect(pagination.text()).toContain('2')
    expect(pagination.text()).toContain('Selanjutnya')

    mockFetch.mockClear()
    await pagination.vm.$emit('page-change', 2)
    expect(mockFetch).toHaveBeenCalledWith(
      expect.objectContaining({
        page: 2,
        per_page: 10
      })
    )
  })
})
