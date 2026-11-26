import { nextTick } from 'vue'
import { flushPromises, mount } from '@vue/test-utils'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import FloorView from '../bed/FloorView.vue'
import SearchableSelect from '@/components/common/SearchableSelect.vue'
import FloorFormModal from '@/components/master-data/floors/FloorFormModal.vue'
import BaseConfirmDialog from '@/components/ui/BaseConfirmDialog.vue'
import { useToast } from '@/composables/core/useToast'
import { useFloors } from '@/composables/useFloors'
import { buildingsService } from '@/services/master-data/buildings.service'
import { floorsService } from '@/services/master-data/floors.service'

vi.mock('@/services/master-data/floors.service', async (importOriginal) => {
  const actual = await importOriginal()
  return {
    floorsService: {
      ...actual.floorsService,
      list: vi.fn(),
      get: vi.fn(),
      create: vi.fn(),
      update: vi.fn(),
      remove: vi.fn(),
      getFloors: vi.fn(),
    },
  }
})

vi.mock('@/services/master-data/buildings.service', async (importOriginal) => {
  const actual = await importOriginal()
  return {
    buildingsService: {
      ...actual.buildingsService,
      list: vi.fn(),
      getBuildings: vi.fn(),
    },
  }
})

const mockBuildings = [
  { id: 1, name: 'Gedung Utama' },
  { id: 2, name: 'Gedung Paviliun' },
]

const mockFloors = [
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
]

const listResponse = (data = mockFloors, metaOverrides = {}) => ({
  data,
  meta: {
    current_page: 1,
    last_page: 1,
    per_page: 10,
    total: data.length,
    from: data.length > 0 ? 1 : 0,
    to: data.length,
    ...metaOverrides,
  },
})

const toast = useToast()
const toastMessages = () => toast.toasts.value.map((item) => item.message)

async function mountView() {
  const wrapper = mount(FloorView, {
    global: {
      stubs: {
        Teleport: true,
      },
    },
  })
  await flushPromises()
  return wrapper
}

const findButtonByText = (wrapper, label) =>
  wrapper.findAll('button').find((button) => button.text().trim() === label)

const findControlByLabel = (wrapper, labelText) => {
  const label = wrapper
    .findAll('label')
    .find((node) => node.text().replace('*', '').trim() === labelText)
  const forId = label.attributes('for')
  return wrapper.get(`[id="${forId}"]`)
}

const tableCells = (wrapper) =>
  wrapper.findAll('tbody tr').map((row) => row.findAll('td').map((cell) => cell.text()))

