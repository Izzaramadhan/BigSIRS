import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import ProcedureCategoryFormModal from '../ProcedureCategoryFormModal.vue'

vi.mock('@/services/procedureCategory', () => {
  return {
    procedureCategoryService: {
      createProcedureCategory: vi.fn(),
      updateProcedureCategory: vi.fn()
    }
  }
})

import { procedureCategoryService } from '@/services/procedureCategory'

describe('ProcedureCategoryFormModal.vue', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('renders correctly with default title for new category', () => {
    const wrapper = mount(ProcedureCategoryFormModal, {
      props: {
        isOpen: true,
        category: null
      },
      global: {
        stubs: {
          Teleport: true
        }
      }
    })
    
    expect(wrapper.find('.modal-title').text()).toBe('Tambah Kategori Tindakan')
    expect(wrapper.find('#name').element.value).toBe('')
  })

  it('renders correctly with title for editing category', async () => {
    const wrapper = mount(ProcedureCategoryFormModal, {
      props: {
        isOpen: true,
        category: { id: 1, name: 'KGA', description: 'Testing', is_active: true }
      },
      global: {
        stubs: {
          Teleport: true
        }
      }
    })
    
    await wrapper.vm.$nextTick()
    
    expect(wrapper.find('.modal-title').text()).toBe('Edit Kategori Tindakan')
    expect(wrapper.find('#name').element.value).toBe('KGA')
    expect(wrapper.find('#description').element.value).toBe('Testing')
  })

  it('transforms and trims inputs on submit', async () => {
    procedureCategoryService.createProcedureCategory.mockResolvedValue({ success: true })
    
    const wrapper = mount(ProcedureCategoryFormModal, {
      props: {
        isOpen: true,
        category: null
      },
      global: {
        stubs: {
          Teleport: true
        }
      }
    })
    
    await wrapper.find('#name').setValue('   KGA   ')
    await wrapper.find('#description').setValue('   ')
    
    await wrapper.find('form').trigger('submit.prevent')
    
    expect(procedureCategoryService.createProcedureCategory).toHaveBeenCalledWith({
      name: 'KGA',
      description: null,
      is_active: true
    })
  })

  it('maps validation errors to fields', async () => {
    const errorResponse = {
      response: {
        status: 422,
        data: {
          errors: {
            name: ['Nama kategori wajib diisi.']
          }
        }
      }
    }
    
    procedureCategoryService.createProcedureCategory.mockRejectedValue(errorResponse)
    
    const wrapper = mount(ProcedureCategoryFormModal, {
      props: {
        isOpen: true,
        category: null
      },
      global: {
        stubs: {
          Teleport: true
        }
      }
    })
    
    await wrapper.find('#name').setValue('')
    await wrapper.find('form').trigger('submit.prevent')
    
    // Wait for async validation error rendering
    await wrapper.vm.$nextTick()
    
    expect(wrapper.find('.invalid-feedback').exists()).toBe(true)
    expect(wrapper.find('.invalid-feedback').text()).toBe('Nama kategori wajib diisi.')
    expect(wrapper.find('#name').classes()).toContain('is-invalid')
  })
})
