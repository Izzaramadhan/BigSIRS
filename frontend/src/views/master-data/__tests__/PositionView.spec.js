import { nextTick } from 'vue'
import { flushPromises, mount } from '@vue/test-utils'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import PositionView from '../PositionView.vue'
import { useToast } from '@/composables/core/useToast'
import { usePositions } from '@/composables/usePositions'
import { positionsService } from '@/services/master-data/positions.service'

vi.mock('@/services/master-data/positions.service', async (importOriginal) => {
  const actual = await importOriginal()
  return {
    positionsService: {
      ...actual.positionsService,
      list: vi.fn(),
      get: vi.fn(),
      create: vi.fn(),
      update: vi.fn(),
      remove: vi.fn(),
    },
  }
})

const mockPositions = [
  { id: 1, name: 'Administrator', description: 'Pengelola sistem', is_active: true },
  { id: 2, name: 'Perawat', description: null, is_active: true },
]

const listResponse = (data = mockPositions, metaOverrides = {}) => ({
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
  const wrapper = mount(PositionView, {
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

describe('PositionView', () => {
  beforeEach(() => {
    vi.resetAllMocks()
    vi.useRealTimers()
    toast.clear()
    positionsService.list.mockResolvedValue(listResponse())
  })

  afterEach(() => {
    vi.useRealTimers()
    document.body.innerHTML = ''
  })

  it('menampilkan header, breadcrumbs, tombol tambah, dan 4 kolom standar (No, Nama Jabatan, Deskripsi, Aksi)', async () => {
    const wrapper = await mountView()

    expect(wrapper.get('h1').text()).toBe('Data Jabatan')
    expect(wrapper.text()).toContain('Kelola data dasar tingkat jabatan pegawai.')

    const breadcrumbItems = wrapper.get('nav[aria-label="Breadcrumb"]').findAll('li')
    expect(breadcrumbItems.map((li) => li.text().replace('/', '').trim())).toEqual([
      'Dashboard',
      'Data Pegawai',
      'Jabatan',
    ])

    expect(findButtonByText(wrapper, 'Tambah Jabatan')).toBeDefined()

    const headers = wrapper.findAll('thead th').map((th) => th.text())
    expect(headers).toEqual(['No', 'Nama Jabatan', 'Deskripsi', 'Aksi'])

    const [first, second] = tableCells(wrapper)
    expect(first.slice(0, 3)).toEqual(['1', 'Administrator', 'Pengelola sistem'])
    expect(second.slice(0, 3)).toEqual(['2', 'Perawat', '—'])
  })

  it('menampilkan loading state dan empty state (data kosong maupun hasil pencarian kosong)', async () => {
    let resolveList
    positionsService.list.mockReturnValueOnce(new Promise((resolve) => (resolveList = resolve)))

    const wrapper = mount(PositionView, { global: { stubs: { Teleport: true } } })
    await flushPromises()
    expect(wrapper.get('tbody').attributes('aria-busy')).toBe('true')

    resolveList(listResponse([]))
    await flushPromises()
    expect(wrapper.get('tbody').attributes('aria-busy')).toBeUndefined()
    expect(wrapper.text()).toContain('Belum ada data jabatan')

    vi.useFakeTimers()
    positionsService.list.mockResolvedValue(listResponse([]))
    await wrapper.get('input[aria-label="Cari nama jabatan"]').setValue('Direktur')
    await nextTick()
    vi.advanceTimersByTime(400)
    await flushPromises()

    expect(wrapper.text()).toContain('Jabatan tidak ditemukan')
    expect(wrapper.text()).toContain('Tidak ada jabatan yang cocok dengan “Direktur”.')
  })

  it('mengirim pencarian API debounced, mendukung muat ulang, dan mengabaikan respons lama', async () => {
    vi.useFakeTimers()
    const wrapper = await mountView()

    await wrapper.get('input[aria-label="Cari nama jabatan"]').setValue('Admin')
    await nextTick()
    vi.advanceTimersByTime(400)
    await flushPromises()

    expect(positionsService.list).toHaveBeenLastCalledWith(
      expect.objectContaining({ page: 1, search: 'Admin' }),
    )

    await wrapper.get('button[aria-label="Muat ulang data"]').trigger('click')
    await flushPromises()
    expect(positionsService.list).toHaveBeenCalledTimes(3)

    let resolveSlow
    let resolveFast
    positionsService.list
      .mockImplementationOnce(() => new Promise((resolve) => (resolveSlow = resolve)))
      .mockImplementationOnce(() => new Promise((resolve) => (resolveFast = resolve)))

    const searchInput = wrapper.get('input[aria-label="Cari nama jabatan"]')
    await searchInput.setValue('Lama')
    await nextTick()
    vi.advanceTimersByTime(400)
    await nextTick()

    await searchInput.setValue('Baru')
    await nextTick()
    vi.advanceTimersByTime(400)
    await nextTick()

    resolveFast(listResponse([{ id: 9, name: 'Kepala Ruangan', description: 'Unit Ranap' }]))
    await flushPromises()

    resolveSlow(listResponse([{ id: 8, name: 'Jabatan Usang', description: null }]))
    await flushPromises()

    const rows = tableCells(wrapper)
    expect(rows).toHaveLength(1)
    expect(rows[0][1]).toBe('Kepala Ruangan')
  })

  it('mendukung pagination lintas halaman dan mundur halaman saat item terakhir dihapus', async () => {
    positionsService.list
      .mockResolvedValueOnce(
        listResponse(
          [mockPositions[0]],
          { current_page: 1, last_page: 2, per_page: 10, total: 11, from: 1, to: 10 },
        ),
      )
      .mockResolvedValueOnce(
        listResponse(
          [{ id: 11, name: 'Kepala IGD', description: null }],
          { current_page: 2, last_page: 2, per_page: 10, total: 11, from: 11, to: 11 },
        ),
      )
      .mockResolvedValueOnce(
        listResponse(
          [mockPositions[0]],
          { current_page: 1, last_page: 1, per_page: 10, total: 10, from: 1, to: 10 },
        ),
      )
    positionsService.remove.mockResolvedValue({})

    const wrapper = await mountView()
    await wrapper.get('button[aria-label="Halaman berikutnya"]').trigger('click')
    await flushPromises()

    expect(positionsService.list).toHaveBeenLastCalledWith(expect.objectContaining({ page: 2 }))
    expect(tableCells(wrapper)[0][0]).toBe('11')

    await wrapper.get('button[aria-label="Hapus Kepala IGD"]').trigger('click')
    await flushPromises()
    await findButtonByText(wrapper.get('[role="dialog"]'), 'Hapus').trigger('click')
    await flushPromises()

    expect(positionsService.list).toHaveBeenLastCalledWith(expect.objectContaining({ page: 1 }))
  })

  it('mendukung default sorting ascending by name, setSort, dan kompatibilitas alias positionsService', async () => {
    const { setSort, sort, fetchItems } = usePositions()
    await fetchItems()

    expect(positionsService.list).toHaveBeenLastCalledWith(
      expect.objectContaining({ sort: 'name', direction: 'asc' }),
    )

    await setSort('name')
    expect(sort.column).toBe('name')
    expect(sort.direction).toBe('desc')
    expect(positionsService.list).toHaveBeenLastCalledWith(
      expect.objectContaining({ page: 1, sort: 'name', direction: 'desc' }),
    )

    expect(typeof positionsService.getPositions).toBe('function')
    expect(typeof positionsService.getPosition).toBe('function')
    expect(typeof positionsService.createPosition).toBe('function')
    expect(typeof positionsService.updatePosition).toBe('function')
    expect(typeof positionsService.deletePosition).toBe('function')
  })

  it('menampilkan error state aman tanpa SQLSTATE bila daftar gagal dimuat dan mendukung retry', async () => {
    positionsService.list.mockRejectedValueOnce({
      response: { status: 500, data: { message: 'SQLSTATE[HY000]: Connection refused' } },
    })

    const wrapper = await mountView()

    expect(wrapper.text()).toContain('Data jabatan gagal dimuat')
    expect(wrapper.text()).not.toContain('SQLSTATE')

    await findButtonByText(wrapper, 'Coba lagi').trigger('click')
    await flushPromises()
    expect(wrapper.findAll('tbody tr')).toHaveLength(2)
  })

  it('memvalidasi form tambah dan menambah data jabatan baru dengan notifikasi toast', async () => {
    positionsService.create.mockResolvedValue({ data: { id: 3, name: 'Kepala Poli' } })

    const wrapper = await mountView()
    await findButtonByText(wrapper, 'Tambah Jabatan').trigger('click')
    await flushPromises()

    const dialog = wrapper.get('[role="dialog"]')
    await dialog.get('form').trigger('submit')
    await flushPromises()

    expect(dialog.text()).toContain('Nama jabatan wajib diisi.')
    expect(positionsService.create).not.toHaveBeenCalled()

    await findControlByLabel(dialog, 'Nama Jabatan').setValue('  Kepala   Poli  ')
    await findControlByLabel(dialog, 'Deskripsi').setValue('Koordinator poliklinik')
    await dialog.get('form').trigger('submit')
    await flushPromises()

    expect(positionsService.create).toHaveBeenCalledWith({
      name: 'Kepala Poli',
      description: 'Koordinator poliklinik',
    })
    expect(toastMessages()).toContain('Data jabatan berhasil ditambahkan.')
    expect(wrapper.find('[role="dialog"]').exists()).toBe(false)
  })

  it('mengedit jabatan, menangani error 422/500 dengan aman, dan mencegah double submit', async () => {
    let resolveUpdate
    positionsService.update
      .mockRejectedValueOnce({
        response: {
          status: 422,
          data: {
            message: 'The given data was invalid.',
            errors: { name: ['The name has already been taken.'] },
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
    await wrapper.get('button[aria-label="Edit Administrator"]').trigger('click')
    await flushPromises()

    const dialog = wrapper.get('[role="dialog"]')
    expect(dialog.text()).toContain('Edit Jabatan')
    expect(findControlByLabel(dialog, 'Nama Jabatan').element.value).toBe('Administrator')
    expect(findControlByLabel(dialog, 'Deskripsi').element.value).toBe('Pengelola sistem')

    await dialog.get('form').trigger('submit')
    await flushPromises()
    expect(wrapper.get('[role="dialog"]').text()).toContain('The name has already been taken.')
    expect(wrapper.findAll('tbody tr')).toHaveLength(2)

    await findControlByLabel(wrapper.get('[role="dialog"]'), 'Nama Jabatan').setValue('Admin IT')
    await findControlByLabel(wrapper.get('[role="dialog"]'), 'Deskripsi').setValue('   ')
    await wrapper.get('[role="dialog"] form').trigger('submit')
    await flushPromises()
    expect(wrapper.get('[role="dialog"]').text()).toContain('Data jabatan gagal diperbarui.')
    expect(wrapper.get('[role="dialog"]').text()).not.toContain('PDOException')

    await wrapper.get('[role="dialog"] form').trigger('submit')
    await nextTick()
    await wrapper.get('[role="dialog"] form').trigger('submit')
    expect(positionsService.update).toHaveBeenCalledTimes(3)

    resolveUpdate({ data: { id: 1, name: 'Admin IT' } })
    await flushPromises()

    expect(positionsService.update).toHaveBeenLastCalledWith(1, {
      name: 'Admin IT',
      description: null,
    })
    expect(toastMessages()).toContain('Data jabatan berhasil diperbarui.')
  })

  it('menghapus jabatan melalui konfirmasi, mencegah double delete, dan menangani penolakan 422 saat dipakai pegawai', async () => {
    let resolveDelete
    positionsService.remove
      .mockRejectedValueOnce({
        response: {
          status: 422,
          data: {
            message: 'Data jabatan tidak dapat dihapus karena sedang digunakan oleh data pegawai.',
          },
        },
      })
      .mockReturnValueOnce(new Promise((resolve) => (resolveDelete = resolve)))

    const wrapper = await mountView()

    await wrapper.get('button[aria-label="Hapus Administrator"]').trigger('click')
    await flushPromises()
    const confirmDialog = wrapper.get('[role="dialog"]')
    expect(confirmDialog.text()).toContain(
      'Apakah Anda yakin ingin menghapus jabatan “Administrator”?',
    )

    await findButtonByText(confirmDialog, 'Hapus').trigger('click')
    await flushPromises()
    expect(positionsService.remove).toHaveBeenCalledWith(1)
    expect(toastMessages()).toContain(
      'Data jabatan tidak dapat dihapus karena sedang digunakan oleh data pegawai.',
    )

    await wrapper.get('button[aria-label="Hapus Perawat"]').trigger('click')
    await flushPromises()
    const confirmBtn = findButtonByText(wrapper.get('[role="dialog"]'), 'Hapus')
    await confirmBtn.trigger('click')
    await nextTick()
    await confirmBtn.trigger('click')
    expect(positionsService.remove).toHaveBeenCalledTimes(2)

    resolveDelete({})
    await flushPromises()
    expect(toastMessages()).toContain('Data jabatan berhasil dihapus.')
  })
})
