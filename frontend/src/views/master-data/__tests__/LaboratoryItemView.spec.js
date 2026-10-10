import { flushPromises, mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import LaboratoryItemView from '../LaboratoryItemView.vue'
import { laboratoryItemsService } from '@/services/master-data/laboratoryItems.service'

vi.mock('@/services/master-data/laboratoryItems.service', () => ({
  laboratoryItemsService: {
    list: vi.fn(),
    detail: vi.fn(),
    create: vi.fn(),
    update: vi.fn(),
    remove: vi.fn(),
  },
}))

const paginatedResponse = {
  data: [
    { id: 1, name: 'KOLESTEROL TOTAL', reference_value: '<200', unit: 'mg/dL' },
    { id: 2, name: 'HBSAG', reference_value: 'Negatif', unit: null },
  ],
  meta: { current_page: 1, last_page: 2, per_page: 10, total: 12 },
  links: {},
}

describe('LaboratoryItemView', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    laboratoryItemsService.list.mockResolvedValue(paginatedResponse)
  })

  it('renders table data, reference symbols, and nullable units safely', async () => {
    const wrapper = mount(LaboratoryItemView, { global: { stubs: { Teleport: true } } })
    await flushPromises()

    expect(wrapper.text()).toContain('KOLESTEROL TOTAL')
    expect(wrapper.text()).toContain('<200')
    expect(wrapper.text()).toContain('HBSAG')
    expect(wrapper.findAll('tbody tr')[1].text()).toContain('-')
  })

  it('shows API error separately from empty data', async () => {
    laboratoryItemsService.list.mockRejectedValue({ response: { data: { message: 'Server error' } } })
    const wrapper = mount(LaboratoryItemView, { global: { stubs: { Teleport: true } } })
    await flushPromises()

    expect(wrapper.text()).toContain('Gagal memuat data item lab.')
    expect(wrapper.text()).not.toContain('Data item lab belum tersedia.')
  })

  it('shows database and search empty states', async () => {
    laboratoryItemsService.list.mockResolvedValue({ data: [], meta: { current_page: 1, last_page: 1, per_page: 10, total: 0 } })
    const wrapper = mount(LaboratoryItemView, { global: { stubs: { Teleport: true } } })
    await flushPromises()
    expect(wrapper.text()).toContain('Data item lab belum tersedia.')

    await wrapper.find('input[type="search"]').setValue('tidak ada')
    expect(wrapper.text()).toContain('Item lab tidak ditemukan.')
  })

  it('sends search and pagination parameters', async () => {
    vi.useFakeTimers()
    const wrapper = mount(LaboratoryItemView, { global: { stubs: { Teleport: true } } })
    await flushPromises()

    await wrapper.find('input[type="search"]').setValue('HDL')
    await vi.advanceTimersByTimeAsync(300)
    expect(laboratoryItemsService.list).toHaveBeenLastCalledWith(expect.objectContaining({ search: 'HDL', page: 1 }))

    await wrapper.findAll('.pagination button')[1].trigger('click')
    expect(laboratoryItemsService.list).toHaveBeenLastCalledWith(expect.objectContaining({ page: 2 }))
    vi.useRealTimers()
  })

  it('opens add and edit modal with the expected values', async () => {
    const wrapper = mount(LaboratoryItemView, { global: { stubs: { Teleport: true } } })
    await flushPromises()

    await wrapper.find('.hero .primary').trigger('click')
    expect(wrapper.text()).toContain('Tambah Item Lab')
    await wrapper.find('.icon-button').trigger('click')

    await wrapper.find('.action.edit').trigger('click')
    expect(wrapper.text()).toContain('Edit Item Lab')
    expect(wrapper.find('#laboratory-item-form input').element.value).toBe('KOLESTEROL TOTAL')
    expect(wrapper.find('#laboratory-item-form textarea').element.value).toBe('<200')
  })

  it('archives an item and refreshes the list', async () => {
    laboratoryItemsService.remove.mockResolvedValue({ message: 'ok' })
    const wrapper = mount(LaboratoryItemView, { global: { stubs: { Teleport: true } } })
    await flushPromises()

    await wrapper.find('.action.delete').trigger('click')
    expect(wrapper.text()).toContain('Hapus Item Lab?')
    laboratoryItemsService.list.mockClear()
    await wrapper.find('.delete-dialog .danger').trigger('click')
    await flushPromises()

    expect(laboratoryItemsService.remove).toHaveBeenCalledWith(1)
    expect(laboratoryItemsService.list).toHaveBeenCalledTimes(1)
  })
})
