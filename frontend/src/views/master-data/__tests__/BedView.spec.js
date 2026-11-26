import { nextTick } from 'vue'
import { flushPromises, mount } from '@vue/test-utils'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import BedView from '../bed/BedView.vue'
import SearchableSelect from '@/components/common/SearchableSelect.vue'
import BedFormModal from '@/components/master-data/beds/BedFormModal.vue'
import BaseConfirmDialog from '@/components/ui/BaseConfirmDialog.vue'
import { useToast } from '@/composables/core/useToast'
import { bedsService } from '@/services/master-data/beds.service'
import { roomsService } from '@/services/master-data/rooms.service'

vi.mock('@/services/master-data/beds.service', async (importOriginal) => {
  const actual = await importOriginal()
  return {
    bedsService: {
      ...actual.bedsService,
      list: vi.fn(),
      get: vi.fn(),
      create: vi.fn(),
      update: vi.fn(),
      remove: vi.fn(),
      getBeds: vi.fn(),
    },
  }
})

vi.mock('@/services/master-data/rooms.service', () => ({
  roomsService: {
    getRooms: vi.fn(),
    getRoom: vi.fn(),
  },
}))

const rooms = [
  {
    id: 1,
    name: 'Kamar 1',
    ward: { id: 3, name: 'Bangsal Maternal Perinatal' },
    room_class: { id: 3, name: 'Kelas 3' },
    display_name: 'Kamar 1 - Bangsal Maternal Perinatal - Kelas 3',
  },
  {
    id: 2,
    name: 'Kamar 2',
    ward: { id: 1, name: 'Bangsal Melati' },
    room_class: null,
  },
]

const beds = [
  {
    id: 1,
    room_id: 1,
    name: 'Bed 1',
    description: 'Bed dekat jendela',
    room: { id: 1, name: 'Kamar 1', room_class: { id: 3, name: 'Kelas 3' } },
    room_table_display_name: 'Kamar 1 - Bangsal Maternal Perinatal',
  },
  {
    id: 2,
    room_id: 2,
    name: 'Bed 2',
    description: null,
    room: { id: 2, name: 'Kamar 2', ward: { id: 1, name: 'Bangsal Melati' }, room_class: null },
  },
]

