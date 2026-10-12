import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import InsuranceTypeFormModal from '../InsuranceTypeFormModal.vue'

describe('InsuranceTypeFormModal.vue', () => {
  it('renders correctly for add new mode', () => {
    const wrapper = mount(InsuranceTypeFormModal, {
      props: {
        isOpen: true,
        insuranceType: null,
        isSubmitting: false,
        errors: {}
      },
      global: {
        stubs: {
          Teleport: true
        }
      }
    })

    expect(wrapper.text()).toContain('Tambah Jenis Asuransi')
    expect(wrapper.text()).toContain('Simpan')
    expect(wrapper.text()).not.toContain('Simpan Perubahan')

    // Confirm Status field is not present
    expect(wrapper.find('input[type="checkbox"]').exists()).toBe(false)
    expect(wrapper.text()).not.toContain('Aktif')
    expect(wrapper.text()).not.toContain('Nonaktif')
  })

  it('renders correctly for edit mode with initial data', () => {
    const wrapper = mount(InsuranceTypeFormModal, {
      props: {
        isOpen: true,
        insuranceType: {
          id: 5,
          name: 'BPJS Pensiunan',
          is_government: true,
          inacbg_id: '12'
        },
        isSubmitting: false,
        errors: {}
      },
      global: {
        stubs: {
          Teleport: true
        }
      }
    })

    expect(wrapper.text()).toContain('Edit Jenis Asuransi')
    expect(wrapper.text()).toContain('Simpan Perubahan')

    const nameInput = wrapper.find('input#name')
    expect(nameInput.element.value).toBe('BPJS Pensiunan')

    const inacbgInput = wrapper.find('input#inacbg_id')
    expect(inacbgInput.element.value).toBe('12')

    // Confirm Status field is not present
    expect(wrapper.find('input[type="checkbox"]').exists()).toBe(false)
  })

  it('emits submit event with proper payload', async () => {
    const wrapper = mount(InsuranceTypeFormModal, {
      props: {
        isOpen: true,
        insuranceType: null,
        isSubmitting: false,
        errors: {}
      },
      global: {
        stubs: {
          Teleport: true
        }
      }
    })

    await wrapper.find('input#name').setValue('Asuransi ABC')
    const radios = wrapper.findAll('input[type="radio"]')
    // Set is_government = true
    await radios[1].setValue(true)
    await wrapper.find('input#inacbg_id').setValue('99')

    await wrapper.find('form').trigger('submit')

    expect(wrapper.emitted()).toHaveProperty('submit')
    expect(wrapper.emitted('submit')[0][0]).toEqual({
      name: 'Asuransi ABC',
      is_government: true,
      inacbg_id: '99'
    })
  })

  it('emits close event when Batal button is clicked', async () => {
    const wrapper = mount(InsuranceTypeFormModal, {
      props: {
        isOpen: true,
        insuranceType: null,
        isSubmitting: false,
        errors: {}
      },
      global: {
        stubs: {
          Teleport: true
        }
      }
    })

    const cancelBtn = wrapper.find('.btn-cancel')
    await cancelBtn.trigger('click')

    expect(wrapper.emitted()).toHaveProperty('close')
  })
})
