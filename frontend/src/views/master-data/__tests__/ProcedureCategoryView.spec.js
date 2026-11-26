import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import ProcedureCategoryView from '../ProcedureCategoryView.vue'
import { procedureCategoryService } from '@/services/procedureCategory'

vi.mock('@/services/procedureCategory', () => {
  return {
    procedureCategoryService: {
      getProcedureCategories: vi.fn(),
      createProcedureCategory: vi.fn(),
      updateProcedureCategory: vi.fn(),
      archiveProcedureCategory: vi.fn()
    }
  }
})

describe('ProcedureCategoryView.vue', () => {
  beforeEach(() => {
    vi.clearAllMocks()

    procedureCategoryService.getProcedureCategories.mockResolvedValue({
      data: {
        data: [
          { id: 1, name: 'KGA', description: 'KGA Desc' },
          { id: 2, name: 'IPM', description: 'IPM Desc' }
        ],
        meta: {
          current_page: 1,
          last_page: 1,
          per_page: 15,
          total: 2
        }
      }
    })
  })

  const mountView = () =>
    mount(ProcedureCategoryView, {
      global: {
        stubs: {
          Teleport: true
        }
      }
    })

  it('renders correctly and fetches procedure categories on mount', async () => {
    const wrapper = mountView()

    expect(wrapper.find('.page-title').text()).toBe('Kategori Tindakan')

    await flushPromises()

    expect(procedureCategoryService.getProcedureCategories).toHaveBeenCalledTimes(1)

    const rows = wrapper.findAll('tbody tr')
    expect(rows.length).toBe(2)
    expect(rows[0].text()).toContain('KGA')
    expect(rows[1].text()).toContain('IPM')
  })

  it('shows and hides modal when Add button is clicked', async () => {
    const wrapper = mountView()

    await flushPromises()

    expect(wrapper.find('.modal-overlay').exists()).toBe(false)

    await wrapper.find('.btn-primary').trigger('click')

    expect(wrapper.find('.modal-overlay').exists()).toBe(true)
    expect(wrapper.find('.modal-header h3').text()).toBe('Tambah Kategori Tindakan')

    await wrapper.find('.btn-close').trigger('click')
    expect(wrapper.find('.modal-overlay').exists()).toBe(false)
  })

  it('calls delete API when delete button is clicked and confirmed in dialog', async () => {
    procedureCategoryService.archiveProcedureCategory.mockResolvedValue({ success: true })

    const wrapper = mountView()

    await flushPromises()

    const deleteBtns = wrapper.findAll('.btn-action.delete')
    await deleteBtns[0].trigger('click')

    expect(wrapper.find('.modal-header h3').text()).toBe('Hapus Kategori Tindakan?')
    await wrapper.find('.modal-footer .btn-danger').trigger('click')
    await flushPromises()

    expect(procedureCategoryService.archiveProcedureCategory).toHaveBeenCalledWith(1)
  })

  it('submits create modal form and refreshes list', async () => {
    procedureCategoryService.createProcedureCategory.mockResolvedValue({ data: { id: 3 } })

    const wrapper = mountView()
    await flushPromises()

    await wrapper.find('.btn-primary').trigger('click')
    await wrapper.find('#name').setValue('Bedah Minor')
    await wrapper.find('form').trigger('submit.prevent')
    await flushPromises()

    expect(procedureCategoryService.createProcedureCategory).toHaveBeenCalledWith({
      name: 'Bedah Minor',
      description: null
    })
    expect(procedureCategoryService.getProcedureCategories).toHaveBeenCalledTimes(2)
  })
})