const listResponse = (data = beds, metaOverrides = {}) => ({
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
  const wrapper = mount(BedView, {
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

describe('BedView', () => {
  beforeEach(() => {
    vi.resetAllMocks()
    vi.useRealTimers()
    toast.clear()
    bedsService.list.mockResolvedValue(listResponse())
    bedsService.getBeds.mockImplementation((params) => bedsService.list(params))
    roomsService.getRooms.mockResolvedValue({ data: rooms })
  })

  afterEach(() => {
    vi.useRealTimers()
    document.body.innerHTML = ''
  })

  it('menampilkan judul, deskripsi halaman, breadcrumbs, dan 6 kolom tabel sesuai urutan', async () => {
    const wrapper = await mountView()

    expect(wrapper.get('h1').text()).toBe('Data Bed')
    expect(wrapper.text()).toContain(
      'Kelola data tempat tidur berdasarkan kamar, bangsal, dan kelas perawatan rumah sakit.',
    )

    const breadcrumbItems = wrapper.get('nav[aria-label="Breadcrumb"]').findAll('li')
    expect(breadcrumbItems.map((li) => li.text().replace('/', '').trim())).toEqual([
      'Dashboard',
      'Master Data',
      'Data Bed',
    ])

    expect(wrapper.findAll('thead th').map((th) => th.text())).toEqual([
      'No',
      'Nama Kamar',
      'Nama Kelas',
      'Nama Bed',
      'Deskripsi',
      'Aksi',
    ])

    const [first, second] = tableCells(wrapper)
    expect(first.slice(0, 5)).toEqual([
      '1',
      'Kamar 1 - Bangsal Maternal Perinatal',
      'Kelas 3',
      'Bed 1',
      'Bed dekat jendela',
    ])
    expect(second.slice(0, 5)).toEqual(['2', 'Kamar 2 - Bangsal Melati', '—', 'Bed 2', '—'])
  })

  it('menampilkan loading state dan empty state (data kosong maupun hasil pencarian kosong)', async () => {
    let resolveList
    bedsService.list.mockReturnValueOnce(new Promise((resolve) => (resolveList = resolve)))

    const pendingWrapper = mount(BedView, { global: { stubs: { Teleport: true } } })
    await flushPromises()
    expect(pendingWrapper.get('tbody').attributes('aria-busy')).toBe('true')

    resolveList(listResponse([]))
    await flushPromises()
    expect(pendingWrapper.get('tbody').attributes('aria-busy')).toBeUndefined()
    expect(pendingWrapper.text()).toContain('Belum ada data bed')

    vi.useFakeTimers()
    bedsService.list.mockResolvedValue(listResponse([]))
    const searchInput = pendingWrapper.get('input[aria-label="Cari bed"]')
    await searchInput.setValue('VIP Khusus')
    await nextTick()
    vi.advanceTimersByTime(400)
    await flushPromises()

    expect(pendingWrapper.text()).toContain('Bed tidak ditemukan')
    expect(pendingWrapper.text()).toContain('Tidak ada bed yang cocok dengan “VIP Khusus”.')
  })

  it('mengirim pencarian ke API dan mendukung tombol muat ulang', async () => {
    vi.useFakeTimers()
    const wrapper = await mountView()

    const searchInput = wrapper.get('input[aria-label="Cari bed"]')
    await searchInput.setValue('Melati')
    await nextTick()
    vi.advanceTimersByTime(400)
    await flushPromises()

    expect(bedsService.list).toHaveBeenLastCalledWith(
      expect.objectContaining({
        page: 1,
        search: 'Melati',
      }),
    )

    await wrapper.get('button[aria-label="Muat ulang data"]').trigger('click')
    await flushPromises()
    expect(bedsService.list).toHaveBeenCalledTimes(3)
  })

  // Regresi Bug P0 #4: Race condition pencarian
  it('mengabaikan respons pencarian lama (race condition)', async () => {
    vi.useFakeTimers()
    const wrapper = await mountView()

    let resolveSlow
    let resolveFast
    bedsService.list
      .mockImplementationOnce(() => new Promise((resolve) => (resolveSlow = resolve)))
      .mockImplementationOnce(() => new Promise((resolve) => (resolveFast = resolve)))

    const searchInput = wrapper.get('input[aria-label="Cari bed"]')
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
          room_id: 1,
          name: 'Bed Terbaru',
          description: null,
          room_table_display_name: 'Kamar 1 - Bangsal Maternal Perinatal',
        },
      ]),
    )
    await flushPromises()

    resolveSlow(
      listResponse([
        {
          id: 8,
          room_id: 1,
          name: 'Bed Usang',
          description: null,
          room_table_display_name: 'Kamar 1 - Bangsal Maternal Perinatal',
        },
      ]),
    )
    await flushPromises()

    const rows = tableCells(wrapper)
    expect(rows).toHaveLength(1)
    expect(rows[0][3]).toBe('Bed Terbaru')
  })

  it('mendukung pagination lintas halaman sesuai data API', async () => {
    bedsService.list
      .mockResolvedValueOnce(
        listResponse(
          [beds[0]],
          { current_page: 1, last_page: 2, per_page: 10, total: 12, from: 1, to: 10 },
        ),
      )
      .mockResolvedValueOnce(
        listResponse(
          [{ ...beds[1], id: 11, name: 'Bed 11' }],
          { current_page: 2, last_page: 2, per_page: 10, total: 12, from: 11, to: 12 },
        ),
      )

    const wrapper = await mountView()
    await wrapper.get('button[aria-label="Halaman berikutnya"]').trigger('click')
    await flushPromises()

    expect(bedsService.list).toHaveBeenLastCalledWith(expect.objectContaining({ page: 2 }))
    const rows = tableCells(wrapper)
    expect(rows[0][0]).toBe('11')
    expect(rows[0][3]).toBe('Bed 11')
  })

  it('menampilkan error state aman bila daftar gagal dimuat dan dapat mencoba lagi', async () => {
    bedsService.list.mockRejectedValueOnce({
      response: { status: 500, data: { message: 'SQLSTATE[HY000]: Connection refused' } },
    })
    const wrapper = await mountView()

    expect(wrapper.text()).toContain('Data bed gagal dimuat')
    expect(wrapper.text()).not.toContain('SQLSTATE')

    await findButtonByText(wrapper, 'Coba lagi').trigger('click')
    await flushPromises()

    expect(wrapper.findAll('tbody tr')).toHaveLength(2)
  })

  it('memuat lookup kamar non-paginated dengan format Kamar - Bangsal - Kelas dan menangani error muat kamar', async () => {
    roomsService.getRooms.mockRejectedValueOnce({ response: { status: 500, data: {} } })
    const wrapper = await mountView()

    await findButtonByText(wrapper, 'Tambah Bed').trigger('click')
    await flushPromises()

    expect(roomsService.getRooms).toHaveBeenCalledWith({
      per_page: -1,
      sort: 'name',
      direction: 'asc',
    })
    expect(wrapper.get('[role="dialog"]').text()).toContain('Gagal memuat data kamar.')

    roomsService.getRooms.mockResolvedValueOnce({ data: rooms })
    await findButtonByText(wrapper.get('[role="dialog"]'), 'Coba lagi').trigger('click')
    await flushPromises()

    const roomSelect = findControlByLabel(wrapper.get('[role="dialog"]'), 'Nama Kamar')
    const optionTexts = roomSelect.findAll('option').map((o) => o.text())
    expect(optionTexts).toContain('Kamar 1 - Bangsal Maternal Perinatal - Kelas 3')
    expect(optionTexts).toContain('Kamar 2 - Bangsal Melati - (Kelas Belum Ditentukan)')
  })

  it('membuat bed baru lalu me-refresh daftar dan menampilkan toast sukses', async () => {
    bedsService.create.mockResolvedValue({ data: { id: 3 } })
    const wrapper = await mountView()

    await findButtonByText(wrapper, 'Tambah Bed').trigger('click')
    await flushPromises()

    const dialog = wrapper.get('[role="dialog"]')
    await findControlByLabel(dialog, 'Nama Kamar').setValue('1')
    await findControlByLabel(dialog, 'Nama Bed').setValue('Bed 3')
    await findControlByLabel(dialog, 'Deskripsi').setValue('Dekat pintu')
    await dialog.get('form').trigger('submit')
    await flushPromises()

    expect(bedsService.create).toHaveBeenCalledWith({
      room_id: 1,
      name: 'Bed 3',
      description: 'Dekat pintu',
    })
    expect(bedsService.list).toHaveBeenCalledTimes(2)
    expect(toastMessages()).toContain('Data bed berhasil ditambahkan.')
    expect(wrapper.findComponent(BedFormModal).props('open')).toBe(false)
  })

  it('membuka form edit berisi data bed yang dipilih dan menyimpan perubahan', async () => {
    bedsService.update.mockResolvedValue({ data: { id: 1 } })
    const wrapper = await mountView()

    await wrapper.get('button[aria-label="Edit Bed 1"]').trigger('click')
    await flushPromises()

    const dialog = wrapper.get('[role="dialog"]')
    expect(dialog.text()).toContain('Edit Bed')
    expect(findControlByLabel(dialog, 'Nama Kamar').element.value).toBe('1')
    expect(findControlByLabel(dialog, 'Nama Bed').element.value).toBe('Bed 1')

    await findControlByLabel(dialog, 'Nama Bed').setValue('Bed 1 Revisi')
    await dialog.get('form').trigger('submit')
    await flushPromises()

    expect(bedsService.update).toHaveBeenCalledWith(1, {
      room_id: 1,
      name: 'Bed 1 Revisi',
      description: 'Bed dekat jendela',
    })
    expect(toastMessages()).toContain('Data bed berhasil diperbarui.')
  })

  // Regresi Bug P0 #1: Validasi 422 tidak boleh membuat tabel menghilang
  it('validasi server 422: tabel tetap tampil, error diteruskan ke field form, dan toast error muncul', async () => {
    bedsService.create.mockRejectedValue({
      response: {
        status: 422,
        data: {
          message: 'Data tidak valid.',
          errors: {
            room_id: ['Kamar yang dipilih harus memiliki relasi Kelas yang valid.'],
            name: ['Nama bed sudah digunakan pada kamar yang dipilih.'],
          },
        },
      },
    })
    const wrapper = await mountView()

    await findButtonByText(wrapper, 'Tambah Bed').trigger('click')
    await flushPromises()

    const dialog = wrapper.get('[role="dialog"]')
    await findControlByLabel(dialog, 'Nama Kamar').setValue('1')
    await findControlByLabel(dialog, 'Nama Bed').setValue('Bed 1')
    await dialog.get('form').trigger('submit')
    await flushPromises()

    const modal = wrapper.findComponent(BedFormModal)
    expect(modal.props('open')).toBe(true)
    expect(modal.props('serverErrors')).toEqual({
      room_id: 'Kamar yang dipilih harus memiliki relasi Kelas yang valid.',
      name: 'Nama bed sudah digunakan pada kamar yang dipilih.',
    })
    const updatedDialog = wrapper.get('[role="dialog"]')
    expect(updatedDialog.text()).toContain('Kamar yang dipilih harus memiliki relasi Kelas yang valid.')
    expect(updatedDialog.text()).toContain('Nama bed sudah digunakan pada kamar yang dipilih.')
    expect(updatedDialog.findComponent(SearchableSelect).props('isInvalid')).toBe(true)
    expect(wrapper.findAll('tbody tr')).toHaveLength(2)
    expect(toastMessages()).toContain('Data tidak valid.')
    expect(toastMessages()).not.toContain('Data bed berhasil ditambahkan.')
  })

  // Regresi Bug P0 #3: Validasi dropdown SearchableSelect wajib memakai prop isInvalid
  it('validasi sisi klien mengirim isInvalid ke SearchableSelect dan mencegah submit kosong', async () => {
    const wrapper = await mountView()

    await findButtonByText(wrapper, 'Tambah Bed').trigger('click')
    await flushPromises()

    await wrapper.get('[role="dialog"] form').trigger('submit')
    await flushPromises()

    const updatedDialog = wrapper.get('[role="dialog"]')
    expect(updatedDialog.text()).toContain('Nama kamar wajib dipilih.')
    expect(updatedDialog.text()).toContain('Nama bed wajib diisi.')
    expect(updatedDialog.findComponent(SearchableSelect).props('isInvalid')).toBe(true)
    expect(bedsService.create).not.toHaveBeenCalled()
  })

  it('mencegah double submit pada form selama request create/update berjalan', async () => {
    let finishCreate
    bedsService.create.mockReturnValue(new Promise((resolve) => (finishCreate = resolve)))

    const wrapper = await mountView()
    await findButtonByText(wrapper, 'Tambah Bed').trigger('click')
    await flushPromises()

    const dialog = wrapper.get('[role="dialog"]')
    await findControlByLabel(dialog, 'Nama Kamar').setValue('1')
    await findControlByLabel(dialog, 'Nama Bed').setValue('Bed Baru')
    await dialog.get('form').trigger('submit')
    await dialog.get('form').trigger('submit')
    await flushPromises()

    expect(bedsService.create).toHaveBeenCalledTimes(1)
    expect(findButtonByText(wrapper.get('[role="dialog"]'), 'Simpan').attributes('disabled')).toBeDefined()

    finishCreate({ data: { id: 5 } })
    await flushPromises()
    expect(toastMessages()).toContain('Data bed berhasil ditambahkan.')
  })

  // Regresi Bug P0 #2: BaseConfirmDialog mengunci tombol hapus selama request berjalan
  it('menghapus bed setelah konfirmasi dan mengunci tombol hapus untuk mencegah double delete', async () => {
    let finishDelete
    bedsService.remove.mockReturnValue(new Promise((resolve) => (finishDelete = resolve)))
    const wrapper = await mountView()

    await wrapper.get('button[aria-label="Hapus Bed 1"]').trigger('click')
    await flushPromises()

    const confirmDialog = wrapper.findComponent(BaseConfirmDialog)
    expect(confirmDialog.props('open')).toBe(true)
    expect(confirmDialog.props('message')).toBe(
      'Apakah Anda yakin ingin menghapus “Bed 1” dari “Kamar 1 - Bangsal Maternal Perinatal”?',
    )

    await findButtonByText(wrapper.get('[role="dialog"]'), 'Hapus').trigger('click')
    await flushPromises()

    expect(confirmDialog.props('loading')).toBe(true)
    expect(findButtonByText(wrapper.get('[role="dialog"]'), 'Hapus').attributes('disabled')).toBeDefined()
    expect(bedsService.remove).toHaveBeenCalledTimes(1)
    expect(bedsService.remove).toHaveBeenCalledWith(1)

    finishDelete({})
    await flushPromises()
    expect(toastMessages()).toContain('Data bed berhasil dihapus.')
  })

  it('mempertahankan alias kompatibilitas bedsService.getBeds', async () => {
    await bedsService.getBeds({ page: 1 })
    expect(bedsService.list).toHaveBeenCalledWith({ page: 1 })
  })
})
