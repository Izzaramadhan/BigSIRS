import { flushPromises, mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import RoomView from '../bed/RoomView.vue'
import { roomsService } from '@/services/master-data/rooms.service'
import { wardsService } from '@/services/master-data/wards.service'

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
      class_id: null,
      class: null,
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
          building: { id: 1, name: 'Gedung Utama' },
        },
      },
      ward_display_name: 'Bangsal Melati (Lantai 1 - Gedung Utama)',
      code: 'KMR-002',
      name: 'Kamar Melati 2',
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

describe('RoomView.vue', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    roomsService.getRooms.mockResolvedValue(mockPaginatedRoomsResponse)
    wardsService.getWards.mockResolvedValue({ data: mockWards })
  })

  const mountView = () =>
    mount(RoomView, {
      global: {
        stubs: {
          Teleport: true,
        },
      },
    })

  it('renders page header, breadcrumbs, add button, and table with exact 5 columns', async () => {
    const wrapper = mountView()
    await flushPromises()

    // Title and description
    expect(wrapper.text()).toContain('Data Kamar')
    expect(wrapper.text()).toContain(
      'Kelola data kamar berdasarkan bangsal, lantai, gedung, dan kelas perawatan rumah sakit.',
    )

    // Breadcrumbs
    expect(wrapper.text()).toContain('Dashboard')
    expect(wrapper.text()).toContain('Master Data')
    expect(wrapper.text()).toContain('Data Bed')
    expect(wrapper.text()).toContain('Data Kamar')

    // Add button
    expect(wrapper.text()).toContain('Tambah Kamar')

    // Exact 5 columns: No, Nama Bangsal, Nama Kamar, Nama Kelas, Aksi
    const ths = wrapper.findAll('thead th')
    expect(ths).toHaveLength(5)
    expect(ths[0].text()).toBe('No')
    expect(ths[1].text()).toBe('Nama Bangsal')
    expect(ths[2].text()).toBe('Nama Kamar')
    expect(ths[3].text()).toBe('Nama Kelas')
    expect(ths[4].text()).toBe('Aksi')

    // Ensure Code, Description, and Status headers are NOT present in table
    const headerTexts = ths.map((th) => th.text())
    expect(headerTexts).not.toContain('Kode')
    expect(headerTexts).not.toContain('Deskripsi')
    expect(headerTexts).not.toContain('Status')
  })

  it('displays ward format [Nama Bangsal] ([Nama Lantai] - [Nama Gedung]) and neutral dash for unintegrated class', async () => {
    const wrapper = mountView()
    await flushPromises()

    expect(wrapper.text()).toContain('Bangsal Maternal Perinatal (Lantai 2 - RUANG BUGENVILLE)')
    expect(wrapper.text()).toContain('Kamar Melati 1')
    expect(wrapper.text()).toContain('Bangsal Melati (Lantai 1 - Gedung Utama)')
    expect(wrapper.text()).toContain('Kamar Melati 2')

    // Kelas neutral dash
    const classCells = wrapper.findAll('tbody td.col-class')
    expect(classCells).toHaveLength(2)
    expect(classCells[0].text()).toBe('—')
    expect(classCells[1].text()).toBe('—')
  })

  it('calculates table row numbers correctly based on current_page and per_page', async () => {
    roomsService.getRooms.mockResolvedValue({
      data: [
        {
          id: 11,
          ward_id: 1,
          ward: { id: 1, name: 'Bangsal A', floor: { id: 1, name: 'Lantai 1', building: { id: 1, name: 'Gedung A' } } },
          name: 'Kamar 11',
          code: 'K-11',
          class: null,
        },
        {
          id: 12,
          ward_id: 1,
          ward: { id: 1, name: 'Bangsal A', floor: { id: 1, name: 'Lantai 1', building: { id: 1, name: 'Gedung A' } } },
          name: 'Kamar 12',
          code: 'K-12',
          class: null,
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

  it('handles add flow and shows exact success toast "Data kamar berhasil ditambahkan."', async () => {
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
        class_id: null,
        class: null,
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
    const codeInput = formModal.find('input#room-code')
    const nameInput = formModal.find('input#room-name')
    const classInput = formModal.find('input#room-class')
    const descInput = formModal.find('textarea#room-description')

    expect(codeInput.exists()).toBe(true)
    expect(nameInput.exists()).toBe(true)
    expect(classInput.exists()).toBe(true)
    expect(classInput.attributes('disabled')).toBeDefined()
    expect(descInput.exists()).toBe(true)

    // Fill form
    formModal.vm.form.ward_id = 3
    await codeInput.setValue('KMR-003')
    await nameInput.setValue('Kamar Melati 3')
    await descInput.setValue('Kamar baru')

    await formModal.find('form').trigger('submit')
    await flushPromises()

    expect(roomsService.createRoom).toHaveBeenCalledWith({
      ward_id: 3,
      code: 'KMR-003',
      name: 'Kamar Melati 3',
      class_id: null,
      description: 'Kamar baru',
    })

    expect(wrapper.text()).toContain('Data kamar berhasil ditambahkan.')
  })

  it('handles edit flow and shows exact success toast "Data kamar berhasil diperbarui."', async () => {
    roomsService.updateRoom.mockResolvedValue({
      data: {
        id: 1,
        ward_id: 3,
        code: 'KMR-001',
        name: 'Kamar Melati 1 Update',
        class_id: null,
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
    expect(deleteDialog.props('isOpen')).toBe(true)
    expect(deleteDialog.props('confirmMessage')).toBe(
      'Apakah Anda yakin ingin menghapus kamar “Kamar Melati 1” dari “Bangsal Maternal Perinatal (Lantai 2 - RUANG BUGENVILLE)”?',
    )

    deleteDialog.vm.$emit('confirm')
    await flushPromises()

    expect(roomsService.deleteRoom).toHaveBeenCalledWith(1)
    expect(wrapper.text()).toContain('Data kamar berhasil dihapus.')
  })

  it('displays warning when wards list is empty in modal and disables saving', async () => {
    wardsService.getWards.mockResolvedValue({ data: [] })

    const wrapper = mountView()
    await flushPromises()

    const addBtn = wrapper.find('.page-header button.btn-primary')
    await addBtn.trigger('click')
    await flushPromises()

    expect(wrapper.text()).toContain('Data Bangsal belum tersedia.')
    expect(wrapper.text()).toContain('Data Gedung')
    expect(wrapper.text()).toContain('Data Lantai')
    expect(wrapper.text()).toContain('Data Bangsal')

    const formModal = wrapper.findComponent({ name: 'RoomFormModal' })
    const masterModal = formModal.findComponent({ name: 'MasterDataFormModal' })
    expect(masterModal.props('isSubmitDisabled')).toBe(true)
  })

  it('handles validation errors on add and displays error toast without success toast', async () => {
    const errorResponse = {
      response: {
        status: 422,
        data: {
          message: 'The given data was invalid.',
          errors: {
            code: ['Kode kamar sudah digunakan.'],
            name: ['Nama kamar sudah digunakan pada bangsal yang dipilih.'],
          },
        },
      },
    }
    roomsService.createRoom.mockRejectedValue(errorResponse)

    const wrapper = mountView()
    await flushPromises()

    const addBtn = wrapper.find('.page-header button.btn-primary')
    await addBtn.trigger('click')
    await flushPromises()

    const formModal = wrapper.findComponent({ name: 'RoomFormModal' })
    formModal.vm.form.ward_id = 3
    await formModal.find('input#room-code').setValue('KMR-DUPLICATE')
    await formModal.find('input#room-name').setValue('Kamar Duplikat')

    await formModal.find('form').trigger('submit')
    await flushPromises()

    expect(wrapper.text()).toContain('Kode kamar sudah digunakan.')
    expect(wrapper.text()).toContain('Nama kamar sudah digunakan pada bangsal yang dipilih.')
    expect(wrapper.text()).not.toContain('Data kamar berhasil ditambahkan.')
  })
})
