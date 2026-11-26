import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import PolyclinicFormModal from '../PolyclinicFormModal.vue'
import { polyclinicService } from '@/services/polyclinic'

vi.mock('@/services/polyclinic', () => ({
  polyclinicService: {
    getPolyclinic: vi.fn(),
  },
}))

vi.mock('@/services/lookup.service', () => ({
  default: {
    getWarehouses: vi.fn().mockResolvedValue([{ id: 1, code: 'W1', name: 'Gudang 1' }]),
  },
}))

describe('PolyclinicFormModal', () => {
  const mockServiceTypes = [
    { value: 'rawat-jalan', label: 'Rawat Jalan' },
    { value: 'farmasi', label: 'Farmasi' },
  ]

  const mountModal = (propsData = {}) =>
    mount(PolyclinicFormModal, {
      props: {
        open: true,
        serviceTypes: mockServiceTypes,
        ...propsData,
      },
      global: {
        stubs: {
          Teleport: true,
        },
      },
    })

  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('renders correctly with default title for new polyclinic', () => {
    const wrapper = mountModal()
    expect(wrapper.find('[role="dialog"]').exists()).toBe(true)
    expect(wrapper.find('h2').text()).toBe('Tambah Poliklinik')
  })

  it('renders correctly with title for editing polyclinic', () => {
    polyclinicService.getPolyclinic.mockResolvedValue({
      data: { data: { id: 1, code: 'TEST', name: 'Test', service_type: 'rawat-jalan' } },
    })
    const wrapper = mountModal({ polyclinic: { id: 1, code: 'TEST' } })
    expect(wrapper.find('h2').text()).toBe('Edit Poliklinik')
  })

  it('does NOT have Poliklinik Induk or Kode SatuSehat field', () => {
    const wrapper = mountModal()
    expect(wrapper.text()).not.toContain('Poliklinik Induk')
    expect(wrapper.text()).not.toContain('Kode SatuSehat')
    expect(wrapper.find('#satusehat_code').exists()).toBe(false)
  })

  it('has all required fields', () => {
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

  it('renders bpjs_code input as optional without required attribute', () => {
    const wrapper = mountModal()
    const input = wrapper.find('input#bpjs_code')
    const label = wrapper.find('label[for="bpjs_code"]')

    expect(input.exists()).toBe(true)
    expect(input.attributes('required')).toBeUndefined()
    expect(input.attributes('placeholder')).toBe('Masukkan kode BPJS')
    expect(input.attributes('maxlength')).toBe('50')
    expect(label.text()).not.toContain('*')
  })

  it('displays stored bpjs_code value on Edit modal', async () => {
    polyclinicService.getPolyclinic.mockResolvedValue({
      data: {
        data: {
          id: 10,
          code: 'POLI-10',
          name: 'Poli Jantung',
          service_type: 'rawat-jalan',
          bpjs_code: 'BPJS-10',
        },
      },
    })

    const wrapper = mountModal({
      polyclinic: { id: 10, code: 'POLI-10', bpjs_code: 'BPJS-10' },
    })
    await flushPromises()

    expect(wrapper.find('input#bpjs_code').element.value).toBe('BPJS-10')
  })

  it('displays empty string if stored bpjs_code is null on Edit modal', async () => {
    polyclinicService.getPolyclinic.mockResolvedValue({
      data: {
        data: {
          id: 11,
          code: 'POLI-11',
          name: 'Poli Kulit',
          service_type: 'rawat-jalan',
          bpjs_code: null,
        },
      },
    })

    const wrapper = mountModal({
      polyclinic: { id: 11, code: 'POLI-11', bpjs_code: null },
    })
    await flushPromises()

    expect(wrapper.find('input#bpjs_code').element.value).toBe('')
  })

  it('lists all service types and populates Gudang Default dropdown', async () => {
    const wrapper = mountModal()
    await flushPromises()

    const serviceOptions = wrapper
      .find('#service_type')
      .findAll('option')
      .map((option) => option.text())
    expect(serviceOptions).toContain('-- Pilih Jenis Layanan --')
    expect(serviceOptions).toContain('Rawat Jalan')
    expect(serviceOptions).toContain('Farmasi')

    const warehouseOptions = wrapper
      .find('#warehouse_id')
      .findAll('option')
      .map((option) => option.text())
    expect(warehouseOptions).toContain('W1 — Gudang 1')
  })

  it('blocks submit with client validation messages when required fields are empty', async () => {
    const wrapper = mountModal()

    await wrapper.find('form').trigger('submit.prevent')

    expect(wrapper.emitted('submit')).toBeFalsy()
    expect(wrapper.text()).toContain('Kode poliklinik wajib diisi.')
    expect(wrapper.text()).toContain('Nama poliklinik wajib diisi.')
    expect(wrapper.text()).toContain('Jenis layanan wajib dipilih.')
  })

  it('transforms code to uppercase, trims inputs, and excludes parent_id on submit', async () => {
    const wrapper = mountModal()
    await flushPromises()

    await wrapper.find('input#code').setValue(' poli-test ')
    await wrapper.find('input#name').setValue('Test Name')
    await wrapper.find('#service_type').setValue('rawat-jalan')
    await wrapper.find('textarea#description').setValue(' desc ')
    await wrapper.find('input#bpjs_code').setValue('  BPJS-01  ')

    await wrapper.find('form').trigger('submit.prevent')

    const emitted = wrapper.emitted('submit')
    expect(emitted).toBeTruthy()
    expect(emitted[0][0]).toEqual(
      expect.objectContaining({
        code: 'POLI-TEST',
        name: 'Test Name',
        service_type: 'rawat-jalan',
        description: 'desc',
        warehouse_id: null,
        bpjs_code: 'BPJS-01',
      }),
    )
    expect(emitted[0][0]).not.toHaveProperty('parent_id')
    expect(emitted[0][0]).not.toHaveProperty('satusehat_code')
  })

  it('sends bpjs_code as null when input contains only whitespace', async () => {
    const wrapper = mountModal()
    await flushPromises()

    await wrapper.find('input#code').setValue('TEST01')
    await wrapper.find('input#name').setValue('Test Name')
    await wrapper.find('#service_type').setValue('rawat-jalan')
    await wrapper.find('input#bpjs_code').setValue('    ')

    await wrapper.find('form').trigger('submit.prevent')

    const emitted = wrapper.emitted('submit')
    expect(emitted).toBeTruthy()
    expect(emitted[0][0].bpjs_code).toBeNull()
  })

  it('maps serverErrors and generalError to BaseField and BaseAlert', () => {
    const wrapper = mountModal({
      serverErrors: {
        code: 'Code is taken',
        bpjs_code: 'Kode BPJS maksimal 50 karakter.',
      },
      generalError: 'Terdapat poliklinik turunan.',
    })

    expect(wrapper.find('#code').attributes('aria-invalid')).toBe('true')
    expect(wrapper.find('#bpjs_code').attributes('aria-invalid')).toBe('true')
    expect(wrapper.text()).toContain('Code is taken')
    expect(wrapper.text()).toContain('Kode BPJS maksimal 50 karakter.')
    expect(wrapper.text()).toContain('Terdapat poliklinik turunan.')
  })

  it('prevents double-submit while saving is true', async () => {
    const wrapper = mountModal({ saving: true })

    await wrapper.find('input#code').setValue('POLI-01')
    await wrapper.find('input#name').setValue('Poli Umum')
    await wrapper.find('#service_type').setValue('rawat-jalan')
    await wrapper.find('form').trigger('submit.prevent')

    expect(wrapper.emitted('submit')).toBeFalsy()
  })
})
