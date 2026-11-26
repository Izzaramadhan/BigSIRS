import { nextTick } from 'vue'
import { flushPromises, mount } from '@vue/test-utils'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import WardView from '../bed/WardView.vue'
import SearchableSelect from '@/components/common/SearchableSelect.vue'
import WardFormModal from '@/components/master-data/wards/WardFormModal.vue'
import BaseConfirmDialog from '@/components/ui/BaseConfirmDialog.vue'
import { useToast } from '@/composables/core/useToast'
import { useWards } from '@/composables/useWards'
import { floorsService } from '@/services/master-data/floors.service'
import { wardsService } from '@/services/master-data/wards.service'

vi.mock('@/services/master-data/wards.service', async (importOriginal) => {
  const actual = await importOriginal()
  return {
    wardsService: {
      ...actual.wardsService,
      list: vi.fn(),
      get: vi.fn(),
      create: vi.fn(),
      update: vi.fn(),
      remove: vi.fn(),
      getWards: vi.fn(),
    },
  }
})

vi.mock('@/services/master-data/floors.service', async (importOriginal) => {
  const actual = await importOriginal()
  return {
    floorsService: {
      ...actual.floorsService,
      list: vi.fn(),
      getFloors: vi.fn(),
    },
  }
})

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

const mockWards = [
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
]

