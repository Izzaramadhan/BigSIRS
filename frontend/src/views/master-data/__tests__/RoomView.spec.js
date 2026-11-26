import { nextTick } from 'vue'
import { flushPromises, mount } from '@vue/test-utils'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import RoomView from '../bed/RoomView.vue'
import SearchableSelect from '@/components/common/SearchableSelect.vue'
import AddRoomClassDialog from '@/components/master-data/rooms/AddRoomClassDialog.vue'
import RoomFormModal from '@/components/master-data/rooms/RoomFormModal.vue'
import BaseConfirmDialog from '@/components/ui/BaseConfirmDialog.vue'
import { useToast } from '@/composables/core/useToast'
import { useRooms } from '@/composables/useRooms'
import { roomClassesService } from '@/services/master-data/roomClasses.service'
import { roomsService } from '@/services/master-data/rooms.service'
import { wardsService } from '@/services/master-data/wards.service'

vi.mock('@/services/master-data/rooms.service', async (importOriginal) => {
  const actual = await importOriginal()
  return {
    roomsService: {
      ...actual.roomsService,
      list: vi.fn(),
      get: vi.fn(),
      create: vi.fn(),
      update: vi.fn(),
      remove: vi.fn(),
      getRooms: vi.fn(),
    },
  }
})

vi.mock('@/services/master-data/wards.service', async (importOriginal) => {
  const actual = await importOriginal()
  return {
    wardsService: {
      ...actual.wardsService,
      list: vi.fn(),
      getWards: vi.fn(),
    },
  }
})

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
  },
]

const mockRoomClasses = [
  { id: 1, name: 'Kelas I', code: 'KL1' },
  { id: 2, name: 'Kelas II', code: 'KL2' },
]

const mockRooms = [
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
    room_class_id: null,
    room_class: null,
    class_id: null,
    class: null,
    description: null,
  },
]

