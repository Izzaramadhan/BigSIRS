import { flushPromises, mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import FloorView from '../bed/FloorView.vue'
import { floorsService } from '@/services/master-data/floors.service'
import { buildingsService } from '@/services/master-data/buildings.service'

vi.mock('@/services/master-data/floors.service', () => ({
  floorsService: {
    getFloors: vi.fn(),
    getFloor: vi.fn(),
    createFloor: vi.fn(),
    updateFloor: vi.fn(),
    deleteFloor: vi.fn(),
  },
}))

vi.mock('@/services/master-data/buildings.service', () => ({
  buildingsService: {
    getBuildings: vi.fn(),
  },
}))

const mockBuildings = [
  { id: 1, name: 'Gedung Utama' },
  { id: 2, name: 'Gedung Paviliun' },
]

const mockPaginatedResponse = {
  data: [
    {
      id: 1,
      building_id: 1,
      building: { id: 1, name: 'Gedung Utama' },
      name: 'Lantai 1',
      description: 'Lantai Poliklinik',
      created_at: '2026-10-22T08:00:00Z',
      updated_at: '2026-10-22T08:00:00Z',
    },
    {
      id: 2,
      building_id: 2,
      building: { id: 2, name: 'Gedung Paviliun' },
      name: 'Lantai 2',
      description: null,
      created_at: '2026-10-22T08:00:00Z',
      updated_at: '2026-10-22T08:00:00Z',
    },
  ],
  meta: { current_page: 1, last_page: 1, per_page: 10, total: 2 },
  links: {},
}

describe('FloorView.vue', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    floorsService.getFloors.mockResolvedValue(mockPaginatedResponse)
    buildingsService.getBuildings.mockResolvedValue({ data: mockBuildings })
  })

  const mountView = () =>
    mount(FloorView, {
      global: {
        stubs: {
          Teleport: true,
        },
      },
    })

  it('renders page header, breadcrumbs, add button, and table with 4 columns', async () => {
    const wrapper = mountView()
    await flushPromises()

    // Title and subtitle
    expect(wrapper.text()).toContain('Data Lantai')
    expect(wrapper.text()).toContain(
      'Kelola data lantai berdasarkan gedung untuk mendukung pengaturan ruangan dan tempat tidur rumah sakit.',
    )

    // Breadcrumbs
    expect(wrapper.text()).toContain('Dashboard')
    expect(wrapper.text()).toContain('Master Data')
    expect(wrapper.text()).toContain('Data Bed')

    // Add button
    expect(wrapper.text()).toContain('Tambah Lantai')

    // Table data
    expect(wrapper.text()).toContain('Gedung Utama')
    expect(wrapper.text()).toContain('Lantai 1')
    expect(wrapper.text()).toContain('Gedung Paviliun')
    expect(wrapper.text()).toContain('Lantai 2')

    // Exact 4 columns: No, Nama Gedung, Nama Lantai, Aksi
    const ths = wrapper.findAll('thead th')
    expect(ths).toHaveLength(4)
    expect(ths[0].text()).toBe('No')
    expect(ths[1].text()).toBe('Nama Gedung')
    expect(ths[2].text()).toBe('Nama Lantai')
    expect(ths[3].text()).toBe('Aksi')

    // Ensure description column header is NOT present in table
    const headerTexts = ths.map((th) => th.text())
    expect(headerTexts).not.toContain('Deskripsi')
  })

  it('calculates table row numbers correctly based on current_page and per_page', async () => {
    floorsService.getFloors.mockResolvedValue({
      data: [
        { id: 11, building_id: 1, building: { id: 1, name: 'Gedung A' }, name: 'Lantai 11' },
        { id: 12, building_id: 1, building: { id: 1, name: 'Gedung A' }, name: 'Lantai 12' },
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
    floorsService.getFloors.mockResolvedValue({
      data: [],
      meta: { current_page: 1, last_page: 1, per_page: 10, total: 0 },
    })

    const wrapper = mountView()
    await flushPromises()

    expect(wrapper.text()).toContain('Belum ada data yang ditambahkan.')
  })

  it('shows error state when fetching floors fails', async () => {
    floorsService.getFloors.mockRejectedValue({
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

    expect(floorsService.getFloors).toHaveBeenCalled()
  })

  it('handles add flow and shows exact success toast "Data lantai berhasil ditambahkan."', async () => {
    floorsService.createFloor.mockResolvedValue({
      data: {
        id: 3,
        building_id: 1,
        building: { id: 1, name: 'Gedung Utama' },
        name: 'Lantai 3',
        description: 'Lantai Rawat Inap',
      },
    })

    const wrapper = mountView()
    await flushPromises()

    // Open add modal
    const addBtn = wrapper.find('.page-header button.btn-primary')
    await addBtn.trigger('click')

    expect(wrapper.text()).toContain('Tambah Lantai')

    const formModal = wrapper.findComponent({ name: 'FloorFormModal' })
    expect(formModal.exists()).toBe(true)
    await flushPromises()

    const nameInput = formModal.find('input#floor-name')
    const descInput = formModal.find('textarea#floor-description')

    await nameInput.setValue('Lantai 3')
    await descInput.setValue('Lantai Rawat Inap')

    formModal.vm.form.building_id = 1

    await formModal.find('form').trigger('submit')
    await flushPromises()

    expect(floorsService.createFloor).toHaveBeenCalledWith({
      building_id: 1,
      name: 'Lantai 3',
      description: 'Lantai Rawat Inap',
    })

    expect(wrapper.text()).toContain('Data lantai berhasil ditambahkan.')
  })

  it('handles edit flow and shows exact success toast "Data lantai berhasil diperbarui."', async () => {
    floorsService.updateFloor.mockResolvedValue({
      data: {
        id: 1,
        building_id: 1,
        building: { id: 1, name: 'Gedung Utama' },
        name: 'Lantai 1 Revisi',
        description: 'Deskripsi Baru',
      },
    })

    const wrapper = mountView()
    await flushPromises()

    // Click edit on first row
    const editBtn = wrapper.findAll('tbody tr')[0].findAll('button')[0]
    await editBtn.trigger('click')
    await flushPromises()

    expect(wrapper.text()).toContain('Edit Lantai')

    const formModal = wrapper.findComponent({ name: 'FloorFormModal' })
    const nameInput = formModal.find('input#floor-name')
    await nameInput.setValue('Lantai 1 Revisi')

    await formModal.find('form').trigger('submit')
    await flushPromises()

    expect(floorsService.updateFloor).toHaveBeenCalledWith(
      1,
      expect.objectContaining({
        name: 'Lantai 1 Revisi',
      }),
    )

    expect(wrapper.text()).toContain('Data lantai berhasil diperbarui.')
  })

  it('handles delete flow, shows confirmation with floor and building name, and shows exact toast "Data lantai berhasil dihapus."', async () => {
    floorsService.deleteFloor.mockResolvedValue({ success: true })

    const wrapper = mountView()
    await flushPromises()

    // Click delete on first row (Lantai 1 from Gedung Utama)
    const deleteBtn = wrapper.findAll('tbody tr')[0].findAll('button')[1]
    await deleteBtn.trigger('click')

    const deleteDialog = wrapper.findComponent({ name: 'MasterDataDeleteDialog' })
    expect(deleteDialog.exists()).toBe(true)
    expect(deleteDialog.props('isOpen')).toBe(true)
    expect(deleteDialog.props('confirmMessage')).toBe(
      'Apakah Anda yakin ingin menghapus lantai “Lantai 1” dari “Gedung Utama”?',
    )

    deleteDialog.vm.$emit('confirm')
    await flushPromises()

    expect(floorsService.deleteFloor).toHaveBeenCalledWith(1)
    expect(wrapper.text()).toContain('Data lantai berhasil dihapus.')
  })

  it('displays warning when buildings list is empty in modal', async () => {
    buildingsService.getBuildings.mockResolvedValue({ data: [] })

    const wrapper = mountView()
    await flushPromises()

    const addBtn = wrapper.find('.page-header button.btn-primary')
    await addBtn.trigger('click')
    await flushPromises()

    expect(wrapper.text()).toContain('Data Gedung belum tersedia.')
  })

  it('handles validation errors on add and displays error toast without success toast', async () => {
    const errorResponse = {
      response: {
        status: 422,
        data: {
          message: 'The given data was invalid.',
          errors: {
            name: ['Nama lantai sudah digunakan pada gedung yang dipilih.'],
          },
        },
      },
    }
    floorsService.createFloor.mockRejectedValue(errorResponse)

    const wrapper = mountView()
    await flushPromises()

    const addBtn = wrapper.find('.page-header button.btn-primary')
    await addBtn.trigger('click')
    await flushPromises()

    const formModal = wrapper.findComponent({ name: 'FloorFormModal' })
    formModal.vm.form.building_id = 1
    const nameInput = formModal.find('input#floor-name')
    await nameInput.setValue('Lantai Duplikat')

    await formModal.find('form').trigger('submit')
    await flushPromises()

    expect(wrapper.text()).toContain('Nama lantai sudah digunakan pada gedung yang dipilih.')
    expect(wrapper.text()).not.toContain('Data lantai berhasil ditambahkan.')
  })
})
