import { flushPromises, mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import RoomView from '../bed/RoomView.vue'
import { roomsService } from '@/services/master-data/rooms.service'
import { wardsService } from '@/services/master-data/wards.service'
import { roomClassesService } from '@/services/master-data/roomClasses.service'

vi.mock('@/services/master-data/rooms.service', () => ({
  roomsService: {
    getRooms: vi.fn(),
    getRoom: vi.fn(),
    createRoom: vi.fn(),
    updateRoom: vi.fn(),
    deleteRoom: vi.fn(),
  },
}))

vi.mock('@/services/master-data/wards.service', () => ({
  wardsService: {
    getWards: vi.fn(),
  },
}))

vi.mock('@/services/master-data/roomClasses.service', () => ({
  roomClassesService: {
    getRoomClasses: vi.fn(),
    getRoomClassOptions: vi.fn(),
    createRoomClass: vi.fn(),
    getRoomClass: vi.fn(),
  },
}))

const mockWards = [
  {
    id: 1,
    name: 'Bangsal Melati',
    floor_id: 1,
    floor: {
      id: 1,
      name: 'Lantai 1',
      building_id: 1,
      building: { id: 1, name: 'Gedung Utama' },
    },
    ward_display_name: 'Bangsal Melati (Lantai 1 - Gedung Utama)',
    display_name: 'Bangsal Melati (Lantai 1 - Gedung Utama)',
  },
  {
    id: 3,
    name: 'Bangsal Maternal Perinatal',
    floor_id: 2,
    floor: {
      id: 2,
      name: 'Lantai 2',
      building_id: 1,
      building: { id: 1, name: 'RUANG BUGENVILLE' },
    },
    ward_display_name: 'Bangsal Maternal Perinatal (Lantai 2 - RUANG BUGENVILLE)',
    display_name: 'Bangsal Maternal Perinatal (Lantai 2 - RUANG BUGENVILLE)',
  },
]

const mockRoomClasses = [
  { id: 1, name: 'Kelas I', code: 'KL1' },
  { id: 2, name: 'Kelas II', code: 'KL2' },
]

const mockPaginatedRoomsResponse = {
  data: [
    {
      id: 1,
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
      ward_display_name: 'Bangsal Maternal Perinatal (Lantai 2 - RUANG BUGENVILLE)',
      code: 'KMR-001',
      name: 'Kamar Melati 1',
      room_class_id: 2,
      room_class: { id: 2, code: 'KLS-02', name: 'Kelas II' },
      class_id: 2,
      class: { id: 2, code: 'KLS-02', name: 'Kelas II' },
      description: 'Kamar perawatan maternal',
      created_at: '2026-10-22T08:00:00Z',
      updated_at: '2026-10-22T08:00:00Z',
    },
    {
      id: 2,
      ward_id: 1,
      ward: {
        id: 1,
        name: 'Bangsal Melati',
        floor: {
          id: 1,
          name: 'Lantai 1',
          building_id: 1,
          building: { id: 1, name: 'Gedung Utama' },
        },
      },
      ward_display_name: 'Bangsal Melati (Lantai 1 - Gedung Utama)',
      code: 'KMR-002',
      name: 'Kamar Melati 2',
      room_class_id: null,
      room_class: null,
      class_id: null,
      class: null,
      description: null,
      created_at: '2026-10-22T08:00:00Z',
      updated_at: '2026-10-22T08:00:00Z',
    },
  ],
  meta: { current_page: 1, last_page: 1, per_page: 10, total: 2 },
  links: {},
}

describe('RoomView.vue and Room Management with Kelas', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    roomsService.getRooms.mockResolvedValue(mockPaginatedRoomsResponse)
    wardsService.getWards.mockResolvedValue({ data: mockWards })
    roomClassesService.getRoomClassOptions.mockResolvedValue({ data: mockRoomClasses })
  })

  const mountView = () => {
    return mount(RoomView, {
      global: {
        stubs: {
          Teleport: true,
        },
      },
    })
  }

  it('renders page header with title, subtitle, breadcrumbs and actions', async () => {
    const wrapper = mountView()
    await flushPromises()

    expect(wrapper.text()).toContain('Data Kamar')
    expect(wrapper.text()).toContain(
      'Kelola data kamar berdasarkan bangsal, lantai, gedung, dan kelas perawatan rumah sakit.',
    )
    expect(wrapper.text()).toContain('Tambah Kamar')

    const breadcrumbSpans = wrapper.find('.breadcrumbs').findAll('span:not(.separator)')
    expect(breadcrumbSpans.length).toBe(4)
    expect(breadcrumbSpans[0].text()).toContain('Dashboard')
    expect(breadcrumbSpans[1].text()).toContain('Master Data')
    expect(breadcrumbSpans[2].text()).toContain('Data Bed')
    expect(breadcrumbSpans[3].text()).toContain('Data Kamar')
  })

  it('renders table columns correctly including Nama Kelas and displays class name or —', async () => {
    const wrapper = mountView()
    await flushPromises()

    const headers = wrapper.findAll('thead th')
    expect(headers[0].text()).toBe('No')
    expect(headers[1].text()).toBe('Nama Bangsal')
    expect(headers[2].text()).toBe('Nama Kamar')
    expect(headers[3].text()).toBe('Nama Kelas')
    expect(headers[4].text()).toBe('Aksi')

    const rows = wrapper.findAll('tbody tr')
    expect(rows.length).toBe(2)

    // First row has Kelas II
    expect(rows[0].find('.col-ward').text()).toBe(
      'Bangsal Maternal Perinatal (Lantai 2 - RUANG BUGENVILLE)',
    )
    expect(rows[0].find('.col-name').text()).toBe('Kamar Melati 1')
    expect(rows[0].find('.col-class').text()).toBe('Kelas II')

    // Second row has no class (legacy), displays —
    expect(rows[1].find('.col-ward').text()).toBe('Bangsal Melati (Lantai 1 - Gedung Utama)')
    expect(rows[1].find('.col-name').text()).toBe('Kamar Melati 2')
    expect(rows[1].find('.col-class').text()).toBe('—')
  })

  it('calculates row numbers correctly on page 2', async () => {
    roomsService.getRooms.mockResolvedValue({
      data: [
        {
          id: 11,
          ward_id: 1,
          ward: { id: 1, name: 'Bangsal A', floor: { id: 1, name: 'Lantai 1', building: { id: 1, name: 'Gedung A' } } },
          name: 'Kamar 11',
          code: 'K-11',
          room_class: { id: 1, name: 'Kelas I' },
        },
        {
          id: 12,
          ward_id: 1,
          ward: { id: 1, name: 'Bangsal A', floor: { id: 1, name: 'Lantai 1', building: { id: 1, name: 'Gedung A' } } },
          name: 'Kamar 12',
          code: 'K-12',
          room_class: null,
        },
      ],
      meta: { current_page: 2, last_page: 2, per_page: 10, total: 12 },
    })

    const wrapper = mountView()
    await flushPromises()

    const rows = wrapper.findAll('tbody tr')
    expect(rows[0].find('.col-no').text()).toBe('11')
    expect(rows[1].find('.col-no').text()).toBe('12')
  })

  it('shows empty state when data is empty', async () => {
    roomsService.getRooms.mockResolvedValue({
      data: [],
      meta: { current_page: 1, last_page: 1, per_page: 10, total: 0 },
    })

    const wrapper = mountView()
    await flushPromises()

    expect(wrapper.text()).toContain('Belum ada data yang ditambahkan.')
  })

  it('shows error state when fetching rooms fails', async () => {
    roomsService.getRooms.mockRejectedValue({
      response: {
        data: { message: 'Koneksi ke server terputus.' },
      },
    })

    const wrapper = mountView()
    await flushPromises()

    expect(wrapper.text()).toContain('Koneksi ke server terputus.')
    expect(wrapper.text()).not.toContain('Belum ada data yang ditambahkan.')
  })

  it('handles search input and refresh trigger', async () => {
    const wrapper = mountView()
    await flushPromises()

    const searchInput = wrapper.find('.search-input')
    expect(searchInput.exists()).toBe(true)

    const refreshBtn = wrapper.find('button[aria-label="Refresh data"]')
    expect(refreshBtn.exists()).toBe(true)
    await refreshBtn.trigger('click')
    await flushPromises()

    expect(roomsService.getRooms).toHaveBeenCalled()
  })

  it('handles add flow with active Kelas select and shows exact success toast "Data kamar berhasil ditambahkan."', async () => {
    roomsService.createRoom.mockResolvedValue({
      data: {
        id: 3,
        ward_id: 3,
        ward: {
          id: 3,
          name: 'Bangsal Maternal Perinatal',
          floor: { id: 2, name: 'Lantai 2', building: { id: 1, name: 'RUANG BUGENVILLE' } },
        },
        ward_display_name: 'Bangsal Maternal Perinatal (Lantai 2 - RUANG BUGENVILLE)',
        code: 'KMR-003',
        name: 'Kamar Melati 3',
        room_class_id: 2,
        room_class: { id: 2, code: 'KL2', name: 'Kelas II' },
        description: 'Kamar baru',
      },
    })

    const wrapper = mountView()
    await flushPromises()

    // Open add modal
    const addBtn = wrapper.find('.page-header button.btn-primary')
    await addBtn.trigger('click')

    expect(wrapper.text()).toContain('Tambah Kamar')

    const formModal = wrapper.findComponent({ name: 'RoomFormModal' })
    expect(formModal.exists()).toBe(true)
    await flushPromises()

    // Check fields in order: Bangsal, Kode, Nama Kamar, Kelas, Deskripsi
    const wardSelect = formModal.find('#room-ward-id')
    const codeInput = formModal.find('input#room-code')
    const nameInput = formModal.find('input#room-name')
    const classSelect = formModal.find('#room-class-id')
    const descInput = formModal.find('textarea#room-description')

    expect(wardSelect.exists()).toBe(true)
    expect(codeInput.exists()).toBe(true)
    expect(nameInput.exists()).toBe(true)
    expect(classSelect.exists()).toBe(true)
    expect(descInput.exists()).toBe(true)

    // Fill form
    formModal.vm.form.ward_id = 3
    await codeInput.setValue('KMR-003')
    await nameInput.setValue('Kamar Melati 3')
    formModal.vm.form.room_class_id = 2
    await descInput.setValue('Kamar baru')

    await formModal.find('form').trigger('submit')
    await flushPromises()

    expect(roomsService.createRoom).toHaveBeenCalledWith({
      ward_id: 3,
      code: 'KMR-003',
      name: 'Kamar Melati 3',
      room_class_id: 2,
      class_id: 2,
      description: 'Kamar baru',
    })

    expect(wrapper.text()).toContain('Data kamar berhasil ditambahkan.')
  })

  it('supports adding a new room class inline from inside the room form', async () => {
    roomClassesService.createRoomClass.mockResolvedValue({
      data: {
        id: 99,
        code: 'KLS-99',
        name: 'Kelas VVIP Baru',
      },
    })

    const wrapper = mountView()
    await flushPromises()

    // Open add modal
    await wrapper.find('.page-header button.btn-primary').trigger('click')
    await flushPromises()

    const formModal = wrapper.findComponent({ name: 'RoomFormModal' })

    // Simulate searchable select create event
    formModal.vm.handleCreateRoomClass('Kelas VVIP Baru')
    await flushPromises()

    const addClassDialog = formModal.findComponent({ name: 'AddRoomClassDialog' })
    expect(addClassDialog.exists()).toBe(true)
    expect(addClassDialog.props('isOpen')).toBe(true)
    expect(addClassDialog.props('initialName')).toBe('Kelas VVIP Baru')

    // Confirm add class
    await addClassDialog.vm.$emit('submit', 'Kelas VVIP Baru')
    await flushPromises()

    expect(roomClassesService.createRoomClass).toHaveBeenCalledWith({
      name: 'Kelas VVIP Baru',
    })

    // Auto-selects the newly created class
    expect(formModal.vm.form.room_class_id).toBe(99)
    expect(wrapper.text()).toContain('Data kelas berhasil ditambahkan.')
  })

  it('handles edit flow, pre-selects existing class, and shows exact success toast "Data kamar berhasil diperbarui."', async () => {
    roomsService.updateRoom.mockResolvedValue({
      data: {
        id: 1,
        ward_id: 3,
        code: 'KMR-001',
        name: 'Kamar Melati 1 Update',
        room_class_id: 1,
        room_class: { id: 1, name: 'Kelas I' },
        description: 'Deskripsi Update',
      },
    })

    const wrapper = mountView()
    await flushPromises()

    // Click edit on first row
    const editBtn = wrapper.findAll('tbody tr')[0].findAll('button')[0]
    await editBtn.trigger('click')
    await flushPromises()

    expect(wrapper.text()).toContain('Edit Kamar')

    const formModal = wrapper.findComponent({ name: 'RoomFormModal' })
    expect(formModal.vm.form.ward_id).toBe(3)
    expect(formModal.vm.form.code).toBe('KMR-001')
    expect(formModal.vm.form.name).toBe('Kamar Melati 1')
    // Pre-selects previous class (id 2)
    expect(formModal.vm.form.room_class_id).toBe(2)

    // Change class to 1
    formModal.vm.form.room_class_id = 1
    const nameInput = formModal.find('input#room-name')
    await nameInput.setValue('Kamar Melati 1 Update')

    await formModal.find('form').trigger('submit')
    await flushPromises()

    expect(roomsService.updateRoom).toHaveBeenCalledWith(
      1,
      expect.objectContaining({
        ward_id: 3,
        code: 'KMR-001',
        name: 'Kamar Melati 1 Update',
        room_class_id: 1,
      }),
    )

    expect(wrapper.text()).toContain('Data kamar berhasil diperbarui.')
  })

  it('handles delete flow, shows confirmation with kamar and ward name, and shows exact toast "Data kamar berhasil dihapus."', async () => {
    roomsService.deleteRoom.mockResolvedValue({ success: true })

    const wrapper = mountView()
    await flushPromises()

    // Click delete on first row
    const deleteBtn = wrapper.findAll('tbody tr')[0].findAll('button')[1]
    await deleteBtn.trigger('click')

    const deleteDialog = wrapper.findComponent({ name: 'MasterDataDeleteDialog' })
    expect(deleteDialog.exists()).toBe(true)
    expect(deleteDialog.props('confirmMessage')).toContain('Kamar Melati 1')
    expect(deleteDialog.props('confirmMessage')).toContain(
      'Bangsal Maternal Perinatal (Lantai 2 - RUANG BUGENVILLE)',
    )

    // Confirm deletion
    const confirmBtn = deleteDialog.find('button.btn-danger')
    await confirmBtn.trigger('click')
    await flushPromises()

    expect(roomsService.deleteRoom).toHaveBeenCalledWith(1)
    expect(wrapper.text()).toContain('Data kamar berhasil dihapus.')
  })
})