const listResponse = (data = mockRooms, metaOverrides = {}) => ({
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
  const wrapper = mount(RoomView, {
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

describe('RoomView', () => {
  beforeEach(() => {
    vi.resetAllMocks()
    vi.useRealTimers()
    toast.clear()
    roomsService.list.mockResolvedValue(listResponse())
    roomsService.getRooms.mockImplementation((params) => roomsService.list(params))
    wardsService.getWards.mockImplementation((params) => wardsService.list(params))
    wardsService.list.mockResolvedValue({ data: mockWards })
    roomClassesService.getRoomClassOptions.mockResolvedValue({ data: mockRoomClasses })
  })

  afterEach(() => {
    vi.useRealTimers()
    document.body.innerHTML = ''
  })

  it('menampilkan header, breadcrumbs, tombol tambah, dan 5 kolom tabel dengan hierarki Bangsal serta Nama Kelas', async () => {
    const wrapper = await mountView()

    expect(wrapper.get('h1').text()).toBe('Data Kamar')
    expect(wrapper.text()).toContain(
      'Kelola data kamar berdasarkan bangsal, lantai, gedung, dan kelas perawatan rumah sakit.',
    )

    const breadcrumbItems = wrapper.get('nav[aria-label="Breadcrumb"]').findAll('li')
    expect(breadcrumbItems.map((li) => li.text().replace('/', '').trim())).toEqual([
      'Dashboard',
      'Master Data',
      'Data Bed',
      'Data Kamar',
    ])

    expect(findButtonByText(wrapper, 'Tambah Kamar')).toBeDefined()
    expect(wrapper.findAll('thead th').map((th) => th.text())).toEqual([
      'No',
      'Nama Bangsal',
      'Nama Kamar',
      'Nama Kelas',
      'Aksi',
    ])

    const [first, second] = tableCells(wrapper)
    expect(first.slice(0, 4)).toEqual([
      '1',
      'Bangsal Maternal Perinatal - Lantai 2 - RUANG BUGENVILLE',
      'Kamar Melati 1',
      'Kelas II',
    ])
    expect(second.slice(0, 4)).toEqual([
      '2',
      'Bangsal Melati - Lantai 1 - Gedung Utama',
      'Kamar Melati 2',
      '—',
    ])
  })

  it('menampilkan loading state dan empty state (kosong serta hasil pencarian kosong)', async () => {
    let resolveList
    roomsService.list.mockReturnValueOnce(new Promise((resolve) => (resolveList = resolve)))

    const wrapper = mount(RoomView, { global: { stubs: { Teleport: true } } })
    await flushPromises()
    expect(wrapper.get('tbody').attributes('aria-busy')).toBe('true')

    resolveList(listResponse([]))
    await flushPromises()
    expect(wrapper.get('tbody').attributes('aria-busy')).toBeUndefined()
    expect(wrapper.text()).toContain('Belum ada data kamar')

    vi.useFakeTimers()
    roomsService.list.mockResolvedValue(listResponse([]))
    await wrapper.get('input[aria-label="Cari kamar"]').setValue('VVIP')
    await nextTick()
    vi.advanceTimersByTime(400)
    await flushPromises()

    expect(wrapper.text()).toContain('Kamar tidak ditemukan')
    expect(wrapper.text()).toContain('Tidak ada kamar yang cocok dengan “VVIP”.')
  })

  it('mengirim pencarian API debounced dan mendukung tombol muat ulang', async () => {
    vi.useFakeTimers()
    const wrapper = await mountView()

    await wrapper.get('input[aria-label="Cari kamar"]').setValue('Melati')
    await nextTick()
    vi.advanceTimersByTime(400)
    await flushPromises()

    expect(roomsService.list).toHaveBeenLastCalledWith(
      expect.objectContaining({
        page: 1,
        search: 'Melati',
      }),
    )

    await wrapper.get('button[aria-label="Muat ulang data"]').trigger('click')
    await flushPromises()
    expect(roomsService.list).toHaveBeenCalledTimes(3)
  })

  it('mengabaikan respons pencarian lama (race condition)', async () => {
    vi.useFakeTimers()
    const wrapper = await mountView()

    let resolveSlow
    let resolveFast
    roomsService.list
      .mockImplementationOnce(() => new Promise((resolve) => (resolveSlow = resolve)))
      .mockImplementationOnce(() => new Promise((resolve) => (resolveFast = resolve)))

    const searchInput = wrapper.get('input[aria-label="Cari kamar"]')
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
          ward_id: 1,
          ward: mockWards[0],
          name: 'Kamar Terbaru',
          room_class: { id: 1, name: 'Kelas I' },
        },
      ]),
    )
    await flushPromises()

    resolveSlow(
      listResponse([
        {
          id: 8,
          ward_id: 1,
          ward: mockWards[0],
          name: 'Kamar Usang',
          room_class: { id: 1, name: 'Kelas I' },
        },
      ]),
    )
    await flushPromises()

    const rows = tableCells(wrapper)
    expect(rows).toHaveLength(1)
    expect(rows[0][2]).toBe('Kamar Terbaru')
  })

  it('mendukung pagination lintas halaman dan mundur halaman saat item terakhir dihapus', async () => {
    roomsService.list
      .mockResolvedValueOnce(
        listResponse(
          [mockRooms[0]],
          { current_page: 1, last_page: 2, per_page: 10, total: 11, from: 1, to: 10 },
        ),
      )
      .mockResolvedValueOnce(
        listResponse(
          [{ ...mockRooms[1], id: 11, name: 'Kamar 11' }],
          { current_page: 2, last_page: 2, per_page: 10, total: 11, from: 11, to: 11 },
        ),
      )
      .mockResolvedValueOnce(
        listResponse(
          [mockRooms[0]],
          { current_page: 1, last_page: 1, per_page: 10, total: 10, from: 1, to: 10 },
        ),
      )
    roomsService.remove.mockResolvedValue({})

    const wrapper = await mountView()
    await wrapper.get('button[aria-label="Halaman berikutnya"]').trigger('click')
    await flushPromises()

    expect(roomsService.list).toHaveBeenLastCalledWith(expect.objectContaining({ page: 2 }))
    expect(tableCells(wrapper)[0][0]).toBe('11')

    await wrapper.get('button[aria-label="Hapus Kamar 11"]').trigger('click')
    await flushPromises()
    await findButtonByText(wrapper.get('[role="dialog"]'), 'Hapus').trigger('click')
    await flushPromises()

    expect(roomsService.list).toHaveBeenLastCalledWith(expect.objectContaining({ page: 1 }))
  })

  it('mendukung sorting API melalui useRooms().setSort', async () => {
    const { setSort, sort, fetchItems } = useRooms()
    await fetchItems()

    expect(roomsService.list).toHaveBeenLastCalledWith(
      expect.objectContaining({ sort: 'created_at', direction: 'desc' }),
    )

    await setSort('name')
    expect(sort.column).toBe('name')
    expect(sort.direction).toBe('asc')
    expect(roomsService.list).toHaveBeenLastCalledWith(
      expect.objectContaining({ page: 1, sort: 'name', direction: 'asc' }),
    )
  })

  it('menampilkan error state aman bila daftar gagal dimuat tanpa bocoran informasi teknis', async () => {
    roomsService.list.mockRejectedValueOnce({
      response: { status: 500, data: { message: 'SQLSTATE[HY000]: Connection refused' } },
    })

    const wrapper = await mountView()
    expect(wrapper.text()).toContain('Data kamar gagal dimuat')
    expect(wrapper.text()).not.toContain('SQLSTATE')

    await findButtonByText(wrapper, 'Coba lagi').trigger('click')
    await flushPromises()
    expect(wrapper.findAll('tbody tr')).toHaveLength(2)
  })

  it('memuat lookup bangsal & kelas non-paginated, mendukung error retry, dan warning bangsal kosong', async () => {
    wardsService.getWards.mockRejectedValueOnce({
      response: { status: 500, data: { message: 'PDOException bangsal' } },
    })
    roomClassesService.getRoomClassOptions.mockRejectedValueOnce({
      response: { status: 500, data: { message: 'SQLSTATE kelas' } },
    })

    const wrapper = await mountView()
    await findButtonByText(wrapper, 'Tambah Kamar').trigger('click')
    await flushPromises()

    expect(wardsService.getWards).toHaveBeenCalledWith({
      per_page: -1,
      sort: 'name',
      direction: 'asc',
    })
    expect(roomClassesService.getRoomClassOptions).toHaveBeenCalledWith({ search: '' })

    const dialogAfterErr = wrapper.get('[role="dialog"]')
    expect(dialogAfterErr.text()).toContain('Gagal memuat data bangsal.')
    expect(dialogAfterErr.text()).toContain('Gagal memuat data kelas.')
    expect(dialogAfterErr.text()).not.toContain('PDOException')
    expect(dialogAfterErr.text()).not.toContain('SQLSTATE')

    wardsService.getWards.mockResolvedValueOnce({ data: [] })
    roomClassesService.getRoomClassOptions.mockResolvedValueOnce({ data: mockRoomClasses })

    const retryButtons = dialogAfterErr.findAll('button').filter((b) => b.text().trim() === 'Coba lagi')
    expect(retryButtons).toHaveLength(2)
    await retryButtons[0].trigger('click')
    await retryButtons[1].trigger('click')
    await flushPromises()

    const dialogEmptyWard = wrapper.get('[role="dialog"]')
    expect(dialogEmptyWard.text()).toContain('Data Bangsal belum tersedia.')
    expect(findButtonByText(dialogEmptyWard, 'Simpan').attributes('disabled')).toBeDefined()

    wardsService.getWards.mockResolvedValueOnce({ data: mockWards })
    roomClassesService.getRoomClassOptions.mockResolvedValueOnce({ data: mockRoomClasses })
    await findButtonByText(dialogEmptyWard, 'Batal').trigger('click')
    await findButtonByText(wrapper, 'Tambah Kamar').trigger('click')
    await flushPromises()

    const wardSelect = findControlByLabel(wrapper.get('[role="dialog"]'), 'Nama Bangsal')
    expect(wardSelect.findAll('option').map((o) => o.text())).toEqual([
      'Pilih bangsal',
      'Bangsal Melati - Lantai 1 - Gedung Utama',
      'Bangsal Maternal Perinatal - Lantai 2 - RUANG BUGENVILLE',
    ])
  })

  it('menambah kamar baru, me-refresh daftar, dan menampilkan toast sukses', async () => {
    roomsService.create.mockResolvedValue({ data: { id: 3 } })
    const wrapper = await mountView()

    await findButtonByText(wrapper, 'Tambah Kamar').trigger('click')
    await flushPromises()

    const dialog = wrapper.get('[role="dialog"]')
    await findControlByLabel(dialog, 'Nama Bangsal').setValue('3')
    await findControlByLabel(dialog, 'Kode Kamar').setValue('KMR-003')
    await findControlByLabel(dialog, 'Nama Kamar').setValue('Kamar Melati 3')
    await findControlByLabel(dialog, 'Kelas').setValue('2')
    await findControlByLabel(dialog, 'Deskripsi').setValue('Kamar baru')
    await dialog.get('form').trigger('submit')
    await flushPromises()

    expect(roomsService.create).toHaveBeenCalledWith({
      ward_id: 3,
      code: 'KMR-003',
      name: 'Kamar Melati 3',
      room_class_id: 2,
      class_id: 2,
      description: 'Kamar baru',
    })
    expect(roomsService.list).toHaveBeenCalledTimes(2)
    expect(toastMessages()).toContain('Data kamar berhasil ditambahkan.')
    expect(wrapper.findComponent(RoomFormModal).props('open')).toBe(false)
  })

  it('mendukung tambah kelas baru inline dengan validasi, pencegahan double submit, menjaga form kamar, dan memilih kelas baru otomatis', async () => {
    let resolveCreateClass
    roomClassesService.createRoomClass
      .mockRejectedValueOnce({
        response: {
          status: 422,
          data: {
            message: 'The given data was invalid.',
            errors: {
              code: ['Kode kelas sudah digunakan.'],
            },
          },
        },
      })
      .mockReturnValueOnce(new Promise((resolve) => (resolveCreateClass = resolve)))

    const wrapper = await mountView()
    await findButtonByText(wrapper, 'Tambah Kamar').trigger('click')
    await flushPromises()

    const roomDialog = wrapper.get('[role="dialog"]')
    await findControlByLabel(roomDialog, 'Nama Bangsal').setValue('3')
    await findControlByLabel(roomDialog, 'Kode Kamar').setValue('KMR-999')
    await findControlByLabel(roomDialog, 'Nama Kamar').setValue('Kamar VVIP Baru')
    await findControlByLabel(roomDialog, 'Deskripsi').setValue('Catatan kamar tetap terjaga')

    const selects = roomDialog.findAllComponents(SearchableSelect)
    selects[1].vm.$emit('create', 'Kelas VVIP Baru')
    await flushPromises()

    const addClassDialog = wrapper.findComponent(AddRoomClassDialog)
    expect(addClassDialog.props('open')).toBe(true)
    expect(addClassDialog.props('initialName')).toBe('Kelas VVIP Baru')

    // Validasi wajib saat Kode dan Harga masih kosong
    const getInlineDialog = () => wrapper.findAll('[role="dialog"]')[1]
    await getInlineDialog().get('form').trigger('submit')
    await flushPromises()
    expect(getInlineDialog().text()).toContain('Kode kelas wajib diisi.')
    expect(getInlineDialog().text()).toContain('Harga kelas wajib diisi.')
    expect(roomClassesService.createRoomClass).not.toHaveBeenCalled()

    // Uji error 422 dari server pada field Kode Kelas
    await findControlByLabel(getInlineDialog(), 'Kode Kelas').setValue('VIP-DUP')
    await findControlByLabel(getInlineDialog(), 'Harga Kelas').setValue('0')
    await getInlineDialog().get('form').trigger('submit')
    await flushPromises()

    expect(getInlineDialog().text()).toContain('Kode kelas sudah digunakan.')
    expect(addClassDialog.props('open')).toBe(true)

    // Submit valid + pencegahan double submit saat saving
    await findControlByLabel(getInlineDialog(), 'Kode Kelas').setValue('VVIP-01')
    await getInlineDialog().get('form').trigger('submit')
    await nextTick()
    await getInlineDialog().get('form').trigger('submit')
    expect(roomClassesService.createRoomClass).toHaveBeenCalledTimes(2)
    expect(roomClassesService.createRoomClass).toHaveBeenLastCalledWith({
      name: 'Kelas VVIP Baru',
      code: 'VVIP-01',
      price: 0,
    })

    resolveCreateClass({
      data: { id: 99, code: 'VVIP-01', name: 'Kelas VVIP Baru', price: 0 },
    })
    await flushPromises()

    expect(addClassDialog.props('open')).toBe(false)
    expect(toastMessages()).toContain('Data kelas berhasil ditambahkan.')

    const currentRoomDialog = wrapper.get('[role="dialog"]')
    expect(findControlByLabel(currentRoomDialog, 'Kelas').element.value).toBe('99')
    expect(findControlByLabel(currentRoomDialog, 'Nama Bangsal').element.value).toBe('3')
    expect(findControlByLabel(currentRoomDialog, 'Kode Kamar').element.value).toBe('KMR-999')
    expect(findControlByLabel(currentRoomDialog, 'Nama Kamar').element.value).toBe('Kamar VVIP Baru')
    expect(findControlByLabel(currentRoomDialog, 'Deskripsi').element.value).toBe(
      'Catatan kamar tetap terjaga',
    )
  })

  it('membuka form edit berisi data lama termasuk kelas tersimpan dan mengirim pembaruan', async () => {
    roomsService.update.mockResolvedValue({ data: { id: 1 } })
    const wrapper = await mountView()

    await wrapper.get('button[aria-label="Edit Kamar Melati 1"]').trigger('click')
    await flushPromises()

    const dialog = wrapper.get('[role="dialog"]')
    expect(dialog.text()).toContain('Edit Kamar')
    expect(findControlByLabel(dialog, 'Nama Bangsal').element.value).toBe('3')
    expect(findControlByLabel(dialog, 'Kode Kamar').element.value).toBe('KMR-001')
    expect(findControlByLabel(dialog, 'Nama Kamar').element.value).toBe('Kamar Melati 1')
    expect(findControlByLabel(dialog, 'Kelas').element.value).toBe('2')

    await findControlByLabel(dialog, 'Kelas').setValue('1')
    await findControlByLabel(dialog, 'Nama Kamar').setValue('Kamar Melati 1 Update')
    await dialog.get('form').trigger('submit')
    await flushPromises()

    expect(roomsService.update).toHaveBeenCalledWith(1, {
      ward_id: 3,
      code: 'KMR-001',
      name: 'Kamar Melati 1 Update',
      room_class_id: 1,
      class_id: 1,
      description: 'Kamar perawatan maternal',
    })
    expect(toastMessages()).toContain('Data kamar berhasil diperbarui.')
  })

  it('validasi 422 menjaga tabel tetap tampil dan mengirim isInvalid ke SearchableSelect serta field terkait', async () => {
    roomsService.create.mockRejectedValue({
      response: {
        status: 422,
        data: {
          message: 'The given data was invalid.',
          errors: {
            ward_id: ['Bangsal yang dipilih harus memiliki relasi Lantai dan Gedung yang valid.'],
            code: ['Kode kamar sudah digunakan.'],
            name: ['Nama kamar sudah digunakan pada bangsal yang dipilih.'],
          },
        },
      },
    })

    const wrapper = await mountView()
    await findButtonByText(wrapper, 'Tambah Kamar').trigger('click')
    await flushPromises()

    const dialog = wrapper.get('[role="dialog"]')
    await findControlByLabel(dialog, 'Nama Bangsal').setValue('3')
    await findControlByLabel(dialog, 'Kode Kamar').setValue('KMR-001')
    await findControlByLabel(dialog, 'Nama Kamar').setValue('Kamar Melati 1')
    await findControlByLabel(dialog, 'Kelas').setValue('2')
    await dialog.get('form').trigger('submit')
    await flushPromises()

    const updatedDialog = wrapper.get('[role="dialog"]')
    expect(wrapper.findComponent(RoomFormModal).props('open')).toBe(true)
    expect(updatedDialog.text()).toContain(
      'Bangsal yang dipilih harus memiliki relasi Lantai dan Gedung yang valid.',
    )
    expect(updatedDialog.text()).toContain('Kode kamar sudah digunakan.')
    expect(updatedDialog.text()).toContain('Nama kamar sudah digunakan pada bangsal yang dipilih.')
    expect(updatedDialog.findAllComponents(SearchableSelect)[0].props('isInvalid')).toBe(true)
    expect(wrapper.findAll('tbody tr')).toHaveLength(2)
    expect(toastMessages()).toContain('The given data was invalid.')
    expect(toastMessages()).not.toContain('Data kamar berhasil ditambahkan.')
  })

  it('validasi sisi klien mewajibkan bangsal, kode, nama kamar, dan kelas serta menandai dropdown invalid', async () => {
    const wrapper = await mountView()
    await findButtonByText(wrapper, 'Tambah Kamar').trigger('click')
    await flushPromises()

    await wrapper.get('[role="dialog"] form').trigger('submit')
    await flushPromises()

    const updatedDialog = wrapper.get('[role="dialog"]')
    expect(updatedDialog.text()).toContain('Bangsal wajib dipilih.')
    expect(updatedDialog.text()).toContain('Kode kamar wajib diisi.')
    expect(updatedDialog.text()).toContain('Nama kamar wajib diisi.')
    expect(updatedDialog.text()).toContain('Kelas wajib dipilih.')
    const selects = updatedDialog.findAllComponents(SearchableSelect)
    expect(selects[0].props('isInvalid')).toBe(true)
    expect(selects[1].props('isInvalid')).toBe(true)
    expect(roomsService.create).not.toHaveBeenCalled()
  })

  it('mencegah double submit selama proses simpan berlangsung', async () => {
    let finishCreate
    roomsService.create.mockReturnValue(new Promise((resolve) => (finishCreate = resolve)))

    const wrapper = await mountView()
    await findButtonByText(wrapper, 'Tambah Kamar').trigger('click')
    await flushPromises()

    const dialog = wrapper.get('[role="dialog"]')
    await findControlByLabel(dialog, 'Nama Bangsal').setValue('3')
    await findControlByLabel(dialog, 'Kode Kamar').setValue('KMR-009')
    await findControlByLabel(dialog, 'Nama Kamar').setValue('Kamar Baru')
    await findControlByLabel(dialog, 'Kelas').setValue('1')
    await dialog.get('form').trigger('submit')
    await dialog.get('form').trigger('submit')
    await flushPromises()

    expect(roomsService.create).toHaveBeenCalledTimes(1)
    expect(findButtonByText(wrapper.get('[role="dialog"]'), 'Simpan').attributes('disabled')).toBeDefined()

    finishCreate({ data: { id: 9 } })
    await flushPromises()
    expect(toastMessages()).toContain('Data kamar berhasil ditambahkan.')
  })

  it('menghapus kamar melalui BaseConfirmDialog dan mencegah double delete', async () => {
    let finishDelete
    roomsService.remove.mockReturnValue(new Promise((resolve) => (finishDelete = resolve)))
    const wrapper = await mountView()

    await wrapper.get('button[aria-label="Hapus Kamar Melati 1"]').trigger('click')
    await flushPromises()

    const confirmDialog = wrapper.findComponent(BaseConfirmDialog)
    expect(confirmDialog.props('open')).toBe(true)
    expect(confirmDialog.props('message')).toBe(
      'Apakah Anda yakin ingin menghapus kamar “Kamar Melati 1” dari “Bangsal Maternal Perinatal - Lantai 2 - RUANG BUGENVILLE”?',
    )

    await findButtonByText(wrapper.get('[role="dialog"]'), 'Hapus').trigger('click')
    await flushPromises()

    expect(confirmDialog.props('loading')).toBe(true)
    expect(findButtonByText(wrapper.get('[role="dialog"]'), 'Hapus').attributes('disabled')).toBeDefined()
    expect(roomsService.remove).toHaveBeenCalledTimes(1)
    expect(roomsService.remove).toHaveBeenCalledWith(1)

    finishDelete({})
    await flushPromises()
    expect(toastMessages()).toContain('Data kamar berhasil dihapus.')
  })

  it('menampilkan toast error saat hapus kamar gagal tanpa menghilangkan tabel', async () => {
    roomsService.remove.mockRejectedValue({
      response: {
        status: 422,
        data: { message: 'Kamar tidak dapat dihapus karena masih digunakan oleh data tempat tidur (bed).' },
      },
    })

    const wrapper = await mountView()
    await wrapper.get('button[aria-label="Hapus Kamar Melati 1"]').trigger('click')
    await flushPromises()

    await findButtonByText(wrapper.get('[role="dialog"]'), 'Hapus').trigger('click')
    await flushPromises()

    expect(toastMessages()).toContain(
      'Kamar tidak dapat dihapus karena masih digunakan oleh data tempat tidur (bed).',
    )
    expect(wrapper.findAll('tbody tr')).toHaveLength(2)
  })

  it('mempertahankan kompatibilitas alias roomsService.getRooms dan wardsService.getWards', async () => {
    await roomsService.getRooms({ per_page: -1 })
    expect(roomsService.list).toHaveBeenCalledWith({ per_page: -1 })

    await wardsService.getWards({ per_page: -1 })
    expect(wardsService.list).toHaveBeenCalledWith({ per_page: -1 })
  })
})
