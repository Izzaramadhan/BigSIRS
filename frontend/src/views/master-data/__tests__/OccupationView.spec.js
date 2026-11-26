import { nextTick } from 'vue'
import { flushPromises, mount } from '@vue/test-utils'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import OccupationView from '../OccupationView.vue'
import { useToast } from '@/composables/core/useToast'
import { useOccupations } from '@/composables/useOccupations'
import { occupationsService } from '@/services/master-data/occupations.service'

vi.mock('@/services/master-data/occupations.service', async (importOriginal) => {
  const actual = await importOriginal()
  return {
    occupationsService: {
      ...actual.occupationsService,
      list: vi.fn(),
      get: vi.fn(),
      create: vi.fn(),
      update: vi.fn(),
      remove: vi.fn(),
    },
  }
})

const mockOccupations = [
  { id: 1, name: 'PNS', is_active: true },
  { id: 2, name: 'Wiraswasta', is_active: true },
]

const listResponse = (data = mockOccupations, metaOverrides = {}) => ({
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
  const wrapper = mount(OccupationView, {
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

describe('OccupationView', () => {
  beforeEach(() => {
    vi.resetAllMocks()
    vi.useRealTimers()
    toast.clear()
    occupationsService.list.mockResolvedValue(listResponse())
  })

  afterEach(() => {
    vi.useRealTimers()
    document.body.innerHTML = ''
  })

  it('menampilkan header, breadcrumbs, tombol tambah, dan 3 kolom standar', async () => {
    const wrapper = await mountView()

    expect(wrapper.get('h1').text()).toBe('Pekerjaan')
    expect(wrapper.text()).toContain('Kelola data dasar pekerjaan.')

    const breadcrumbItems = wrapper.get('nav[aria-label="Breadcrumb"]').findAll('li')
    expect(breadcrumbItems.map((li) => li.text().replace('/', '').trim())).toEqual([
      'Dashboard',
      'Data Dasar',
      'Pekerjaan',
    ])

    expect(findButtonByText(wrapper, 'Tambah Pekerjaan')).toBeDefined()

    const headers = wrapper.findAll('thead th').map((th) => th.text())
    expect(headers).toEqual(['No', 'Pekerjaan', 'Aksi'])

    const [first, second] = tableCells(wrapper)
    expect(first.slice(0, 2)).toEqual(['1', 'PNS'])
    expect(second.slice(0, 2)).toEqual(['2', 'Wiraswasta'])
  })

  it('menampilkan loading state dan empty state (data kosong maupun hasil pencarian kosong)', async () => {
    let resolveList
    occupationsService.list.mockReturnValueOnce(new Promise((resolve) => (resolveList = resolve)))

    const wrapper = mount(OccupationView, { global: { stubs: { Teleport: true } } })
    await flushPromises()
    expect(wrapper.get('tbody').attributes('aria-busy')).toBe('true')

    resolveList(listResponse([]))
    await flushPromises()
    expect(wrapper.get('tbody').attributes('aria-busy')).toBeUndefined()
    expect(wrapper.text()).toContain('Belum ada data pekerjaan')

    vi.useFakeTimers()
    occupationsService.list.mockResolvedValue(listResponse([]))
    await wrapper.get('input[aria-label="Cari pekerjaan"]').setValue('Nelayan')
    await nextTick()
    vi.advanceTimersByTime(400)
    await flushPromises()

    expect(wrapper.text()).toContain('Pekerjaan tidak ditemukan')
    expect(wrapper.text()).toContain('Tidak ada pekerjaan yang cocok dengan “Nelayan”.')
  })

  it('mengirim pencarian API debounced, mendukung muat ulang, dan mengabaikan respons lama', async () => {
    vi.useFakeTimers()
    const wrapper = await mountView()

    await wrapper.get('input[aria-label="Cari pekerjaan"]').setValue('PNS')
    await nextTick()
    vi.advanceTimersByTime(400)
    await flushPromises()

    expect(occupationsService.list).toHaveBeenLastCalledWith(
      expect.objectContaining({ page: 1, search: 'PNS' }),
    )

    await wrapper.get('button[aria-label="Muat ulang data"]').trigger('click')
    await flushPromises()
    expect(occupationsService.list).toHaveBeenCalledTimes(3)

    let resolveSlow
    let resolveFast
    occupationsService.list
      .mockImplementationOnce(() => new Promise((resolve) => (resolveSlow = resolve)))
      .mockImplementationOnce(() => new Promise((resolve) => (resolveFast = resolve)))

    const searchInput = wrapper.get('input[aria-label="Cari pekerjaan"]')
    await searchInput.setValue('Lama')
    await nextTick()
    vi.advanceTimersByTime(400)
    await nextTick()

    await searchInput.setValue('Baru')
    await nextTick()
    vi.advanceTimersByTime(400)
    await nextTick()

    resolveFast(listResponse([{ id: 9, name: 'Karyawan Swasta' }]))
    await flushPromises()

    resolveSlow(listResponse([{ id: 8, name: 'Pekerjaan Usang' }]))
    await flushPromises()

    const rows = tableCells(wrapper)
    expect(rows).toHaveLength(1)
    expect(rows[0][1]).toBe('Karyawan Swasta')
  })

  it('mendukung pagination lintas halaman dan mundur halaman saat item terakhir dihapus', async () => {
    occupationsService.list
      .mockResolvedValueOnce(
        listResponse(
          [mockOccupations[0]],
          { current_page: 1, last_page: 2, per_page: 10, total: 11, from: 1, to: 10 },
        ),
      )
      .mockResolvedValueOnce(
        listResponse(
          [{ id: 11, name: 'Petani' }],
          { current_page: 2, last_page: 2, per_page: 10, total: 11, from: 11, to: 11 },
        ),
      )
      .mockResolvedValueOnce(
        listResponse(
          [mockOccupations[0]],
          { current_page: 1, last_page: 1, per_page: 10, total: 10, from: 1, to: 10 },
        ),
      )
    occupationsService.remove.mockResolvedValue({})

    const wrapper = await mountView()
    await wrapper.get('button[aria-label="Halaman berikutnya"]').trigger('click')
    await flushPromises()

    expect(occupationsService.list).toHaveBeenLastCalledWith(expect.objectContaining({ page: 2 }))
    expect(tableCells(wrapper)[0][0]).toBe('11')

    await wrapper.get('button[aria-label="Hapus Petani"]').trigger('click')
    await flushPromises()
    await findButtonByText(wrapper.get('[role="dialog"]'), 'Hapus').trigger('click')
    await flushPromises()

    expect(occupationsService.list).toHaveBeenLastCalledWith(expect.objectContaining({ page: 1 }))
  })

  it('mendukung default sorting, setSort, dan kompatibilitas alias occupationsService', async () => {
    const { setSort, sort, fetchItems } = useOccupations()
    await fetchItems()

    expect(occupationsService.list).toHaveBeenLastCalledWith(
      expect.objectContaining({ sort: 'id', direction: 'asc' }),
    )

    await setSort('name')
    expect(sort.column).toBe('name')
    expect(sort.direction).toBe('asc')
    expect(occupationsService.list).toHaveBeenLastCalledWith(
      expect.objectContaining({ page: 1, sort: 'name', direction: 'asc' }),
    )

    expect(typeof occupationsService.getOccupations).toBe('function')
    expect(typeof occupationsService.getOccupation).toBe('function')
    expect(typeof occupationsService.createOccupation).toBe('function')
    expect(typeof occupationsService.updateOccupation).toBe('function')
    expect(typeof occupationsService.deleteOccupation).toBe('function')
  })

  it('menampilkan error state aman tanpa SQLSTATE bila daftar gagal dimuat dan mendukung retry', async () => {
    occupationsService.list.mockRejectedValueOnce({
      response: { status: 500, data: { message: 'SQLSTATE[HY000]: Connection refused' } },
    })

    const wrapper = await mountView()

    expect(wrapper.text()).toContain('Data pekerjaan gagal dimuat')
    expect(wrapper.text()).not.toContain('SQLSTATE')

    await findButtonByText(wrapper, 'Coba lagi').trigger('click')
    await flushPromises()
    expect(wrapper.findAll('tbody tr')).toHaveLength(2)
  })

  it('memvalidasi form tambah dan menambah data pekerjaan baru dengan notifikasi toast', async () => {
    occupationsService.create.mockResolvedValue({ data: { id: 3, name: 'TNI / Polri' } })

    const wrapper = await mountView()
    await findButtonByText(wrapper, 'Tambah Pekerjaan').trigger('click')
    await flushPromises()

    const dialog = wrapper.get('[role="dialog"]')
    await dialog.get('form').trigger('submit')
    await flushPromises()

    expect(dialog.text()).toContain('Nama pekerjaan wajib diisi.')
    expect(occupationsService.create).not.toHaveBeenCalled()

    await findControlByLabel(dialog, 'Nama Pekerjaan').setValue('  TNI  /  Polri  ')
    await dialog.get('form').trigger('submit')
    await flushPromises()

    expect(occupationsService.create).toHaveBeenCalledWith({ name: 'TNI / Polri' })
    expect(toastMessages()).toContain('Data pekerjaan berhasil ditambahkan.')
    expect(wrapper.find('[role="dialog"]').exists()).toBe(false)
  })

  it('mengedit pekerjaan, menangani error 422/500 dengan aman, dan mencegah double submit', async () => {
    let resolveUpdate
    occupationsService.update
      .mockRejectedValueOnce({
        response: {
          status: 422,
          data: {
            message: 'The given data was invalid.',
            errors: { name: ['Nama Pekerjaan sudah digunakan.'] },
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
    await wrapper.get('button[aria-label="Edit PNS"]').trigger('click')
    await flushPromises()

    const dialog = wrapper.get('[role="dialog"]')
    expect(dialog.text()).toContain('Edit Pekerjaan')
    expect(findControlByLabel(dialog, 'Nama Pekerjaan').element.value).toBe('PNS')

    await dialog.get('form').trigger('submit')
    await flushPromises()
    expect(wrapper.get('[role="dialog"]').text()).toContain('Nama Pekerjaan sudah digunakan.')
    expect(wrapper.findAll('tbody tr')).toHaveLength(2)

    await findControlByLabel(wrapper.get('[role="dialog"]'), 'Nama Pekerjaan').setValue('ASN')
    await wrapper.get('[role="dialog"] form').trigger('submit')
    await flushPromises()
    expect(wrapper.get('[role="dialog"]').text()).toContain('Data pekerjaan gagal diperbarui.')
    expect(wrapper.get('[role="dialog"]').text()).not.toContain('PDOException')

    await wrapper.get('[role="dialog"] form').trigger('submit')
    await nextTick()
    await wrapper.get('[role="dialog"] form').trigger('submit')
    expect(occupationsService.update).toHaveBeenCalledTimes(3)

    resolveUpdate({ data: { id: 1, name: 'ASN' } })
    await flushPromises()

    expect(occupationsService.update).toHaveBeenLastCalledWith(1, { name: 'ASN' })
    expect(toastMessages()).toContain('Data pekerjaan berhasil diperbarui.')
  })

  it('menghapus pekerjaan melalui konfirmasi, mencegah double delete, dan menangani konflik 409', async () => {
    let resolveDelete
    occupationsService.remove
      .mockRejectedValueOnce({
        response: {
          status: 409,
          data: {
            message:
              'Pekerjaan tidak dapat dihapus karena sedang digunakan oleh data Pegawai/Dokter.',
          },
        },
      })
      .mockReturnValueOnce(new Promise((resolve) => (resolveDelete = resolve)))

    const wrapper = await mountView()

    await wrapper.get('button[aria-label="Hapus PNS"]').trigger('click')
    await flushPromises()
    const confirmDialog = wrapper.get('[role="dialog"]')
    expect(confirmDialog.text()).toContain('Apakah Anda yakin ingin menghapus pekerjaan “PNS”?')

    await findButtonByText(confirmDialog, 'Hapus').trigger('click')
    await flushPromises()
    expect(occupationsService.remove).toHaveBeenCalledWith(1)
    expect(toastMessages()).toContain(
      'Pekerjaan tidak dapat dihapus karena sedang digunakan oleh data Pegawai/Dokter.',
    )

    await wrapper.get('button[aria-label="Hapus Wiraswasta"]').trigger('click')
    await flushPromises()
    const confirmBtn = findButtonByText(wrapper.get('[role="dialog"]'), 'Hapus')
    await confirmBtn.trigger('click')
    await nextTick()
    await confirmBtn.trigger('click')
    expect(occupationsService.remove).toHaveBeenCalledTimes(2)

    resolveDelete({})
    await flushPromises()
    expect(toastMessages()).toContain('Data pekerjaan berhasil dihapus.')
  })
})