const listResponse = (data = mockWards, metaOverrides = {}) => ({
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
  const wrapper = mount(WardView, {
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

describe('WardView', () => {
  beforeEach(() => {
    vi.resetAllMocks()
    vi.useRealTimers()
    toast.clear()
    wardsService.list.mockResolvedValue(listResponse())
    wardsService.getWards.mockImplementation((params) => wardsService.list(params))
    floorsService.getFloors.mockImplementation((params) => floorsService.list(params))
    floorsService.list.mockResolvedValue({ data: mockFloors })
  })

  afterEach(() => {
    vi.useRealTimers()
    document.body.innerHTML = ''
  })

  it('menampilkan header, breadcrumbs, tombol tambah, dan 4 kolom dengan relasi Lantai - Gedung', async () => {
    const wrapper = await mountView()

    expect(wrapper.get('h1').text()).toBe('Data Bangsal')
    expect(wrapper.text()).toContain(
      'Kelola data bangsal berdasarkan lantai dan gedung untuk mendukung pengaturan ruangan serta tempat tidur rumah sakit.',
    )

    const breadcrumbItems = wrapper.get('nav[aria-label="Breadcrumb"]').findAll('li')
    expect(breadcrumbItems.map((li) => li.text().replace('/', '').trim())).toEqual([
      'Dashboard',
      'Master Data',
      'Data Bed',
      'Data Bangsal',
    ])

    expect(findButtonByText(wrapper, 'Tambah Bangsal')).toBeDefined()

    const headers = wrapper.findAll('thead th').map((th) => th.text())
    expect(headers).toEqual(['No', 'Nama Lantai', 'Nama Bangsal', 'Aksi'])
    expect(headers).not.toContain('Deskripsi')
    expect(headers).not.toContain('Status')

    const [first, second] = tableCells(wrapper)
    expect(first.slice(0, 3)).toEqual(['1', 'Lantai 4 - RUANG ARJUNA', 'Bangsal Arjuna A'])
    expect(second.slice(0, 3)).toEqual(['2', 'Lantai 1 - Gedung Utama', 'Bangsal Melati'])
  })

  it('menampilkan loading state dan empty state (data kosong maupun hasil pencarian kosong)', async () => {
    let resolveList
    wardsService.list.mockReturnValueOnce(new Promise((resolve) => (resolveList = resolve)))

    const wrapper = mount(WardView, { global: { stubs: { Teleport: true } } })
    await flushPromises()
    expect(wrapper.get('tbody').attributes('aria-busy')).toBe('true')

    resolveList(listResponse([]))
    await flushPromises()
    expect(wrapper.get('tbody').attributes('aria-busy')).toBeUndefined()
    expect(wrapper.text()).toContain('Belum ada data bangsal')

    vi.useFakeTimers()
    wardsService.list.mockResolvedValue(listResponse([]))
    await wrapper.get('input[aria-label="Cari bangsal"]').setValue('Mawar')
    await nextTick()
    vi.advanceTimersByTime(400)
    await flushPromises()

    expect(wrapper.text()).toContain('Bangsal tidak ditemukan')
    expect(wrapper.text()).toContain('Tidak ada bangsal yang cocok dengan “Mawar”.')
  })

  it('mengirim pencarian API debounced dan mendukung tombol muat ulang', async () => {
    vi.useFakeTimers()
    const wrapper = await mountView()

    await wrapper.get('input[aria-label="Cari bangsal"]').setValue('Arjuna')
    await nextTick()
    vi.advanceTimersByTime(400)
    await flushPromises()

    expect(wardsService.list).toHaveBeenLastCalledWith(
      expect.objectContaining({
        page: 1,
        search: 'Arjuna',
      }),
    )

    await wrapper.get('button[aria-label="Muat ulang data"]').trigger('click')
    await flushPromises()
    expect(wardsService.list).toHaveBeenCalledTimes(3)
  })

  it('mengabaikan respons pencarian lama (race condition)', async () => {
    vi.useFakeTimers()
    const wrapper = await mountView()

    let resolveSlow
    let resolveFast
    wardsService.list
      .mockImplementationOnce(() => new Promise((resolve) => (resolveSlow = resolve)))
      .mockImplementationOnce(() => new Promise((resolve) => (resolveFast = resolve)))

    const searchInput = wrapper.get('input[aria-label="Cari bangsal"]')
    await searchInput.setValue('Lama')
    await nextTick()
    vi.advanceTimersByTime(400)
    await nextTick()

    await searchInput.setValue('Baru')
    await nextTick()
    vi.advanceTimersByTime(400)
    await nextTick()

    resolveFast(
      listResponse([
        {
          id: 9,
          floor_id: 1,
          floor_display_name: 'Lantai 1 - Gedung Utama',
          name: 'Bangsal Terbaru',
        },
      ]),
    )
    await flushPromises()

    resolveSlow(
      listResponse([
        {
          id: 8,
          floor_id: 1,
          floor_display_name: 'Lantai 1 - Gedung Utama',
          name: 'Bangsal Usang',
        },
      ]),
    )
    await flushPromises()

    const rows = tableCells(wrapper)
    expect(rows).toHaveLength(1)
    expect(rows[0][2]).toBe('Bangsal Terbaru')
  })

  it('mendukung pagination lintas halaman dan mundur halaman saat item terakhir dihapus', async () => {
    wardsService.list
      .mockResolvedValueOnce(
        listResponse(
          [mockWards[0]],
          { current_page: 1, last_page: 2, per_page: 10, total: 11, from: 1, to: 10 },
        ),
      )
      .mockResolvedValueOnce(
        listResponse(
          [
            {
              id: 11,
              floor_id: 1,
              floor_display_name: 'Lantai 1 - Gedung Utama',
              name: 'Bangsal 11',
            },
          ],
          { current_page: 2, last_page: 2, per_page: 10, total: 11, from: 11, to: 11 },
        ),
      )
      .mockResolvedValueOnce(
        listResponse(
          [mockWards[0]],
          { current_page: 1, last_page: 1, per_page: 10, total: 10, from: 1, to: 10 },
        ),
      )
    wardsService.remove.mockResolvedValue({})

    const wrapper = await mountView()
    await wrapper.get('button[aria-label="Halaman berikutnya"]').trigger('click')
    await flushPromises()

    expect(wardsService.list).toHaveBeenLastCalledWith(expect.objectContaining({ page: 2 }))
    expect(tableCells(wrapper)[0][0]).toBe('11')

    await wrapper.get('button[aria-label="Hapus Bangsal 11"]').trigger('click')
    await flushPromises()
    await findButtonByText(wrapper.get('[role="dialog"]'), 'Hapus').trigger('click')
    await flushPromises()

    expect(wardsService.list).toHaveBeenLastCalledWith(expect.objectContaining({ page: 1 }))
  })

  it('mendukung sorting API melalui useWards().setSort', async () => {
    const { setSort, sort, fetchItems } = useWards()
    await fetchItems()

    expect(wardsService.list).toHaveBeenLastCalledWith(
      expect.objectContaining({ sort: 'created_at', direction: 'desc' }),
    )

    await setSort('name')
    expect(sort.column).toBe('name')
    expect(sort.direction).toBe('asc')
    expect(wardsService.list).toHaveBeenLastCalledWith(
      expect.objectContaining({ page: 1, sort: 'name', direction: 'asc' }),
    )
  })

  it('menampilkan error state aman bila daftar gagal dimuat tanpa informasi teknis', async () => {
    wardsService.list.mockRejectedValueOnce({
      response: { status: 500, data: { message: 'SQLSTATE[HY000]: Connection refused' } },
    })

    const wrapper = await mountView()

    expect(wrapper.text()).toContain('Data bangsal gagal dimuat')
    expect(wrapper.text()).not.toContain('SQLSTATE')

    await findButtonByText(wrapper, 'Coba lagi').trigger('click')
    await flushPromises()
    expect(wrapper.findAll('tbody tr')).toHaveLength(2)
  })

  it('memuat lookup lantai non-paginated dengan label Lantai - Gedung serta mendukung retry dan warning kosong', async () => {
    floorsService.getFloors.mockRejectedValueOnce({
      response: { status: 500, data: { message: 'PDOException in query' } },
    })
    const wrapper = await mountView()

    await findButtonByText(wrapper, 'Tambah Bangsal').trigger('click')
    await flushPromises()

    expect(floorsService.getFloors).toHaveBeenCalledWith({
      per_page: -1,
      sort: 'name',
      direction: 'asc',
    })
    const dialogAfterErr = wrapper.get('[role="dialog"]')
    expect(dialogAfterErr.text()).toContain('Gagal memuat data lantai.')
    expect(dialogAfterErr.text()).not.toContain('PDOException')

    floorsService.getFloors.mockResolvedValueOnce({ data: [] })
    await findButtonByText(dialogAfterErr, 'Coba lagi').trigger('click')
    await flushPromises()
    const dialogEmpty = wrapper.get('[role="dialog"]')
    expect(dialogEmpty.text()).toContain('Data Lantai belum tersedia.')
    expect(findButtonByText(dialogEmpty, 'Simpan').attributes('disabled')).toBeDefined()

    floorsService.getFloors.mockResolvedValueOnce({ data: mockFloors })
    await findButtonByText(dialogEmpty, 'Batal').trigger('click')
    await findButtonByText(wrapper, 'Tambah Bangsal').trigger('click')
    await flushPromises()

    const floorSelect = findControlByLabel(wrapper.get('[role="dialog"]'), 'Nama Lantai')
    expect(floorSelect.findAll('option').map((o) => o.text())).toEqual([
      'Pilih lantai',
      'Lantai 1 - Gedung Utama',
      'Lantai 4 - RUANG ARJUNA',
    ])
  })

  it('menambah bangsal baru, me-refresh daftar, dan menampilkan toast sukses', async () => {
    wardsService.create.mockResolvedValue({ data: { id: 3 } })
    const wrapper = await mountView()

    await findButtonByText(wrapper, 'Tambah Bangsal').trigger('click')
    await flushPromises()

    const dialog = wrapper.get('[role="dialog"]')
    await findControlByLabel(dialog, 'Nama Lantai').setValue('4')
    await findControlByLabel(dialog, 'Nama Bangsal').setValue('Bangsal Arjuna B')
    await findControlByLabel(dialog, 'Deskripsi').setValue('Perawatan Anak')
    await dialog.get('form').trigger('submit')
    await flushPromises()

    expect(wardsService.create).toHaveBeenCalledWith({
      floor_id: 4,
      name: 'Bangsal Arjuna B',
      description: 'Perawatan Anak',
    })
    expect(wardsService.list).toHaveBeenCalledTimes(2)
    expect(toastMessages()).toContain('Data bangsal berhasil ditambahkan.')
    expect(wrapper.findComponent(WardFormModal).props('open')).toBe(false)
  })

  it('membuka form edit berisi data lama dan menyimpan perubahan', async () => {
    wardsService.update.mockResolvedValue({ data: { id: 1 } })
    const wrapper = await mountView()

    await wrapper.get('button[aria-label="Edit Bangsal Arjuna A"]').trigger('click')
    await flushPromises()

    const dialog = wrapper.get('[role="dialog"]')
    expect(dialog.text()).toContain('Edit Bangsal')
    expect(findControlByLabel(dialog, 'Nama Lantai').element.value).toBe('4')
    expect(findControlByLabel(dialog, 'Nama Bangsal').element.value).toBe('Bangsal Arjuna A')
    expect(findControlByLabel(dialog, 'Deskripsi').element.value).toBe('Bangsal perawatan pasien')

    await findControlByLabel(dialog, 'Nama Bangsal').setValue('Bangsal Arjuna A Revisi')
    await dialog.get('form').trigger('submit')
    await flushPromises()

    expect(wardsService.update).toHaveBeenCalledWith(1, {
      floor_id: 4,
      name: 'Bangsal Arjuna A Revisi',
      description: 'Bangsal perawatan pasien',
    })
    expect(toastMessages()).toContain('Data bangsal berhasil diperbarui.')
  })

  it('validasi 422 menjaga tabel tetap tampil dan mengirim isInvalid ke SearchableSelect serta pesan field', async () => {
    wardsService.create.mockRejectedValue({
      response: {
        status: 422,
        data: {
          message: 'The given data was invalid.',
          errors: {
            floor_id: ['Lantai yang dipilih harus memiliki relasi Gedung yang valid.'],
            name: ['Nama bangsal sudah digunakan pada lantai yang dipilih.'],
          },
        },
      },
    })

    const wrapper = await mountView()
    await findButtonByText(wrapper, 'Tambah Bangsal').trigger('click')
    await flushPromises()

    const dialog = wrapper.get('[role="dialog"]')
    await findControlByLabel(dialog, 'Nama Lantai').setValue('4')
    await findControlByLabel(dialog, 'Nama Bangsal').setValue('Bangsal Duplikat')
    await dialog.get('form').trigger('submit')
    await flushPromises()

    const modal = wrapper.findComponent(WardFormModal)
    expect(modal.props('open')).toBe(true)
    const updatedDialog = wrapper.get('[role="dialog"]')
    expect(updatedDialog.text()).toContain(
      'Lantai yang dipilih harus memiliki relasi Gedung yang valid.',
    )
    expect(updatedDialog.text()).toContain('Nama bangsal sudah digunakan pada lantai yang dipilih.')
    expect(updatedDialog.findComponent(SearchableSelect).props('isInvalid')).toBe(true)
    expect(wrapper.findAll('tbody tr')).toHaveLength(2)
    expect(toastMessages()).toContain('The given data was invalid.')
    expect(toastMessages()).not.toContain('Data bangsal berhasil ditambahkan.')
  })

  it('validasi sisi klien mewajibkan lantai serta nama bangsal dan menandai dropdown invalid', async () => {
    const wrapper = await mountView()
    await findButtonByText(wrapper, 'Tambah Bangsal').trigger('click')
    await flushPromises()

    await wrapper.get('[role="dialog"] form').trigger('submit')
    await flushPromises()

    const updatedDialog = wrapper.get('[role="dialog"]')
    expect(updatedDialog.text()).toContain('Lantai wajib dipilih.')
    expect(updatedDialog.text()).toContain('Nama bangsal wajib diisi.')
    expect(updatedDialog.findComponent(SearchableSelect).props('isInvalid')).toBe(true)
    expect(wardsService.create).not.toHaveBeenCalled()
  })

  it('mencegah double submit selama proses simpan berlangsung', async () => {
    let finishCreate
    wardsService.create.mockReturnValue(new Promise((resolve) => (finishCreate = resolve)))

    const wrapper = await mountView()
    await findButtonByText(wrapper, 'Tambah Bangsal').trigger('click')
    await flushPromises()

    const dialog = wrapper.get('[role="dialog"]')
    await findControlByLabel(dialog, 'Nama Lantai').setValue('4')
    await findControlByLabel(dialog, 'Nama Bangsal').setValue('Bangsal Baru')
    await dialog.get('form').trigger('submit')
    await dialog.get('form').trigger('submit')
    await flushPromises()

    expect(wardsService.create).toHaveBeenCalledTimes(1)
    expect(findButtonByText(wrapper.get('[role="dialog"]'), 'Simpan').attributes('disabled')).toBeDefined()

    finishCreate({ data: { id: 5 } })
    await flushPromises()
    expect(toastMessages()).toContain('Data bangsal berhasil ditambahkan.')
  })

  it('menghapus bangsal melalui BaseConfirmDialog dan mencegah double delete', async () => {
    let finishDelete
    wardsService.remove.mockReturnValue(new Promise((resolve) => (finishDelete = resolve)))
    const wrapper = await mountView()

    await wrapper.get('button[aria-label="Hapus Bangsal Arjuna A"]').trigger('click')
    await flushPromises()

    const confirmDialog = wrapper.findComponent(BaseConfirmDialog)
    expect(confirmDialog.props('open')).toBe(true)
    expect(confirmDialog.props('message')).toBe(
      'Apakah Anda yakin ingin menghapus bangsal “Bangsal Arjuna A” dari “Lantai 4 - RUANG ARJUNA”?',
    )

    await findButtonByText(wrapper.get('[role="dialog"]'), 'Hapus').trigger('click')
    await flushPromises()

    expect(confirmDialog.props('loading')).toBe(true)
    expect(findButtonByText(wrapper.get('[role="dialog"]'), 'Hapus').attributes('disabled')).toBeDefined()
    expect(wardsService.remove).toHaveBeenCalledTimes(1)
    expect(wardsService.remove).toHaveBeenCalledWith(1)

    finishDelete({})
    await flushPromises()
    expect(toastMessages()).toContain('Data bangsal berhasil dihapus.')
  })

  it('menampilkan toast error saat hapus gagal tanpa menghilangkan tabel', async () => {
    wardsService.remove.mockRejectedValue({
      response: {
        status: 422,
        data: { message: 'Bangsal tidak dapat dihapus karena masih digunakan oleh data kamar.' },
      },
    })

    const wrapper = await mountView()
    await wrapper.get('button[aria-label="Hapus Bangsal Arjuna A"]').trigger('click')
    await flushPromises()

    await findButtonByText(wrapper.get('[role="dialog"]'), 'Hapus').trigger('click')
    await flushPromises()

    expect(toastMessages()).toContain(
      'Bangsal tidak dapat dihapus karena masih digunakan oleh data kamar.',
    )
    expect(wrapper.findAll('tbody tr')).toHaveLength(2)
  })

  it('mempertahankan kompatibilitas alias floorsService.getFloors dan wardsService.getWards', async () => {
    await floorsService.getFloors({ per_page: -1 })
    expect(floorsService.list).toHaveBeenCalledWith({ per_page: -1 })

    await wardsService.getWards({ per_page: -1 })
    expect(wardsService.list).toHaveBeenCalledWith({ per_page: -1 })
  })
})
