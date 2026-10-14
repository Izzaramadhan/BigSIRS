import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import PolyclinicFormModal from '../PolyclinicFormModal.vue'
import { polyclinicService } from '@/services/polyclinic'

vi.mock('@/services/polyclinic', () => {
  return {
    polyclinicService: {
      getPolyclinic: vi.fn(),
    }
  }
})

vi.mock('@/services/lookup.service', () => {
  return {
    default: {
      getWarehouses: vi.fn().mockResolvedValue([{ id: 1, code: 'W1', name: 'Gudang 1' }])
    }
  }
})

describe('PolyclinicFormModal', () => {
  const mockServiceTypes = [
    { value: 'rawat-jalan', label: 'Rawat Jalan' },
    { value: 'farmasi', label: 'Farmasi' },
  ]

  const mountModal = (propsData = {}) => {
    return mount(PolyclinicFormModal, {
      props: {
        isOpen: true,
        serviceTypes: mockServiceTypes,
        ...propsData
      },
      global: {
        stubs: {
          teleport: true
        }
      }
    })
  }

  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('renders correctly with default title for new polyclinic', () => {
    const wrapper = mountModal()
    expect(wrapper.find('.modal-content').exists()).toBe(true)
    expect(wrapper.find('.modal-header h3').text()).toBe('Tambah Poliklinik')
  })

  it('renders correctly with title for editing polyclinic', () => {
    const wrapper = mountModal({ polyclinic: { id: 1, code: 'TEST' } })
    expect(wrapper.find('.modal-header h3').text()).toBe('Edit Poliklinik')
  })

  it('does NOT have Poliklinik Induk field', () => {
    const wrapper = mountModal()
    expect(wrapper.text()).not.toContain('Poliklinik Induk')
  })

  it('does NOT have Kode SatuSehat field', () => {
    const wrapper = mountModal()
    expect(wrapper.text()).not.toContain('Kode SatuSehat')
    expect(wrapper.find('#satusehat_code').exists()).toBe(false)
  })

  it('has all required new fields in order', () => {
    const wrapper = mountModal()
    
    expect(wrapper.find('#code').exists()).toBe(true)
    expect(wrapper.find('#name').exists()).toBe(true)
    expect(wrapper.find('#service_type').exists()).toBe(true)
    expect(wrapper.find('#description').exists()).toBe(true)
    expect(wrapper.find('#is_visible').exists()).toBe(true)
    expect(wrapper.find('#is_online_visible').exists()).toBe(true)
    expect(wrapper.find('#quota').exists()).toBe(true)
    expect(wrapper.find('#jkn_quota').exists()).toBe(true)
    expect(wrapper.find('#warehouse_id').exists()).toBe(true)
    expect(wrapper.find('#bpjs_code').exists()).toBe(true)
  })

  it('renders bpjs_code input as optional without required attribute or label indicator', () => {
    const wrapper = mountModal()
    const input = wrapper.find('input#bpjs_code')
    const label = wrapper.find('label[for="bpjs_code"]')

    expect(input.exists()).toBe(true)
    expect(input.attributes('required')).toBeUndefined()
    expect(input.attributes('placeholder')).toBe('Masukkan kode BPJS')
    expect(input.attributes('maxlength')).toBe('50')
    expect(label.classes()).not.toContain('required')
  })

  it('displays stored bpjs_code value on Edit modal', async () => {
    polyclinicService.getPolyclinic.mockResolvedValue({
      data: {
        data: {
          id: 10,
          code: 'POLI-10',
          name: 'Poli Jantung',
          bpjs_code: 'BPJS-10'
        }
      }
    })

    const wrapper = mountModal({
      polyclinic: { id: 10, code: 'POLI-10', bpjs_code: 'BPJS-10' }
    })
    await flushPromises()

    const input = wrapper.find('input#bpjs_code')
    expect(input.element.value).toBe('BPJS-10')
  })

  it('displays empty string if stored bpjs_code is null on Edit modal', async () => {
    polyclinicService.getPolyclinic.mockResolvedValue({
      data: {
        data: {
          id: 11,
          code: 'POLI-11',
          name: 'Poli Kulit',
          bpjs_code: null
        }
      }
    })

    const wrapper = mountModal({
      polyclinic: { id: 11, code: 'POLI-11', bpjs_code: null }
    })
    await flushPromises()

    const input = wrapper.find('input#bpjs_code')
    expect(input.element.value).toBe('')
  })

  it('service_type dropdown lists all service types', () => {
    const wrapper = mountModal()
    const select = wrapper.find('#service_type')
    const options = select.findAll('option')
    const optionTexts = options.map(o => o.text())
    
    expect(optionTexts).toContain('-- Pilih Jenis Layanan --')
    expect(optionTexts).toContain('Rawat Jalan')
    expect(optionTexts).toContain('Farmasi')
  })

  it('Gudang Default dropdown is populated', async () => {
    const wrapper = mountModal()
    await flushPromises() // Wait for watch to resolve lookupService
    const select = wrapper.find('#warehouse_id')
    expect(select.exists()).toBe(true)
    const options = select.findAll('option')
    expect(options.length).toBeGreaterThan(1) // Placeholder + 1 mocked item
  })

  it('transforms code to uppercase and trims inputs on submit', async () => {
    const wrapper = mountModal()
    
    await wrapper.find('input#code').setValue(' poli-test ')
    await wrapper.find('input#name').setValue('Test Name')
    await wrapper.find('#service_type').setValue('rawat-jalan')
    await wrapper.find('textarea#description').setValue(' desc ')
    await wrapper.find('input#bpjs_code').setValue('  BPJS-01  ')
    
    await wrapper.find('form').trigger('submit.prevent')
    
    const emitted = wrapper.emitted('submit')
    expect(emitted).toBeTruthy()
    expect(emitted[0][0]).toEqual(expect.objectContaining({
      code: 'POLI-TEST',
      name: 'Test Name',
      service_type: 'rawat-jalan',
      description: 'desc',
      warehouse_id: null,
      bpjs_code: 'BPJS-01',
    }))
  })

  it('sends bpjs_code as null when input is empty or contains only whitespace', async () => {
    const wrapper = mountModal()
    
    await wrapper.find('input#code').setValue('TEST01')
    await wrapper.find('input#name').setValue('Test Name')
    await wrapper.find('#service_type').setValue('rawat-jalan')
    await wrapper.find('input#bpjs_code').setValue('    ')
    
    await wrapper.find('form').trigger('submit.prevent')
    
    const emitted = wrapper.emitted('submit')
    expect(emitted).toBeTruthy()
    expect(emitted[0][0].bpjs_code).toBeNull()
  })

  it('payload does NOT include parent_id', async () => {
    const wrapper = mountModal()
    
    await wrapper.find('input#code').setValue('TEST01')
    await wrapper.find('input#name').setValue('Test')
    await wrapper.find('#service_type').setValue('rawat-jalan')
    await wrapper.find('form').trigger('submit.prevent')
    
    const emitted = wrapper.emitted('submit')
    expect(emitted[0][0]).not.toHaveProperty('parent_id')
  })

  it('maps validation errors to fields including bpjs_code', () => {
    const wrapper = mountModal({
      errors: {
        code: ['Code is taken'],
        bpjs_code: ['Kode BPJS maksimal 50 karakter.'],
      }
    })
    
    expect(wrapper.find('#code').classes()).toContain('is-invalid')
    const bpjsInput = wrapper.find('#bpjs_code')
    expect(bpjsInput.classes()).toContain('is-invalid')
    const bpjsError = wrapper.findAll('.invalid-feedback').find(el => el.text() === 'Kode BPJS maksimal 50 karakter.')
    expect(bpjsError).toBeDefined()
  })
})
