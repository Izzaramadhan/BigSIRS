import { flushPromises, mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import BuildingView from '../bed/BuildingView.vue'
import { buildingsService } from '@/services/master-data/buildings.service'

vi.mock('@/services/master-data/buildings.service', () => ({
  buildingsService: {
    getBuildings: vi.fn(),
    getBuilding: vi.fn(),
    createBuilding: vi.fn(),
    updateBuilding: vi.fn(),
    deleteBuilding: vi.fn(),
  },
}))

const mockPaginatedResponse = {
  data: [
    {
      id: 1,
      name: 'Gedung Teratai',
      description: 'Gedung Ruang Operasi Bedah',
      created_at: '2026-10-22T08:00:00Z',
      updated_at: '2026-10-22T08:00:00Z',
    },
    {
      id: 2,
      name: 'Gedung Mawar',
      description: null,
      created_at: '2026-10-22T08:00:00Z',
      updated_at: '2026-10-22T08:00:00Z',
    },
  ],
  meta: { current_page: 1, last_page: 1, per_page: 10, total: 2 },
  links: {},
}

describe('BuildingView.vue', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    buildingsService.getBuildings.mockResolvedValue(mockPaginatedResponse)
  })

  const mountView = () =>
    mount(BuildingView, {
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
    expect(wrapper.text()).toContain('Data Gedung')
    expect(wrapper.text()).toContain(
      'Kelola data gedung yang digunakan dalam pengaturan ruangan dan tempat tidur rumah sakit.',
    )

    // Breadcrumbs
    expect(wrapper.text()).toContain('Dashboard')
    expect(wrapper.text()).toContain('Master Data')
    expect(wrapper.text()).toContain('Data Bed')

    // Add button
    expect(wrapper.text()).toContain('Tambah Gedung')

    // Table data and fallback for empty description
    expect(wrapper.text()).toContain('Gedung Teratai')
    expect(wrapper.text()).toContain('Gedung Ruang Operasi Bedah')
    expect(wrapper.text()).toContain('Gedung Mawar')
    expect(wrapper.text()).toContain('-')

    // Exact 4 table columns: No, Nama, Deskripsi, Aksi
    const ths = wrapper.findAll('thead th')
    expect(ths).toHaveLength(4)
    expect(ths[0].text()).toBe('No')
    expect(ths[1].text()).toBe('Nama')
    expect(ths[2].text()).toBe('Deskripsi')
    expect(ths[3].text()).toBe('Aksi')
  })

  it('shows empty state when data is empty', async () => {
    buildingsService.getBuildings.mockResolvedValue({
      data: [],
      meta: { current_page: 1, last_page: 1, per_page: 10, total: 0 },
    })

    const wrapper = mountView()
    await flushPromises()

    expect(wrapper.text()).toContain('Belum ada data yang ditambahkan.')
  })

  it('calculates table row numbers correctly based on current_page and per_page', async () => {
    buildingsService.getBuildings.mockResolvedValue({
      data: [
        { id: 11, name: 'Gedung Halmahera', description: null },
        { id: 12, name: 'Gedung Irian', description: null },
      ],
      meta: { current_page: 2, last_page: 2, per_page: 10, total: 12 },
    })

    const wrapper = mountView()
    await flushPromises()

    const rows = wrapper.findAll('tbody tr')
    expect(rows[0].find('.col-no').text()).toBe('11')
    expect(rows[1].find('.col-no').text()).toBe('12')
  })

  it('shows error state when fetching data fails with informative message', async () => {
    buildingsService.getBuildings.mockRejectedValue({
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

    expect(buildingsService.getBuildings).toHaveBeenCalled()
  })

  it('handles add flow and shows exact success toast "Data gedung berhasil ditambahkan."', async () => {
    buildingsService.createBuilding.mockResolvedValue({
      data: {
        id: 3,
        name: 'Gedung Cempaka',
        description: 'Gedung Rawat Inap Kelas 1',
      },
    })

    const wrapper = mountView()
    await flushPromises()

    // Click "Tambah Gedung"
    const addBtn = wrapper.find('.page-header button.btn-primary')
    await addBtn.trigger('click')

    // Check modal opened with Tambah title
    expect(wrapper.text()).toContain('Tambah Gedung')

    const formModal = wrapper.findComponent({ name: 'BuildingFormModal' })
    expect(formModal.exists()).toBe(true)

    const nameInput = formModal.find('input#building-name')
    const descInput = formModal.find('textarea#building-description')

    await nameInput.setValue('Gedung Cempaka')
    await descInput.setValue('Gedung Rawat Inap Kelas 1')

    await formModal.find('form').trigger('submit')
    await flushPromises()

    expect(buildingsService.createBuilding).toHaveBeenCalledWith({
      name: 'Gedung Cempaka',
      description: 'Gedung Rawat Inap Kelas 1',
    })

    expect(wrapper.text()).toContain('Data gedung berhasil ditambahkan.')
  })

  it('handles edit flow and shows exact success toast "Data gedung berhasil diperbarui."', async () => {
    buildingsService.updateBuilding.mockResolvedValue({
      data: {
        id: 1,
        name: 'Gedung Teratai Revisi',
        description: 'Deskripsi Baru',
      },
    })

    const wrapper = mountView()
    await flushPromises()

    // Click edit on first row
    const editBtn = wrapper.findAll('tbody tr')[0].findAll('button')[0]
    await editBtn.trigger('click')

    expect(wrapper.text()).toContain('Edit Gedung')

    const formModal = wrapper.findComponent({ name: 'BuildingFormModal' })
    const nameInput = formModal.find('input#building-name')
    await nameInput.setValue('Gedung Teratai Revisi')

    await formModal.find('form').trigger('submit')
    await flushPromises()

    expect(buildingsService.updateBuilding).toHaveBeenCalledWith(
      1,
      expect.objectContaining({
        name: 'Gedung Teratai Revisi',
      }),
    )

    expect(wrapper.text()).toContain('Data gedung berhasil diperbarui.')
  })

  it('handles delete flow, shows confirmation with building name, and shows exact toast "Data gedung berhasil dihapus."', async () => {
    buildingsService.deleteBuilding.mockResolvedValue({ success: true })

    const wrapper = mountView()
    await flushPromises()

    // Click delete on first row (Gedung Teratai)
    const deleteBtn = wrapper.findAll('tbody tr')[0].findAll('button')[1]
    await deleteBtn.trigger('click')

    const deleteDialog = wrapper.findComponent({ name: 'MasterDataDeleteDialog' })
    expect(deleteDialog.exists()).toBe(true)
    expect(deleteDialog.props('isOpen')).toBe(true)
    expect(deleteDialog.props('confirmMessage')).toBe(
      'Apakah Anda yakin ingin menghapus gedung “Gedung Teratai”?',
    )

    deleteDialog.vm.$emit('confirm')
    await flushPromises()

    expect(buildingsService.deleteBuilding).toHaveBeenCalledWith(1)
    expect(wrapper.text()).toContain('Data gedung berhasil dihapus.')
  })

  it('handles validation errors on add and displays error toast without success toast', async () => {
    const errorResponse = {
      response: {
        status: 422,
        data: {
          message: 'The given data was invalid.',
          errors: {
            name: ['Nama gedung sudah digunakan.'],
          },
        },
      },
    }
    buildingsService.createBuilding.mockRejectedValue(errorResponse)

    const wrapper = mountView()
    await flushPromises()

    const addBtn = wrapper.find('.page-header button.btn-primary')
    await addBtn.trigger('click')

    const formModal = wrapper.findComponent({ name: 'BuildingFormModal' })
    const nameInput = formModal.find('input#building-name')
    await nameInput.setValue('Gedung Duplikat')

    await formModal.find('form').trigger('submit')
    await flushPromises()

    expect(wrapper.text()).toContain('Nama gedung sudah digunakan.')
    expect(wrapper.text()).not.toContain('Data gedung berhasil ditambahkan.')
  })
})
