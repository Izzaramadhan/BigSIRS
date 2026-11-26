import { nextTick } from 'vue'
import { flushPromises, mount } from '@vue/test-utils'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import RoomClassView from '../bed/RoomClassView.vue'
import { useToast } from '@/composables/core/useToast'
import { useRoomClasses } from '@/composables/useRoomClasses'
import { roomClassesService } from '@/services/master-data/roomClasses.service'

vi.mock('@/services/master-data/roomClasses.service', async (importOriginal) => {
  const actual = await importOriginal()
  return {
    roomClassesService: {
      ...actual.roomClassesService,
      list: vi.fn(),
      get: vi.fn(),
      create: vi.fn(),
      update: vi.fn(),
      remove: vi.fn(),
      getRoomClasses: vi.fn(),
    },
  }
})

const mockRoomClasses = [
  {
    id: 1,
    code: 'KLS-1',
    name: 'Kelas 1',
    price: 150000,
    formatted_price: 'Rp150.000',
    description: 'Kelas satu dengan AC',
    aplicare_code: 'KL1',
    sirs_code: '1',
    created_at: '2026-10-22T08:00:00Z',
    updated_at: '2026-10-22T08:00:00Z',
  },
  {
    id: 2,
    code: 'KLS-3',
    name: 'Kelas 3',
    price: 0,
    formatted_price: 'Rp0',
    description: null,
    aplicare_code: null,
    sirs_code: null,
    created_at: '2026-10-22T08:00:00Z',
    updated_at: '2026-10-22T08:00:00Z',
  },
]

