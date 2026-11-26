import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import ProcedureCategoryFormModal from '../ProcedureCategoryFormModal.vue'

describe('ProcedureCategoryFormModal.vue', () => {
  const mountModal = (props) =>
    mount(ProcedureCategoryFormModal, {
      props,
      global: {
        stubs: {
          Teleport: true
        }
      }
    })

  it('renders correctly with default title for new category', () => {
    const wrapper = mountModal({
      isOpen: true,
      category: null
    })

    expect(wrapper.find('.modal-header h3').text()).toBe('Tambah Kategori Tindakan')
    expect(wrapper.find('#name').element.value).toBe('')
  })

  it('renders correctly with title for editing category', async () => {
    const wrapper = mountModal({
      isOpen: true,
      category: { id: 1, name: 'KGA', description: 'Testing' }
    })

    await wrapper.vm.$nextTick()

    expect(wrapper.find('.modal-header h3').text()).toBe('Edit Kategori Tindakan')
    expect(wrapper.find('#name').element.value).toBe('KGA')
    expect(wrapper.find('#description').element.value).toBe('Testing')
  })

  it('transforms and trims inputs on submit event', async () => {
    const wrapper = mountModal({
      isOpen: true,
      category: null
    })

    await wrapper.find('#name').setValue('   KGA   ')
    await wrapper.find('#description').setValue('   ')

    await wrapper.find('form').trigger('submit.prevent')

    expect(wrapper.emitted('submit')).toBeTruthy()
    expect(wrapper.emitted('submit')[0][0]).toEqual({
      name: 'KGA',
      description: ''
    })
  })

  it('maps validation errors prop to fields', () => {
    const wrapper = mountModal({
      isOpen: true,
      category: null,
      errors: {
        name: ['Nama kategori wajib diisi.']
      }
    })

    expect(wrapper.find('.invalid-feedback').exists()).toBe(true)
    expect(wrapper.find('.invalid-feedback').text()).toBe('Nama kategori wajib diisi.')
    expect(wrapper.find('#name').classes()).toContain('is-invalid')
  })
})
