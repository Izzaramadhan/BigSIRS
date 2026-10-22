import { flushPromises, mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import BedView from '../bed/BedView.vue'
import { bedsService } from '@/services/master-data/beds.service'
import { roomsService } from '@/services/master-data/rooms.service'

vi.mock('@/services/master-data/beds.service', () => ({
  bedsService: {
    getBeds: vi.fn(),
    getBed: vi.fn(),
    createBed: vi.fn(),
    updateBed: vi.fn(),
    deleteBed: vi.fn(),
  },
}))

vi.mock('@/services/master-data/rooms.service', () => ({
  roomsService: {
    getRooms: vi.fn(),
    getRoom: vi.fn(),
  },
}))

const mockRooms = [
  {
    id: 1,
    name: 'Kamar 1',
    code: 'KMR-001',
    ward_id: 3,
    ward: {
      id: 3,
      name: 'Bangsal Maternal Perinatal',
      floor: {
        id: 2,
        name: 'Lantai 2',
        building: { id: 1, name: 'RUANG BUGENVILLE' },
      },
    },
    room_class_id: 3,
    room_class: { id: 3, code: 'KLS-3', name: 'Kelas 3' },
    class: { id: 3, code: 'KLS-3', name: 'Kelas 3' },
    display_name: 'Kamar 1 - Bangsal Maternal Perinatal - Kelas 3',
  },
  {
    id: 2,
    name: 'Kamar 2',
    code: 'KMR-002',
    ward_id: 1,
    ward: {
      id: 1,
      name: 'Bangsal Melati',
      floor: { id: 1, name: 'Lantai 1' },
    },
    room_class_id: 1,
    room_class: { id: 1, code: 'VIP', name: 'VIP' },
    class: { id: 1, code: 'VIP', name: 'VIP' },
    display_name: 'Kamar 2 - Bangsal Melati - VIP',
  },
]

const mockPaginatedBedsResponse = {
  data: [
    {
      id: 1,
      room_id: 1,
      name: 'Bed 1',
      description: 'Bed dekat jendela',
      room: {
        id: 1,
        code: 'KMR-001',
        name: 'Kamar 1',
        ward: {
          id: 3,
          name: 'Bangsal Maternal Perinatal',
          floor: {
            id: 2,
            name: 'Lantai 2',
            building: { id: 1, name: 'RUANG BUGENVILLE' },
          },
        },
        room_class: { id: 3, code: 'KLS-3', name: 'Kelas 3' },
        class: { id: 3, code: 'KLS-3', name: 'Kelas 3' },
      },
      room_table_display_name: 'Kamar 1 - Bangsal Maternal Perinatal',
      room_form_display_name: 'Kamar 1 - Bangsal Maternal Perinatal - Kelas 3',
      created_at: '2026-10-22T08:00:00Z',
      updated_at: '2026-10-22T08:00:00Z',
    },
    {
      id: 2,
      room_id: 2,
      name: 'Bed 2',
      description: null,
      room: {
        id: 2,
        code: 'KMR-002',
        name: 'Kamar 2',
        ward: {
          id: 1,
          name: 'Bangsal Melati',
        },
        room_class: null,
        class: null,
      },
      room_table_display_name: 'Kamar 2 - Bangsal Melati',
      room_form_display_name: 'Kamar 2 - Bangsal Melati - (Kelas Belum Ditentukan)',
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

describe('BedView.vue and Bed Management', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    bedsService.getBeds.mockResolvedValue(mockPaginatedBedsResponse)
    roomsService.getRooms.mockResolvedValue({ data: mockRooms })
  })

  const mountView = async () => {
    const wrapper = mount(BedView, {
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
    expect(wrapper.text()).toContain('Data Bed')
    expect(wrapper.text()).toContain('Kelola data tempat tidur berdasarkan kamar, bangsal, dan kelas perawatan rumah sakit.')
  })

  it('renders table headers with required 6 columns in exact order', async () => {
    const wrapper = await mountView()
    const ths = wrapper.findAll('thead th')
    expect(ths).toHaveLength(6)
    expect(ths[0].text()).toBe('No')
    expect(ths[1].text()).toBe('Nama Kamar')
    expect(ths[2].text()).toBe('Nama Kelas')
    expect(ths[3].text()).toBe('Nama Bed')
    expect(ths[4].text()).toBe('Deskripsi')
    expect(ths[5].text()).toBe('Aksi')
  })

  it('renders Nama Kamar formatted as [Nama Kamar] - [Nama Bangsal]', async () => {
    const wrapper = await mountView()
    const rows = wrapper.findAll('tbody tr')
    expect(rows).toHaveLength(2)

    // First row: Kamar 1 - Bangsal Maternal Perinatal
    expect(rows[0].find('.col-room').text()).toBe('Kamar 1 - Bangsal Maternal Perinatal')
    // Second row: Kamar 2 - Bangsal Melati
    expect(rows[1].find('.col-room').text()).toBe('Kamar 2 - Bangsal Melati')
  })

  it('renders Nama Kelas in its own column and handles missing class with fallback', async () => {
    const wrapper = await mountView()
    const rows = wrapper.findAll('tbody tr')

    // First row: Kelas 3
    expect(rows[0].find('.col-class').text()).toBe('Kelas 3')
    // Second row: missing class renders —
    expect(rows[1].find('.col-class').text()).toBe('—')
  })

  it('renders Nama Bed and handles empty description with —', async () => {
    const wrapper = await mountView()
    const rows = wrapper.findAll('tbody tr')

    expect(rows[0].find('.col-name').text()).toBe('Bed 1')
    expect(rows[0].find('.col-description').text()).toBe('Bed dekat jendela')

    expect(rows[1].find('.col-name').text()).toBe('Bed 2')
    expect(rows[1].find('.col-description').text()).toBe('—')
  })

  it('opens add modal with room options formatted as Kamar - Bangsal - Kelas', async () => {
    const wrapper = await mountView()
    const addButton = wrapper.find('button.btn-primary')
    await addButton.trigger('click')
    await flushPromises()

    const modal = wrapper.findComponent({ name: 'BedFormModal' })
    expect(modal.exists()).toBe(true)
    expect(modal.props('isOpen')).toBe(true)
    expect(roomsService.getRooms).toHaveBeenCalled()
  })

  it('successfully creates bed and calls createBed with room_id and name', async () => {
    bedsService.createBed.mockResolvedValue({
      data: {
        id: 3,
        room_id: 1,
        name: 'Bed 3',
        description: null,
      },
    })

    const wrapper = await mountView()
    const addButton = wrapper.find('button.btn-primary')
    await addButton.trigger('click')
    await flushPromises()

    const modal = wrapper.findComponent({ name: 'BedFormModal' })
    modal.vm.$emit('submit', {
      room_id: 1,
      name: 'Bed 3',
      description: null,
    })
    await flushPromises()

    expect(bedsService.createBed).toHaveBeenCalledWith({
      room_id: 1,
      name: 'Bed 3',
      description: null,
    })
    expect(wrapper.text()).toContain('Data bed berhasil ditambahkan.')
  })

  it('opens edit modal with existing bed data', async () => {
    const wrapper = await mountView()
    const editBtn = wrapper.find('.actions-cell button[title="Edit"]')
    await editBtn.trigger('click')
    await flushPromises()

    const modal = wrapper.findComponent({ name: 'BedFormModal' })
    expect(modal.props('isOpen')).toBe(true)
    expect(modal.props('bed')).toBeTruthy()
    expect(modal.props('bed').name).toBe('Bed 1')
  })

  it('opens delete confirmation dialog with formatted message and deletes bed', async () => {
    bedsService.deleteBed.mockResolvedValue({ success: true })

    const wrapper = await mountView()
    const deleteBtn = wrapper.find('.actions-cell button[title="Hapus"]')
    await deleteBtn.trigger('click')
    await flushPromises()

    const dialog = wrapper.findComponent({ name: 'MasterDataDeleteDialog' })
    expect(dialog.props('isOpen')).toBe(true)
    expect(dialog.props('title')).toBe('Hapus Bed')
    expect(dialog.props('confirmMessage')).toContain('Apakah Anda yakin ingin menghapus “Bed 1” dari “Kamar 1 - Bangsal Maternal Perinatal”?')

    dialog.vm.$emit('confirm')
    await flushPromises()

    expect(bedsService.deleteBed).toHaveBeenCalledWith(1)
    expect(wrapper.text()).toContain('Data bed berhasil dihapus.')
  })
})
