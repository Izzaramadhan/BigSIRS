import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import ProcedureCategoryView from '../ProcedureCategoryView.vue'
import { procedureCategoryService } from '@/services/procedureCategory'

vi.mock('@/services/procedureCategory', () => {
  return {
    procedureCategoryService: {
      getProcedureCategories: vi.fn(),
      archiveProcedureCategory: vi.fn(),
      updateProcedureCategoryStatus: vi.fn()
    }
  }
})

describe('ProcedureCategoryView.vue', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    window.confirm = vi.fn(() => true)
    window.alert = vi.fn()
    
    procedureCategoryService.getProcedureCategories.mockResolvedValue({
      data: {
        data: [
          { id: 1, name: 'KGA', description: 'KGA Desc', is_active: true },
          { id: 2, name: 'IPM', description: 'IPM Desc', is_active: false }
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

  it('renders correctly and fetches procedure categories on mount', async () => {
    const wrapper = mount(ProcedureCategoryView, {
      global: {
        stubs: {
          Teleport: true
        }
      }
    })
    
    expect(wrapper.find('.page-title').text()).toBe('Kategori Tindakan')
    
    await flushPromises()
    
    expect(procedureCategoryService.getProcedureCategories).toHaveBeenCalledTimes(1)
    
    const rows = wrapper.findAll('tbody tr')
    expect(rows.length).toBe(2)
    expect(rows[0].text()).toContain('KGA')
    expect(rows[1].text()).toContain('IPM')
  })

  it('shows and hides modal when Add button is clicked', async () => {
    const wrapper = mount(ProcedureCategoryView, {
      global: {
        stubs: {
          Teleport: true
        }
      }
    })
    
    await flushPromises()
    
    expect(wrapper.find('.modal-container').exists()).toBe(false)
    
    await wrapper.find('.btn-primary').trigger('click')
    
    expect(wrapper.find('.modal-container').exists()).toBe(true)
    expect(wrapper.find('.modal-title').text()).toBe('Tambah Kategori Tindakan')
    
    await wrapper.find('.btn-close').trigger('click')
    expect(wrapper.find('.modal-container').exists()).toBe(false)
  })

  it('calls delete API when delete button is clicked and confirmed', async () => {
    procedureCategoryService.archiveProcedureCategory.mockResolvedValue({ success: true })
    
    const wrapper = mount(ProcedureCategoryView, {
      global: {
        stubs: {
          Teleport: true
        }
      }
    })
    
    await flushPromises()
    
    const deleteBtns = wrapper.findAll('.btn-action.delete')
    await deleteBtns[0].trigger('click')
    
    expect(window.confirm).toHaveBeenCalledWith('Apakah Anda yakin ingin menghapus kategori tindakan "KGA"?')
    expect(procedureCategoryService.archiveProcedureCategory).toHaveBeenCalledWith(1)
  })

  it('calls toggle API when status button is clicked and confirmed', async () => {
    procedureCategoryService.updateProcedureCategoryStatus.mockResolvedValue({ success: true })
    
    const wrapper = mount(ProcedureCategoryView, {
      global: {
        stubs: {
          Teleport: true
        }
      }
    })
    
    await flushPromises()
    
    const statusBtns = wrapper.findAll('.status-toggle')
    await statusBtns[0].trigger('click')
    
    expect(window.confirm).toHaveBeenCalledWith('Apakah Anda yakin ingin menonaktifkan kategori tindakan "KGA"?')
    expect(procedureCategoryService.updateProcedureCategoryStatus).toHaveBeenCalledWith(1, false)
  })
})