describe('FloorView', () => {
  beforeEach(() => {
    vi.resetAllMocks()
    vi.useRealTimers()
    toast.clear()
    floorsService.list.mockResolvedValue(listResponse())
    floorsService.getFloors.mockImplementation((params) => floorsService.list(params))
    buildingsService.getBuildings.mockImplementation((params) => buildingsService.list(params))
    buildingsService.list.mockResolvedValue({ data: mockBuildings })
  })

  afterEach(() => {
    vi.useRealTimers()
    document.body.innerHTML = ''
  })

  it('menampilkan header, breadcrumbs, tombol tambah, dan 4 kolom tabel tanpa deskripsi', async () => {
    const wrapper = await mountView()

    expect(wrapper.get('h1').text()).toBe('Data Lantai')
    expect(wrapper.text()).toContain(
      'Kelola data lantai berdasarkan gedung untuk mendukung pengaturan ruangan dan tempat tidur rumah sakit.',
    )

    const breadcrumbItems = wrapper.get('nav[aria-label="Breadcrumb"]').findAll('li')
    expect(breadcrumbItems.map((li) => li.text().replace('/', '').trim())).toEqual([
      'Dashboard',
      'Master Data',
      'Data Bed',
      'Data Lantai',
    ])

    expect(findButtonByText(wrapper, 'Tambah Lantai')).toBeDefined()

    const headers = wrapper.findAll('thead th').map((th) => th.text())
    expect(headers).toEqual(['No', 'Nama Gedung', 'Nama Lantai', 'Aksi'])
    expect(headers).not.toContain('Deskripsi')

    const [first, second] = tableCells(wrapper)
    expect(first.slice(0, 3)).toEqual(['1', 'Gedung Utama', 'Lantai 1'])
    expect(second.slice(0, 3)).toEqual(['2', 'Gedung Paviliun', 'Lantai 2'])
  })

  it('menampilkan loading state dan empty state (data kosong serta hasil pencarian kosong)', async () => {
    let resolveList
    floorsService.list.mockReturnValueOnce(new Promise((resolve) => (resolveList = resolve)))

    const wrapper = mount(FloorView, { global: { stubs: { Teleport: true } } })
    await flushPromises()
    expect(wrapper.get('tbody').attributes('aria-busy')).toBe('true')

    resolveList(listResponse([]))
    await flushPromises()
    expect(wrapper.get('tbody').attributes('aria-busy')).toBeUndefined()
    expect(wrapper.text()).toContain('Belum ada data lantai')

    vi.useFakeTimers()
    floorsService.list.mockResolvedValue(listResponse([]))
    await wrapper.get('input[aria-label="Cari lantai"]').setValue('Basemen')
    await nextTick()
    vi.advanceTimersByTime(400)
    await flushPromises()

    expect(wrapper.text()).toContain('Lantai tidak ditemukan')
    expect(wrapper.text()).toContain('Tidak ada lantai yang cocok dengan “Basemen”.')
  })

  it('mengirim pencarian API debounced dan mendukung muat ulang', async () => {
    vi.useFakeTimers()
    const wrapper = await mountView()

    await wrapper.get('input[aria-label="Cari lantai"]').setValue('Poliklinik')
    await nextTick()
    vi.advanceTimersByTime(400)
    await flushPromises()

    expect(floorsService.list).toHaveBeenLastCalledWith(
      expect.objectContaining({
        page: 1,
        search: 'Poliklinik',
      }),
    )

    await wrapper.get('button[aria-label="Muat ulang data"]').trigger('click')
    await flushPromises()
    expect(floorsService.list).toHaveBeenCalledTimes(3)
  })

  it('mengabaikan respons pencarian lama (race condition)', async () => {
    vi.useFakeTimers()
    const wrapper = await mountView()

    let resolveSlow
    let resolveFast
    floorsService.list
      .mockImplementationOnce(() => new Promise((resolve) => (resolveSlow = resolve)))
      .mockImplementationOnce(() => new Promise((resolve) => (resolveFast = resolve)))

    const searchInput = wrapper.get('input[aria-label="Cari lantai"]')
    await searchInput.setValue('Lama')
    await nextTick()
    vi.advanceTimersByTime(400)
    await nextTick()

    await searchInput.setValue('Baru')
    await nextTick()
    vi.advanceTimersByTime(400)
    await nextTick()

    resolveFast(
      listResponse([{ id: 9, building_id: 1, building: { id: 1, name: 'Gedung Utama' }, name: 'Lantai Terbaru' }]),
    )
    await flushPromises()

    resolveSlow(
      listResponse([{ id: 8, building_id: 1, building: { id: 1, name: 'Gedung Utama' }, name: 'Lantai Usang' }]),
    )
    await flushPromises()

    const rows = tableCells(wrapper)
    expect(rows).toHaveLength(1)
    expect(rows[0][2]).toBe('Lantai Terbaru')
  })

  it('mendukung pagination lintas halaman dan mundur halaman saat item terakhir dihapus', async () => {
    floorsService.list
      .mockResolvedValueOnce(
        listResponse(
          [mockFloors[0]],
          { current_page: 1, last_page: 2, per_page: 10, total: 11, from: 1, to: 10 },
        ),
      )
      .mockResolvedValueOnce(
        listResponse(
          [{ id: 11, building_id: 1, building: { id: 1, name: 'Gedung A' }, name: 'Lantai 11' }],
          { current_page: 2, last_page: 2, per_page: 10, total: 11, from: 11, to: 11 },
        ),
      )
      .mockResolvedValueOnce(
        listResponse(
          [mockFloors[0]],
          { current_page: 1, last_page: 1, per_page: 10, total: 10, from: 1, to: 10 },
        ),
      )
    floorsService.remove.mockResolvedValue({})

    const wrapper = await mountView()
    await wrapper.get('button[aria-label="Halaman berikutnya"]').trigger('click')
    await flushPromises()

    expect(floorsService.list).toHaveBeenLastCalledWith(expect.objectContaining({ page: 2 }))
    expect(tableCells(wrapper)[0][0]).toBe('11')

    await wrapper.get('button[aria-label="Hapus Lantai 11"]').trigger('click')
    await flushPromises()
    await findButtonByText(wrapper.get('[role="dialog"]'), 'Hapus').trigger('click')
    await flushPromises()

    expect(floorsService.list).toHaveBeenLastCalledWith(expect.objectContaining({ page: 1 }))
  })

  it('mendukung sorting melalui useFloors().setSort', async () => {
    const { setSort, sort, fetchItems } = useFloors()
    await fetchItems()

    expect(floorsService.list).toHaveBeenLastCalledWith(
      expect.objectContaining({ sort: 'created_at', direction: 'desc' }),
    )

    await setSort('name')
    expect(sort.column).toBe('name')
    expect(sort.direction).toBe('asc')
    expect(floorsService.list).toHaveBeenLastCalledWith(
      expect.objectContaining({ page: 1, sort: 'name', direction: 'asc' }),
    )
  })

  it('menampilkan error state aman bila daftar gagal dimuat tanpa detail teknis', async () => {
    floorsService.list.mockRejectedValueOnce({
      response: { status: 500, data: { message: 'SQLSTATE[HY000]: Connection refused' } },
    })

    const wrapper = await mountView()

    expect(wrapper.text()).toContain('Data lantai gagal dimuat')
    expect(wrapper.text()).not.toContain('SQLSTATE')

    await findButtonByText(wrapper, 'Coba lagi').trigger('click')
    await flushPromises()
    expect(wrapper.findAll('tbody tr')).toHaveLength(2)
  })

  it('memuat lookup gedung non-paginated, menangani warning kosong, dan coba lagi saat gagal muat gedung', async () => {
    buildingsService.getBuildings.mockRejectedValueOnce({ response: { status: 500, data: {} } })
    const wrapper = await mountView()

    await findButtonByText(wrapper, 'Tambah Lantai').trigger('click')
    await flushPromises()

    expect(buildingsService.getBuildings).toHaveBeenCalledWith({
      per_page: -1,
      sort: 'name',
      direction: 'asc',
    })
    expect(wrapper.get('[role="dialog"]').text()).toContain('Gagal memuat data gedung.')

    buildingsService.getBuildings.mockResolvedValueOnce({ data: [] })
    await findButtonByText(wrapper.get('[role="dialog"]'), 'Coba lagi').trigger('click')
    await flushPromises()
    expect(wrapper.get('[role="dialog"]').text()).toContain('Data Gedung belum tersedia.')

    buildingsService.getBuildings.mockResolvedValueOnce({ data: mockBuildings })
    await findButtonByText(wrapper.get('[role="dialog"]'), 'Batal').trigger('click')
    await findButtonByText(wrapper, 'Tambah Lantai').trigger('click')
    await flushPromises()

    const buildingSelect = findControlByLabel(wrapper.get('[role="dialog"]'), 'Nama Gedung')
    expect(buildingSelect.findAll('option').map((o) => o.text())).toEqual([
      'Pilih gedung',
      'Gedung Utama',
      'Gedung Paviliun',
    ])
  })

  it('menambah lantai baru, me-refresh daftar, dan menampilkan toast sukses', async () => {
    floorsService.create.mockResolvedValue({ data: { id: 3 } })
    const wrapper = await mountView()

    await findButtonByText(wrapper, 'Tambah Lantai').trigger('click')
    await flushPromises()

    const dialog = wrapper.get('[role="dialog"]')
    await findControlByLabel(dialog, 'Nama Gedung').setValue('1')
    await findControlByLabel(dialog, 'Nama Lantai').setValue('Lantai 3')
    await findControlByLabel(dialog, 'Deskripsi').setValue('Lantai Rawat Inap')
    await dialog.get('form').trigger('submit')
    await flushPromises()

    expect(floorsService.create).toHaveBeenCalledWith({
      building_id: 1,
      name: 'Lantai 3',
      description: 'Lantai Rawat Inap',
    })
    expect(floorsService.list).toHaveBeenCalledTimes(2)
    expect(toastMessages()).toContain('Data lantai berhasil ditambahkan.')
    expect(wrapper.findComponent(FloorFormModal).props('open')).toBe(false)
  })

  it('membuka form edit terisi data lantai dan menyimpan pembaruan', async () => {
    floorsService.update.mockResolvedValue({ data: { id: 1 } })
    const wrapper = await mountView()

    await wrapper.get('button[aria-label="Edit Lantai 1"]').trigger('click')
    await flushPromises()

    const dialog = wrapper.get('[role="dialog"]')
    expect(dialog.text()).toContain('Edit Lantai')
    expect(findControlByLabel(dialog, 'Nama Gedung').element.value).toBe('1')
    expect(findControlByLabel(dialog, 'Nama Lantai').element.value).toBe('Lantai 1')

    await findControlByLabel(dialog, 'Nama Lantai').setValue('Lantai 1 Revisi')
    await dialog.get('form').trigger('submit')
    await flushPromises()

    expect(floorsService.update).toHaveBeenCalledWith(1, {
      building_id: 1,
      name: 'Lantai 1 Revisi',
      description: 'Lantai Poliklinik',
    })
    expect(toastMessages()).toContain('Data lantai berhasil diperbarui.')
  })

  it('validasi 422 menjaga tabel tetap tampil dan mengirim isInvalid ke SearchableSelect serta field terkait', async () => {
    floorsService.create.mockRejectedValue({
      response: {
        status: 422,
        data: {
          message: 'The given data was invalid.',
          errors: {
            building_id: ['Gedung yang dipilih tidak valid atau sudah dihapus.'],
            name: ['Nama lantai sudah digunakan pada gedung yang dipilih.'],
          },
        },
      },
    })

    const wrapper = await mountView()
    await findButtonByText(wrapper, 'Tambah Lantai').trigger('click')
    await flushPromises()

    const dialog = wrapper.get('[role="dialog"]')
    await findControlByLabel(dialog, 'Nama Gedung').setValue('1')
    await findControlByLabel(dialog, 'Nama Lantai').setValue('Lantai Duplikat')
    await dialog.get('form').trigger('submit')
    await flushPromises()

    const modal = wrapper.findComponent(FloorFormModal)
    expect(modal.props('open')).toBe(true)
    const updatedDialog = wrapper.get('[role="dialog"]')
    expect(updatedDialog.text()).toContain('Gedung yang dipilih tidak valid atau sudah dihapus.')
    expect(updatedDialog.text()).toContain('Nama lantai sudah digunakan pada gedung yang dipilih.')
    expect(updatedDialog.findComponent(SearchableSelect).props('isInvalid')).toBe(true)
    expect(wrapper.findAll('tbody tr')).toHaveLength(2)
    expect(toastMessages()).toContain('The given data was invalid.')
    expect(toastMessages()).not.toContain('Data lantai berhasil ditambahkan.')
  })

  it('validasi sisi klien mencegah submit bila gedung atau nama lantai kosong', async () => {
    const wrapper = await mountView()
    await findButtonByText(wrapper, 'Tambah Lantai').trigger('click')
    await flushPromises()

    await wrapper.get('[role="dialog"] form').trigger('submit')
    await flushPromises()

    const updatedDialog = wrapper.get('[role="dialog"]')
    expect(updatedDialog.text()).toContain('Gedung wajib dipilih.')
    expect(updatedDialog.text()).toContain('Nama lantai wajib diisi.')
    expect(updatedDialog.findComponent(SearchableSelect).props('isInvalid')).toBe(true)
    expect(floorsService.create).not.toHaveBeenCalled()
  })

  it('mencegah double submit selama proses simpan berlangsung', async () => {
    let finishCreate
    floorsService.create.mockReturnValue(new Promise((resolve) => (finishCreate = resolve)))

    const wrapper = await mountView()
    await findButtonByText(wrapper, 'Tambah Lantai').trigger('click')
    await flushPromises()

    const dialog = wrapper.get('[role="dialog"]')
    await findControlByLabel(dialog, 'Nama Gedung').setValue('1')
    await findControlByLabel(dialog, 'Nama Lantai').setValue('Lantai Baru')
    await dialog.get('form').trigger('submit')
    await dialog.get('form').trigger('submit')
    await flushPromises()

    expect(floorsService.create).toHaveBeenCalledTimes(1)
    expect(findButtonByText(wrapper.get('[role="dialog"]'), 'Simpan').attributes('disabled')).toBeDefined()

    finishCreate({ data: { id: 5 } })
    await flushPromises()
    expect(toastMessages()).toContain('Data lantai berhasil ditambahkan.')
  })

  it('menghapus lantai melalui BaseConfirmDialog dan mencegah double delete', async () => {
    let finishDelete
    floorsService.remove.mockReturnValue(new Promise((resolve) => (finishDelete = resolve)))
    const wrapper = await mountView()

    await wrapper.get('button[aria-label="Hapus Lantai 1"]').trigger('click')
    await flushPromises()

    const confirmDialog = wrapper.findComponent(BaseConfirmDialog)
    expect(confirmDialog.props('open')).toBe(true)
    expect(confirmDialog.props('message')).toBe(
      'Apakah Anda yakin ingin menghapus lantai “Lantai 1” dari “Gedung Utama”?',
    )

    await findButtonByText(wrapper.get('[role="dialog"]'), 'Hapus').trigger('click')
    await flushPromises()

    expect(confirmDialog.props('loading')).toBe(true)
    expect(findButtonByText(wrapper.get('[role="dialog"]'), 'Hapus').attributes('disabled')).toBeDefined()
    expect(floorsService.remove).toHaveBeenCalledTimes(1)
    expect(floorsService.remove).toHaveBeenCalledWith(1)

    finishDelete({})
    await flushPromises()
    expect(toastMessages()).toContain('Data lantai berhasil dihapus.')
  })

  it('menampilkan toast error saat hapus lantai gagal tanpa menghilangkan tabel', async () => {
    floorsService.remove.mockRejectedValue({
      response: {
        status: 422,
        data: { message: 'Lantai tidak dapat dihapus karena masih digunakan oleh data bangsal.' },
      },
    })

    const wrapper = await mountView()
    await wrapper.get('button[aria-label="Hapus Lantai 1"]').trigger('click')
    await flushPromises()

    await findButtonByText(wrapper.get('[role="dialog"]'), 'Hapus').trigger('click')
    await flushPromises()

    expect(toastMessages()).toContain(
      'Lantai tidak dapat dihapus karena masih digunakan oleh data bangsal.',
    )
    expect(wrapper.findAll('tbody tr')).toHaveLength(2)
  })

  it('mempertahankan kompatibilitas alias buildingsService.getBuildings dan floorsService.getFloors', async () => {
    await buildingsService.getBuildings({ per_page: -1 })
    expect(buildingsService.list).toHaveBeenCalledWith({ per_page: -1 })

    await floorsService.getFloors({ per_page: -1 })
    expect(floorsService.list).toHaveBeenCalledWith({ per_page: -1 })
  })
})