const listResponse = (data = mockRoomClasses, metaOverrides = {}) => ({
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
  const wrapper = mount(RoomClassView, {
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

describe('RoomClassView', () => {
  beforeEach(() => {
    vi.resetAllMocks()
    vi.useRealTimers()
    toast.clear()
    roomClassesService.list.mockResolvedValue(listResponse())
    roomClassesService.getRoomClasses.mockImplementation((params) => roomClassesService.list(params))
  })

  afterEach(() => {
    vi.useRealTimers()
    document.body.innerHTML = ''
  })

  it('menampilkan header, breadcrumbs, tombol tambah, dan 7 kolom standar termasuk format harga Rupiah', async () => {
    const wrapper = await mountView()

    expect(wrapper.get('h1').text()).toBe('Data Kelas')
    expect(wrapper.text()).toContain(
      'Kelola data kelas perawatan, tarif harian, serta kode integrasi BPJS Aplicare dan SIRS.',
    )

    const breadcrumbItems = wrapper.get('nav[aria-label="Breadcrumb"]').findAll('li')
    expect(breadcrumbItems.map((li) => li.text().replace('/', '').trim())).toEqual([
      'Dashboard',
      'Master Data',
      'Data Bed',
      'Data Kelas',
    ])

    expect(findButtonByText(wrapper, 'Tambah Kelas')).toBeDefined()

    const headers = wrapper.findAll('thead th').map((th) => th.text())
    expect(headers).toEqual([
      'No',
      'Nama Kelas',
      'Harga Kelas',
      'Deskripsi',
      'Kode Kelas BPJS',
      'Kode Kelas SIRS',
      'Aksi',
    ])

    const [first, second] = tableCells(wrapper)
    expect(first.slice(0, 6)).toEqual([
      '1',
      'Kelas 1',
      'Rp150.000',
      'Kelas satu dengan AC',
      'KL1',
      '1',
    ])
    expect(second.slice(0, 6)).toEqual(['2', 'Kelas 3', 'Rp0', '—', '—', '—'])
  })

  it('menampilkan loading state dan empty state untuk data kosong maupun pencarian kosong', async () => {
    let resolveList
    roomClassesService.list.mockReturnValueOnce(new Promise((resolve) => (resolveList = resolve)))

    const wrapper = mount(RoomClassView, { global: { stubs: { Teleport: true } } })
    await flushPromises()
    expect(wrapper.get('tbody').attributes('aria-busy')).toBe('true')

    resolveList(listResponse([]))
    await flushPromises()
    expect(wrapper.get('tbody').attributes('aria-busy')).toBeUndefined()
    expect(wrapper.text()).toContain('Belum ada data kelas')

    vi.useFakeTimers()
    roomClassesService.list.mockResolvedValue(listResponse([]))
    await wrapper.get('input[aria-label="Cari kelas"]').setValue('President')
    await nextTick()
    vi.advanceTimersByTime(400)
    await flushPromises()

    expect(wrapper.text()).toContain('Kelas tidak ditemukan')
    expect(wrapper.text()).toContain('Tidak ada kelas yang cocok dengan “President”.')
  })

  it('mengirim pencarian API debounced, mendukung muat ulang, dan mengabaikan respons lama', async () => {
    vi.useFakeTimers()
    const wrapper = await mountView()

    await wrapper.get('input[aria-label="Cari kelas"]').setValue('VIP')
    await nextTick()
    vi.advanceTimersByTime(400)
    await flushPromises()

    expect(roomClassesService.list).toHaveBeenLastCalledWith(
      expect.objectContaining({ page: 1, search: 'VIP' }),
    )

    await wrapper.get('button[aria-label="Muat ulang data"]').trigger('click')
    await flushPromises()
    expect(roomClassesService.list).toHaveBeenCalledTimes(3)

    let resolveSlow
    let resolveFast
    roomClassesService.list
      .mockImplementationOnce(() => new Promise((resolve) => (resolveSlow = resolve)))
      .mockImplementationOnce(() => new Promise((resolve) => (resolveFast = resolve)))

    const searchInput = wrapper.get('input[aria-label="Cari kelas"]')
    await searchInput.setValue('Lama')
    await nextTick()
    vi.advanceTimersByTime(400)
    await nextTick()

    await searchInput.setValue('Baru')
    await nextTick()
    vi.advanceTimersByTime(400)
    await nextTick()

    resolveFast(listResponse([{ id: 9, name: 'Kelas Terbaru', code: 'BARU', price: 200000 }]))
    await flushPromises()

    resolveSlow(listResponse([{ id: 8, name: 'Kelas Usang', code: 'LAMA', price: 100000 }]))
    await flushPromises()

    const rows = tableCells(wrapper)
    expect(rows).toHaveLength(1)
    expect(rows[0][1]).toBe('Kelas Terbaru')
  })

  it('mendukung pagination lintas halaman dan mundur halaman saat item terakhir dihapus', async () => {
    roomClassesService.list
      .mockResolvedValueOnce(
        listResponse(
          [mockRoomClasses[0]],
          { current_page: 1, last_page: 2, per_page: 10, total: 11, from: 1, to: 10 },
        ),
      )
      .mockResolvedValueOnce(
        listResponse(
          [{ id: 11, name: 'Kelas 11', code: 'K11', price: 90000 }],
          { current_page: 2, last_page: 2, per_page: 10, total: 11, from: 11, to: 11 },
        ),
      )
      .mockResolvedValueOnce(
        listResponse(
          [mockRoomClasses[0]],
          { current_page: 1, last_page: 1, per_page: 10, total: 10, from: 1, to: 10 },
        ),
      )
    roomClassesService.remove.mockResolvedValue({})

    const wrapper = await mountView()
    await wrapper.get('button[aria-label="Halaman berikutnya"]').trigger('click')
    await flushPromises()

    expect(roomClassesService.list).toHaveBeenLastCalledWith(expect.objectContaining({ page: 2 }))
    expect(tableCells(wrapper)[0][0]).toBe('11')

    await wrapper.get('button[aria-label="Hapus Kelas 11"]').trigger('click')
    await flushPromises()
    await findButtonByText(wrapper.get('[role="dialog"]'), 'Hapus').trigger('click')
    await flushPromises()

    expect(roomClassesService.list).toHaveBeenLastCalledWith(expect.objectContaining({ page: 1 }))
  })

  it('mendukung default sorting ascending by name dan setSort lewat useRoomClasses()', async () => {
    const { setSort, sort, fetchItems } = useRoomClasses()
    await fetchItems()

    expect(roomClassesService.list).toHaveBeenLastCalledWith(
      expect.objectContaining({ sort: 'name', direction: 'asc' }),
    )

    await setSort('name')
    expect(sort.column).toBe('name')
    expect(sort.direction).toBe('desc')
    expect(roomClassesService.list).toHaveBeenLastCalledWith(
      expect.objectContaining({ page: 1, sort: 'name', direction: 'desc' }),
    )
  })

  it('menampilkan error state aman tanpa SQLSTATE bila daftar gagal dimuat dan mendukung retry', async () => {
    roomClassesService.list.mockRejectedValueOnce({
      response: { status: 500, data: { message: 'SQLSTATE[HY000]: Connection refused' } },
    })

    const wrapper = await mountView()

    expect(wrapper.text()).toContain('Data kelas gagal dimuat')
    expect(wrapper.text()).not.toContain('SQLSTATE')

    await findButtonByText(wrapper, 'Coba lagi').trigger('click')
    await flushPromises()
    expect(wrapper.findAll('tbody tr')).toHaveLength(2)
  })

  it('memvalidasi form tambah, memformat input harga Rupiah, dan menambah kelas baru', async () => {
    roomClassesService.create.mockResolvedValue({ data: { id: 3, name: 'VIP', code: 'VIP' } })

    const wrapper = await mountView()
    await findButtonByText(wrapper, 'Tambah Kelas').trigger('click')
    await flushPromises()

    const dialog = wrapper.get('[role="dialog"]')
    expect(dialog.text()).toContain(
      'Kosongkan jika tidak perlu bridging ke sistem BPJS Kesehatan.',
    )
    expect(dialog.text()).toContain('Kosongkan jika tidak perlu pelaporan ke sistem SIRS.')

    // Submit kosong memunculkan error validasi klien
    await dialog.get('form').trigger('submit')
    await flushPromises()
    expect(dialog.text()).toContain('Nama kelas wajib diisi.')
    expect(dialog.text()).toContain('Kode kelas wajib diisi.')
    expect(dialog.text()).toContain('Harga kelas wajib diisi.')
    expect(roomClassesService.create).not.toHaveBeenCalled()

    await findControlByLabel(dialog, 'Nama Kelas').setValue('  VIP  ')
    await findControlByLabel(dialog, 'Kode Kelas').setValue(' VIP ')
    const priceInput = findControlByLabel(dialog, 'Harga Kelas')
    await priceInput.setValue('250000')
    expect(priceInput.element.value).toBe('250.000')
    await findControlByLabel(dialog, 'Deskripsi').setValue('Kamar VIP dengan Sofa Bed')
    await findControlByLabel(dialog, 'Kode Aplicare').setValue('VIP1')

    await dialog.get('form').trigger('submit')
    await flushPromises()

    expect(roomClassesService.create).toHaveBeenCalledWith({
      name: 'VIP',
      code: 'VIP',
      price: 250000,
      description: 'Kamar VIP dengan Sofa Bed',
      aplicare_code: 'VIP1',
      sirs_code: null,
    })
    expect(toastMessages()).toContain('Data kelas berhasil ditambahkan.')
    expect(wrapper.find('[role="dialog"]').exists()).toBe(false)
  })

  it('mengedit kelas, menangani error 422/500 dengan aman, dan memblokir submit ganda saat menyimpan', async () => {
    let resolveUpdate
    roomClassesService.update
      .mockRejectedValueOnce({
        response: {
          status: 422,
          data: {
            message: 'The given data was invalid.',
            errors: {
              name: ['Nama kelas sudah digunakan.'],
              code: ['Kode kelas sudah digunakan.'],
            },
          },
        },
      })
      .mockRejectedValueOnce({
        response: {
          status: 500,
          data: { message: 'PDOException: connection lost' },
        },
      })
      .mockReturnValueOnce(new Promise((resolve) => (resolveUpdate = resolve)))

    const wrapper = await mountView()
    await wrapper.get('button[aria-label="Edit Kelas 1"]').trigger('click')
    await flushPromises()

    const dialog = wrapper.get('[role="dialog"]')
    expect(dialog.text()).toContain('Edit Kelas')
    expect(findControlByLabel(dialog, 'Nama Kelas').element.value).toBe('Kelas 1')
    expect(findControlByLabel(dialog, 'Kode Kelas').element.value).toBe('KLS-1')
    expect(findControlByLabel(dialog, 'Harga Kelas').element.value).toBe('150.000')
    expect(findControlByLabel(dialog, 'Kode Aplicare').element.value).toBe('KL1')
    expect(findControlByLabel(dialog, 'Kode SIRS').element.value).toBe('1')

    // Server error 422 per field
    await dialog.get('form').trigger('submit')
    await flushPromises()
    expect(wrapper.get('[role="dialog"]').text()).toContain('Nama kelas sudah digunakan.')
    expect(wrapper.get('[role="dialog"]').text()).toContain('Kode kelas sudah digunakan.')

    // Server error 500 aman di alert modal tanpa membocorkan PDOException
    await findControlByLabel(wrapper.get('[role="dialog"]'), 'Nama Kelas').setValue('Kelas 1 Baru')
    await wrapper.get('[role="dialog"] form').trigger('submit')
    await flushPromises()
    expect(wrapper.get('[role="dialog"]').text()).toContain('Data kelas gagal diperbarui.')
    expect(wrapper.get('[role="dialog"]').text()).not.toContain('PDOException')

    // Submit berhasil + blokir double-submit saat saving=true
    await wrapper.get('[role="dialog"] form').trigger('submit')
    await nextTick()
    await wrapper.get('[role="dialog"] form').trigger('submit')
    expect(roomClassesService.update).toHaveBeenCalledTimes(3)

    resolveUpdate({ data: { id: 1, name: 'Kelas 1 Baru' } })
    await flushPromises()

    expect(roomClassesService.update).toHaveBeenLastCalledWith(1, {
      name: 'Kelas 1 Baru',
      code: 'KLS-1',
      price: 150000,
      description: 'Kelas satu dengan AC',
      aplicare_code: 'KL1',
      sirs_code: '1',
    })
    expect(toastMessages()).toContain('Data kelas berhasil diperbarui.')
  })

  it('menghapus kelas melalui dialog konfirmasi dan menangani penolakan 422', async () => {
    roomClassesService.remove
      .mockRejectedValueOnce({
        response: {
          status: 422,
          data: { message: 'Data kelas tidak dapat dihapus karena masih digunakan oleh data kamar.' },
        },
      })
      .mockResolvedValueOnce({})

    const wrapper = await mountView()

    // Skenario gagal 422
    await wrapper.get('button[aria-label="Hapus Kelas 1"]').trigger('click')
    await flushPromises()
    const confirmDialog = wrapper.get('[role="dialog"]')
    expect(confirmDialog.text()).toContain('Apakah Anda yakin ingin menghapus kelas “Kelas 1”?')

    await findButtonByText(confirmDialog, 'Hapus').trigger('click')
    await flushPromises()
    expect(roomClassesService.remove).toHaveBeenCalledWith(1)
    expect(toastMessages()).toContain(
      'Data kelas tidak dapat dihapus karena masih digunakan oleh data kamar.',
    )

    // Skenario sukses
    await wrapper.get('button[aria-label="Hapus Kelas 3"]').trigger('click')
    await flushPromises()
    await findButtonByText(wrapper.get('[role="dialog"]'), 'Hapus').trigger('click')
    await flushPromises()

    expect(roomClassesService.remove).toHaveBeenCalledWith(2)
    expect(toastMessages()).toContain('Data kelas berhasil dihapus.')
  })
})
