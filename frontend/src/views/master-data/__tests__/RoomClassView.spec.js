import { flushPromises, mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import RoomClassView from '../bed/RoomClassView.vue'
import { roomClassesService } from '@/services/master-data/roomClasses.service'

vi.mock('@/services/master-data/roomClasses.service', () => ({
  roomClassesService: {
    getRoomClasses: vi.fn(),
    getRoomClassOptions: vi.fn(),
    createRoomClass: vi.fn(),
    updateRoomClass: vi.fn(),
    deleteRoomClass: vi.fn(),
    getRoomClass: vi.fn(),
  },
}))

const mockPaginatedResponse = {
  data: [
    {
      id: 1,
      code: 'KL1',
      name: 'Kelas 1',
      price: 150000,
      formatted_price: 'Rp150.000',
      description: 'Kelas perawatan tingkat satu',
      aplicare_code: '01',
      sirs_code: '3',
      created_at: '2026-10-22T08:00:00Z',
      updated_at: '2026-10-22T08:00:00Z',
    },
    {
      id: 2,
      code: 'KL2',
      name: 'Kelas 2',
      price: 0,
      formatted_price: 'Rp0',
      description: null,
      aplicare_code: null,
      sirs_code: null,
      created_at: '2026-10-22T08:00:00Z',
      updated_at: '2026-10-22T08:00:00Z',
    },
  ],
  meta: {
    current_page: 1,
    last_page: 1,
    per_page: 10,
    total: 2,
    from: 1,
    to: 2,
  },
  links: {},
}

describe('RoomClassView.vue and Room Class Management', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    roomClassesService.getRoomClasses.mockResolvedValue(mockPaginatedResponse)
  })

  const mountView = async () => {
    const wrapper = mount(RoomClassView, {
      global: {
        stubs: {
          teleport: true,
        },
      },
    })
    await flushPromises()
    return wrapper
  }

  it('renders page header with correct title and description', async () => {
    const wrapper = await mountView()
    expect(wrapper.text()).toContain('Data Kelas')
    expect(wrapper.text()).toContain('Kelola kelas perawatan, harga, dan kode referensi untuk mendukung pengaturan kamar serta tempat tidur rumah sakit.')
  })

  it('renders table headers with required 7 columns in exact order', async () => {
    const wrapper = await mountView()
    const ths = wrapper.findAll('thead th')
    expect(ths).toHaveLength(7)
    expect(ths[0].text()).toBe('No')
    expect(ths[1].text()).toBe('Nama Kelas')
    expect(ths[2].text()).toBe('Harga Kelas')
    expect(ths[3].text()).toBe('Deskripsi')
    expect(ths[4].text()).toBe('Kode Kelas BPJS')
    expect(ths[5].text()).toBe('Kode Kelas SIRS')
    expect(ths[6].text()).toBe('Aksi')
  })

  it('renders format Rupiah correctly for non-zero and zero values', async () => {
    const wrapper = await mountView()
    const rows = wrapper.findAll('tbody tr')
    expect(rows).toHaveLength(2)

    // First row: Rp150.000
    expect(rows[0].find('.col-price').text()).toBe('Rp150.000')
    // Second row: Rp0
    expect(rows[1].find('.col-price').text()).toBe('Rp0')
  })

  it('renders Nama Kelas, BPJS code, and SIRS code with fallback to — when empty', async () => {
    const wrapper = await mountView()
    const rows = wrapper.findAll('tbody tr')

    expect(rows[0].find('.col-name').text()).toBe('Kelas 1')
    expect(rows[0].find('.col-description').text()).toBe('Kelas perawatan tingkat satu')
    expect(rows[0].findAll('.col-code')[0].text()).toBe('01')
    expect(rows[0].findAll('.col-code')[1].text()).toBe('3')

    expect(rows[1].find('.col-name').text()).toBe('Kelas 2')
    expect(rows[1].find('.col-description').text()).toBe('—')
    expect(rows[1].findAll('.col-code')[0].text()).toBe('—')
    expect(rows[1].findAll('.col-code')[1].text()).toBe('—')
  })

  it('opens add modal with correct fields and helper text', async () => {
    const wrapper = await mountView()
    const addBtn = wrapper.find('button.btn-primary')
    await addBtn.trigger('click')
    await flushPromises()

    const modal = wrapper.findComponent({ name: 'RoomClassFormModal' })
    expect(modal.exists()).toBe(true)
    expect(modal.props('isOpen')).toBe(true)
    expect(modal.text()).toContain('Kode sementara disimpan secara lokal dan belum terintegrasi dengan layanan Aplicare/BPJS.')
    expect(modal.text()).toContain('Kode sementara disimpan secara lokal dan belum terintegrasi dengan layanan SIRS.')
  })

  it('submits form and calls createRoomClass, then displays success toast', async () => {
    roomClassesService.createRoomClass.mockResolvedValue({
      data: {
        id: 3,
        code: 'KL3',
        name: 'Kelas 3',
        price: 300000,
        description: null,
      },
    })

    const wrapper = await mountView()
    const addBtn = wrapper.find('button.btn-primary')
    await addBtn.trigger('click')
    await flushPromises()

    const modal = wrapper.findComponent({ name: 'RoomClassFormModal' })
    modal.vm.$emit('submit', {
      name: 'Kelas 3',
      code: 'KL3',
      price: 300000,
      description: null,
      aplicare_code: '03',
      sirs_code: '5',
    })
    await flushPromises()

    expect(roomClassesService.createRoomClass).toHaveBeenCalledWith({
      name: 'Kelas 3',
      code: 'KL3',
      price: 300000,
      description: null,
      aplicare_code: '03',
      sirs_code: '5',
    })
    expect(wrapper.text()).toContain('Data kelas berhasil ditambahkan.')
  })

  it('opens edit modal and pre-fills room class data', async () => {
    const wrapper = await mountView()
    const editBtn = wrapper.find('.actions-cell button[title="Edit"]')
    await editBtn.trigger('click')
    await flushPromises()

    const modal = wrapper.findComponent({ name: 'RoomClassFormModal' })
    expect(modal.props('isOpen')).toBe(true)
    expect(modal.props('roomClass')).toBeTruthy()
    expect(modal.props('roomClass').name).toBe('Kelas 1')
  })

  it('opens delete confirmation dialog and handles successful deletion', async () => {
    roomClassesService.deleteRoomClass.mockResolvedValue({ success: true })

    const wrapper = await mountView()
    const deleteBtn = wrapper.find('.actions-cell button[title="Hapus"]')
    await deleteBtn.trigger('click')
    await flushPromises()

    const dialog = wrapper.findComponent({ name: 'MasterDataDeleteDialog' })
    expect(dialog.props('isOpen')).toBe(true)
    expect(dialog.props('title')).toBe('Hapus Kelas')
    expect(dialog.props('confirmMessage')).toContain('Apakah Anda yakin ingin menghapus kelas “Kelas 1”?')

    dialog.vm.$emit('confirm')
    await flushPromises()

    expect(roomClassesService.deleteRoomClass).toHaveBeenCalledWith(1)
    expect(wrapper.text()).toContain('Data kelas berhasil dihapus.')
  })

  it('shows error toast when deletion is rejected because class is used by rooms', async () => {
    roomClassesService.deleteRoomClass.mockRejectedValue({
      response: {
        status: 422,
        data: {
          message: 'Data kelas tidak dapat dihapus karena masih digunakan oleh data kamar.',
        },
      },
    })

    const wrapper = await mountView()
    const deleteBtn = wrapper.find('.actions-cell button[title="Hapus"]')
    await deleteBtn.trigger('click')
    await flushPromises()

    const dialog = wrapper.findComponent({ name: 'MasterDataDeleteDialog' })
    dialog.vm.$emit('confirm')
    await flushPromises()

    expect(wrapper.text()).toContain('Data kelas tidak dapat dihapus karena masih digunakan oleh data kamar.')
  })
})
