import { mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import LaboratoryItemFormModal from '../LaboratoryItemFormModal.vue'
import { laboratoryItemsService } from '@/services/master-data/laboratoryItems.service'

vi.mock('@/services/master-data/laboratoryItems.service', () => ({
  laboratoryItemsService: {
    create: vi.fn(),
    update: vi.fn(),
  },
}))

const mountModal = (props = {}) => mount(LaboratoryItemFormModal, {
  props: { isOpen: true, item: null, ...props },
  global: { stubs: { Teleport: true } },
})

describe('LaboratoryItemFormModal', () => {
  beforeEach(() => vi.clearAllMocks())

  it('submits a new item and converts blank unit to null', async () => {
    laboratoryItemsService.create.mockResolvedValue({})
    const wrapper = mountModal()
    const inputs = wrapper.findAll('input')
    await inputs[0].setValue('HBSAG')
    await wrapper.find('textarea').setValue('Negatif')
    await wrapper.find('form').trigger('submit')

    expect(laboratoryItemsService.create).toHaveBeenCalledWith({ name: 'HBSAG', reference_value: 'Negatif', unit: null })
    expect(wrapper.emitted('saved')).toHaveLength(1)
  })

  it('submits edited values including symbols', async () => {
    laboratoryItemsService.update.mockResolvedValue({})
    const wrapper = mountModal({ item: { id: 5, name: 'KOLESTEROL TOTAL', reference_value: '<200', unit: 'mg/dL' } })
    await wrapper.find('form').trigger('submit')

    expect(laboratoryItemsService.update).toHaveBeenCalledWith(5, {
      name: 'KOLESTEROL TOTAL',
      reference_value: '<200',
      unit: 'mg/dL',
    })
  })

  it('renders backend validation errors and prevents duplicate submits', async () => {
    let rejectRequest
    laboratoryItemsService.create.mockImplementation(() => new Promise((resolve, reject) => { rejectRequest = reject }))
    const wrapper = mountModal()
    await wrapper.find('form').trigger('submit')
    await wrapper.find('form').trigger('submit')
    expect(laboratoryItemsService.create).toHaveBeenCalledTimes(1)

    rejectRequest({ response: { data: { message: 'Validasi gagal', errors: { name: ['Nama wajib diisi.'] } } } })
    await Promise.resolve()
    await Promise.resolve()
    expect(wrapper.text()).toContain('Nama wajib diisi.')
  })
})
