import { mount, flushPromises } from '@vue/test-utils'
import { describe, it, expect, vi } from 'vitest'
import AsyncEmployeeSelect from './AsyncEmployeeSelect.vue'
import LookupService from '@/services/lookup.service'

vi.mock('@/services/lookup.service')

describe('AsyncEmployeeSelect.vue', () => {
  it('menampilkan opsi dengan format NIP - Nama - Profesi', async () => {
    LookupService.getEmployees.mockResolvedValue({
      data: [
        { id: 10, code: '123', name: 'Person 1694', profession: 'Dokter', is_active: true }
      ]
    })

    const wrapper = mount(AsyncEmployeeSelect, {
      props: { multiple: false }
    })

    // Trigger toggle to load options
    await wrapper.find('.select-box').trigger('click')
    await flushPromises()

    const option = wrapper.find('.option-name')
    expect(option.exists()).toBe(true)
    expect(option.text()).toContain('123')
    expect(option.text()).toContain('Person 1694')
    expect(option.text()).toContain('Dokter')
    
    // Select the option
    await option.trigger('click')
    
    // Verify emitted value is employee.id
    const emitted = wrapper.emitted('update:modelValue')
    expect(emitted).toBeTruthy()
    expect(emitted[0][0]).toBe(10)
    
    // Verify selected option keeps the correct formatting
    const selectedText = wrapper.find('.selected-text')
    expect(selectedText.text()).toContain('Person 1694')
    expect(selectedText.text()).toContain('Dokter')
  })

  it('memastikan label terpilih (selected) tidak berubah jika pencarian async dilakukan (mode edit)', async () => {
    const wrapper = mount(AsyncEmployeeSelect, {
      props: { 
        multiple: true,
        modelValue: [10]
      }
    })

    wrapper.vm.setInitialOptions([
      { id: 10, code: '123', name: 'Person 1694', profession: 'Dokter' }
    ])
    await wrapper.vm.$nextTick()

    // Test initially selected tag
    const tags = wrapper.findAll('.tag')
    expect(tags.length).toBe(1)
    expect(tags[0].text()).toContain('Person 1694')
    
    // Trigger async load
    LookupService.getEmployees.mockResolvedValue({
      data: [
        { id: 20, code: '456', name: 'Person 9999', profession: 'Perawat', is_active: true }
      ]
    })
    
    await wrapper.find('.search-input').setValue('Person')
    await new Promise(r => setTimeout(r, 400)) // Wait for debounce
    await flushPromises()

    // Ensure the original selected option is not overwritten or lost
    const tagsAfterSearch = wrapper.findAll('.tag')
    expect(tagsAfterSearch.length).toBe(1)
    expect(tagsAfterSearch[0].text()).toContain('Person 1694')
  })
})
