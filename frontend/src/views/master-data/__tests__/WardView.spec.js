import { flushPromises, mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import WardView from '../bed/WardView.vue'
import { wardsService } from '@/services/master-data/wards.service'
import { floorsService } from '@/services/master-data/floors.service'

vi.mock('@/services/master-data/wards.service', () => ({
  wardsService: {
    getWards: vi.fn(),
    getWard: vi.fn(),
    createWard: vi.fn(),
    updateWard: vi.fn(),
    deleteWard: vi.fn(),
  },
}))

vi.mock('@/services/master-data/floors.service', () => ({
  floorsService: {
    getFloors: vi.fn(),
  },
}))

const mockFloors = [
  {
    id: 1,
    name: 'Lantai 1',
    building_id: 1,
    building: { id: 1, name: 'Gedung Utama' },
    display_name: 'Lantai 1 - Gedung Utama',
  },
  {
    id: 4,
    name: 'Lantai 4',
    building_id: 2,
    building: { id: 2, name: 'RUANG ARJUNA' },
    display_name: 'Lantai 4 - RUANG ARJUNA',
  },
]

const mockPaginatedResponse = {
  data: [
    {
      id: 1,
      floor_id: 4,
      floor: {
        id: 4,
        name: 'Lantai 4',
        building: { id: 2, name: 'RUANG ARJUNA' },
      },
      floor_display_name: 'Lantai 4 - RUANG ARJUNA',
      name: 'Bangsal Arjuna A',
      description: 'Bangsal perawatan pasien',
      created_at: '2026-10-22T08:00:00Z',
      updated_at: '2026-10-22T08:00:00Z',
    },
    {
      id: 2,
      floor_id: 1,
      floor: {
        id: 1,
        name: 'Lantai 1',
        building: { id: 1, name: 'Gedung Utama' },
      },
      floor_display_name: 'Lantai 1 - Gedung Utama',
      name: 'Bangsal Melati',
      description: null,
      created_at: '2026-10-22T08:00:00Z',
      updated_at: '2026-10-22T08:00:00Z',
    },
  ],
  meta: { current_page: 1, last_page: 1, per_page: 10, total: 2 },
  links: {},
}

describe('WardView.vue', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    wardsService.getWards.mockResolvedValue(mockPaginatedResponse)
    floorsService.getFloors.mockResolvedValue({ data: mockFloors })
  })

  const mountView = () =>
    mount(WardView, {
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
    expect(wrapper.text()).toContain('Data Bangsal')
    expect(wrapper.text()).toContain(
      'Kelola data bangsal berdasarkan lantai dan gedung untuk mendukung pengaturan ruangan serta tempat tidur rumah sakit.',
    )

    // Breadcrumbs
    expect(wrapper.text()).toContain('Dashboard')
    expect(wrapper.text()).toContain('Master Data')
    expect(wrapper.text()).toContain('Data Bed')
    expect(wrapper.text()).toContain('Data Bangsal')

    // Add button
    expect(wrapper.text()).toContain('Tambah Bangsal')

    // Table data with formatted floor - building
    expect(wrapper.text()).toContain('Lantai 4 - RUANG ARJUNA')
    expect(wrapper.text()).toContain('Bangsal Arjuna A')
    expect(wrapper.text()).toContain('Lantai 1 - Gedung Utama')
    expect(wrapper.text()).toContain('Bangsal Melati')

    // Exact 4 columns: No, Nama Lantai, Nama Bangsal, Aksi
    const ths = wrapper.findAll('thead th')
    expect(ths).toHaveLength(4)
    expect(ths[0].text()).toBe('No')
    expect(ths[1].text()).toBe('Nama Lantai')
    expect(ths[2].text()).toBe('Nama Bangsal')
    expect(ths[3].text()).toBe('Aksi')

    // Ensure description column header is NOT present in table
    const headerTexts = ths.map((th) => th.text())
    expect(headerTexts).not.toContain('Deskripsi')
    expect(headerTexts).not.toContain('Status')
  })

  it('calculates table row numbers correctly based on current_page and per_page', async () => {
    wardsService.getWards.mockResolvedValue({
      data: [
        {
          id: 11,
          floor_id: 1,
          floor: { id: 1, name: 'Lantai 1', building: { id: 1, name: 'Gedung A' } },
          name: 'Bangsal 11',
        },
        {
          id: 12,
          floor_id: 1,
          floor: { id: 1, name: 'Lantai 1', building: { id: 1, name: 'Gedung A' } },
          name: 'Bangsal 12',
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
    wardsService.getWards.mockResolvedValue({
      data: [],
      meta: { current_page: 1, last_page: 1, per_page: 10, total: 0 },
    })

    const wrapper = mountView()
    await flushPromises()

    expect(wrapper.text()).toContain('Belum ada data yang ditambahkan.')
  })

  it('shows error state when fetching wards fails', async () => {
    wardsService.getWards.mockRejectedValue({
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

    expect(wardsService.getWards).toHaveBeenCalled()
  })

  it('handles add flow and shows exact success toast "Data bangsal berhasil ditambahkan."', async () => {
    wardsService.createWard.mockResolvedValue({
      data: {
        id: 3,
        floor_id: 4,
        floor: { id: 4, name: 'Lantai 4', building: { id: 2, name: 'RUANG ARJUNA' } },
        floor_display_name: 'Lantai 4 - RUANG ARJUNA',
        name: 'Bangsal Arjuna B',
        description: 'Perawatan Anak',
      },
    })

    const wrapper = mountView()
    await flushPromises()

    // Open add modal
    const addBtn = wrapper.find('.page-header button.btn-primary')
    await addBtn.trigger('click')

    expect(wrapper.text()).toContain('Tambah Bangsal')

    const formModal = wrapper.findComponent({ name: 'WardFormModal' })
    expect(formModal.exists()).toBe(true)
    await flushPromises()

    const nameInput = formModal.find('input#ward-name')
    const descInput = formModal.find('textarea#ward-description')

    await nameInput.setValue('Bangsal Arjuna B')
    await descInput.setValue('Perawatan Anak')

    formModal.vm.form.floor_id = 4

    await formModal.find('form').trigger('submit')
    await flushPromises()

    expect(wardsService.createWard).toHaveBeenCalledWith({
      floor_id: 4,
      name: 'Bangsal Arjuna B',
      description: 'Perawatan Anak',
    })

    expect(wrapper.text()).toContain('Data bangsal berhasil ditambahkan.')
  })

  it('handles edit flow and shows exact success toast "Data bangsal berhasil diperbarui."', async () => {
    wardsService.updateWard.mockResolvedValue({
      data: {
        id: 1,
        floor_id: 4,
        floor: { id: 4, name: 'Lantai 4', building: { id: 2, name: 'RUANG ARJUNA' } },
        floor_display_name: 'Lantai 4 - RUANG ARJUNA',
        name: 'Bangsal Arjuna A Revisi',
        description: 'Deskripsi Baru',
      },
    })

    const wrapper = mountView()
    await flushPromises()

    // Click edit on first row
    const editBtn = wrapper.findAll('tbody tr')[0].findAll('button')[0]
    await editBtn.trigger('click')
    await flushPromises()

    expect(wrapper.text()).toContain('Edit Bangsal')

    const formModal = wrapper.findComponent({ name: 'WardFormModal' })
    const nameInput = formModal.find('input#ward-name')
    await nameInput.setValue('Bangsal Arjuna A Revisi')

    await formModal.find('form').trigger('submit')
    await flushPromises()

    expect(wardsService.updateWard).toHaveBeenCalledWith(
      1,
      expect.objectContaining({
        name: 'Bangsal Arjuna A Revisi',
      }),
    )

    expect(wrapper.text()).toContain('Data bangsal berhasil diperbarui.')
  })

  it('handles delete flow, shows confirmation with ward and floor+building name, and shows exact toast "Data bangsal berhasil dihapus."', async () => {
    wardsService.deleteWard.mockResolvedValue({ success: true })

    const wrapper = mountView()
    await flushPromises()

    // Click delete on first row (Bangsal Arjuna A from Lantai 4 - RUANG ARJUNA)
    const deleteBtn = wrapper.findAll('tbody tr')[0].findAll('button')[1]
    await deleteBtn.trigger('click')

    const deleteDialog = wrapper.findComponent({ name: 'MasterDataDeleteDialog' })
    expect(deleteDialog.exists()).toBe(true)
    expect(deleteDialog.props('isOpen')).toBe(true)
    expect(deleteDialog.props('confirmMessage')).toBe(
      'Apakah Anda yakin ingin menghapus bangsal “Bangsal Arjuna A” dari “Lantai 4 - RUANG ARJUNA”?',
    )

    deleteDialog.vm.$emit('confirm')
    await flushPromises()

    expect(wardsService.deleteWard).toHaveBeenCalledWith(1)
    expect(wrapper.text()).toContain('Data bangsal berhasil dihapus.')
  })

  it('displays warning when floors list is empty in modal and disables saving', async () => {
    floorsService.getFloors.mockResolvedValue({ data: [] })

    const wrapper = mountView()
    await flushPromises()

    const addBtn = wrapper.find('.page-header button.btn-primary')
    await addBtn.trigger('click')
    await flushPromises()

    expect(wrapper.text()).toContain('Data Lantai belum tersedia.')
    expect(wrapper.text()).toContain('Data Gedung dan Data Lantai')

    const formModal = wrapper.findComponent({ name: 'WardFormModal' })
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
            name: ['Nama bangsal sudah digunakan pada lantai yang dipilih.'],
          },
        },
      },
    }
    wardsService.createWard.mockRejectedValue(errorResponse)

    const wrapper = mountView()
    await flushPromises()

    const addBtn = wrapper.find('.page-header button.btn-primary')
    await addBtn.trigger('click')
    await flushPromises()

    const formModal = wrapper.findComponent({ name: 'WardFormModal' })
    formModal.vm.form.floor_id = 4
    const nameInput = formModal.find('input#ward-name')
    await nameInput.setValue('Bangsal Duplikat')

    await formModal.find('form').trigger('submit')
    await flushPromises()

    expect(wrapper.text()).toContain('Nama bangsal sudah digunakan pada lantai yang dipilih.')
    expect(wrapper.text()).not.toContain('Data bangsal berhasil ditambahkan.')
  })
})
