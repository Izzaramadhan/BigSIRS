import { nextTick } from 'vue'
import { flushPromises, mount } from '@vue/test-utils'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import BuildingView from '../bed/BuildingView.vue'
import BaseConfirmDialog from '@/components/ui/BaseConfirmDialog.vue'
import BuildingFormModal from '@/components/master-data/buildings/BuildingFormModal.vue'
import { buildingsService } from '@/services/master-data/buildings.service'
import { useToast } from '@/composables/core/useToast'

vi.mock('@/services/master-data/buildings.service', () => ({
  buildingsService: {
    list: vi.fn(),
    get: vi.fn(),
    create: vi.fn(),
    update: vi.fn(),
    remove: vi.fn(),
    getBuildings: vi.fn(),
  },
}))

const buildings = [
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
]

const listResponse = (data = buildings, metaOverrides = {}) => ({
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
  const wrapper = mount(BuildingView, {
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

describe('BuildingView', () => {
  beforeEach(() => {
    vi.resetAllMocks()
    vi.useRealTimers()
    toast.clear()
    buildingsService.list.mockResolvedValue(listResponse())
  })

  afterEach(() => {
    vi.useRealTimers()
    document.body.innerHTML = ''
  })

  it('menampilkan header, breadcrumbs, tombol tambah, dan tabel 4 kolom', async () => {
    const wrapper = await mountView()

    expect(wrapper.get('h1').text()).toBe('Data Gedung')
    expect(wrapper.text()).toContain(
      'Kelola data gedung yang digunakan dalam pengaturan ruangan dan tempat tidur rumah sakit.',
    )

    const breadcrumbItems = wrapper.get('nav[aria-label="Breadcrumb"]').findAll('li')
    expect(breadcrumbItems.map((li) => li.text().replace('/', '').trim())).toEqual([
      'Dashboard',
      'Master Data',
      'Data Bed',
      'Data Gedung',
    ])

    expect(findButtonByText(wrapper, 'Tambah Gedung')).toBeDefined()
    expect(wrapper.findAll('thead th').map((th) => th.text())).toEqual([
      'No',
      'Nama Gedung',
      'Deskripsi',
      'Aksi',
    ])

    const [first, second] = tableCells(wrapper)
    expect(first.slice(0, 3)).toEqual(['1', 'Gedung Teratai', 'Gedung Ruang Operasi Bedah'])
    expect(second.slice(0, 3)).toEqual(['2', 'Gedung Mawar', '—'])
  })

  it('menampilkan loading state selama pengambilan data', async () => {
    let resolveList
    buildingsService.list.mockReturnValue(new Promise((resolve) => (resolveList = resolve)))

    const wrapper = mount(BuildingView, { global: { stubs: { Teleport: true } } })
    await flushPromises()

    expect(wrapper.get('tbody').attributes('aria-busy')).toBe('true')

    resolveList(listResponse())
    await flushPromises()

    expect(wrapper.get('tbody').attributes('aria-busy')).toBeUndefined()
    expect(wrapper.findAll('tbody tr')).toHaveLength(2)
  })

  it('menampilkan empty state bila belum ada data dan bila hasil pencarian kosong', async () => {
    buildingsService.list.mockResolvedValue(listResponse([]))
    const wrapper = await mountView()

    expect(wrapper.text()).toContain('Belum ada data gedung')

    vi.useFakeTimers()
    const searchInput = wrapper.get('input[aria-label="Cari gedung"]')
    await searchInput.setValue('Tidak Ada')
    await nextTick()
    vi.advanceTimersByTime(400)
    await flushPromises()

    expect(wrapper.text()).toContain('Gedung tidak ditemukan')
    expect(wrapper.text()).toContain('Tidak ada gedung yang cocok dengan “Tidak Ada”.')
  })

  it('mengirim parameter pencarian dan mendukung tombol muat ulang', async () => {
    vi.useFakeTimers()
    const wrapper = await mountView()

    const searchInput = wrapper.get('input[aria-label="Cari gedung"]')
    await searchInput.setValue('Teratai')
    vi.advanceTimersByTime(400)
    await flushPromises()

    expect(buildingsService.list).toHaveBeenLastCalledWith(
      expect.objectContaining({
        page: 1,
        search: 'Teratai',
      }),
    )

    await wrapper.get('button[aria-label="Muat ulang data"]').trigger('click')
    await flushPromises()
    expect(buildingsService.list).toHaveBeenCalledTimes(3)
  })

  it('mengabaikan respons pencarian lama (race condition)', async () => {
    vi.useFakeTimers()
    const wrapper = await mountView()

    let resolveSlow
    let resolveFast
    buildingsService.list
      .mockImplementationOnce(() => new Promise((resolve) => (resolveSlow = resolve)))
      .mockImplementationOnce(() => new Promise((resolve) => (resolveFast = resolve)))

    const searchInput = wrapper.get('input[aria-label="Cari gedung"]')
    await searchInput.setValue('Lama')
    await nextTick()
    vi.advanceTimersByTime(400)
    await nextTick()

    await searchInput.setValue('Baru')
    await nextTick()
    vi.advanceTimersByTime(400)
    await nextTick()

    // Respons request kedua tiba lebih dulu
    resolveFast(
      listResponse([{ id: 9, name: 'Gedung Baru', description: 'Hasil terbaru' }]),
    )
    await flushPromises()

    // Respons request pertama tiba belakangan dan harus diabaikan
    resolveSlow(
      listResponse([{ id: 8, name: 'Gedung Lama', description: 'Hasil usang' }]),
    )
    await flushPromises()

    const rows = tableCells(wrapper)
    expect(rows).toHaveLength(1)
    expect(rows[0][1]).toBe('Gedung Baru')
  })

  it('mendukung pagination dan nomor urut lintas halaman', async () => {
    buildingsService.list
      .mockResolvedValueOnce(
        listResponse(
          [{ id: 1, name: 'Gedung 1', description: null }],
          { current_page: 1, last_page: 2, per_page: 10, total: 12, from: 1, to: 10 },
        ),
      )
      .mockResolvedValueOnce(
        listResponse(
          [
            { id: 11, name: 'Gedung Halmahera', description: null },
            { id: 12, name: 'Gedung Irian', description: null },
          ],
          { current_page: 2, last_page: 2, per_page: 10, total: 12, from: 11, to: 12 },
        ),
      )

    const wrapper = await mountView()

    await wrapper.get('button[aria-label="Halaman berikutnya"]').trigger('click')
    await flushPromises()

    expect(buildingsService.list).toHaveBeenLastCalledWith(expect.objectContaining({ page: 2 }))
    const rows = tableCells(wrapper)
    expect(rows[0][0]).toBe('11')
    expect(rows[1][0]).toBe('12')
  })

  it('menampilkan error state aman bila daftar gagal dimuat dan dapat mencoba lagi', async () => {
    buildingsService.list.mockRejectedValueOnce({
      response: { status: 500, data: { message: 'SQLSTATE[HY000]: Connection refused' } },
    })

    const wrapper = await mountView()

    expect(wrapper.text()).toContain('Data gedung gagal dimuat')
    expect(wrapper.text()).not.toContain('SQLSTATE')

    await findButtonByText(wrapper, 'Coba lagi').trigger('click')
    await flushPromises()

    expect(wrapper.findAll('tbody tr')).toHaveLength(2)
  })

  it('menambah gedung baru, me-refresh daftar, dan menampilkan toast sukses', async () => {
    buildingsService.create.mockResolvedValue({
      data: { id: 3, name: 'Gedung Cempaka', description: 'Gedung Rawat Inap Kelas 1' },
    })

    const wrapper = await mountView()
    await findButtonByText(wrapper, 'Tambah Gedung').trigger('click')
    await flushPromises()

    const dialog = wrapper.get('[role="dialog"]')
    expect(dialog.text()).toContain('Tambah Gedung')

    await findControlByLabel(dialog, 'Nama Gedung').setValue('Gedung Cempaka')
    await findControlByLabel(dialog, 'Deskripsi').setValue('Gedung Rawat Inap Kelas 1')
    await dialog.get('form').trigger('submit')
    await flushPromises()

    expect(buildingsService.create).toHaveBeenCalledWith({
      name: 'Gedung Cempaka',
      description: 'Gedung Rawat Inap Kelas 1',
    })
    expect(buildingsService.list).toHaveBeenCalledTimes(2)
    expect(toastMessages()).toContain('Data gedung berhasil ditambahkan.')
    expect(wrapper.findComponent(BuildingFormModal).props('open')).toBe(false)
  })

  it('memuat data pada form edit, mengirim pembaruan, dan menampilkan toast sukses', async () => {
    buildingsService.update.mockResolvedValue({
      data: { id: 1, name: 'Gedung Teratai Revisi', description: 'Gedung Ruang Operasi Bedah' },
    })

    const wrapper = await mountView()
    await wrapper.get('button[aria-label="Edit Gedung Teratai"]').trigger('click')
    await flushPromises()

    const dialog = wrapper.get('[role="dialog"]')
    expect(dialog.text()).toContain('Edit Gedung')

    const nameInput = findControlByLabel(dialog, 'Nama Gedung')
    expect(nameInput.element.value).toBe('Gedung Teratai')

    await nameInput.setValue('Gedung Teratai Revisi')
    await dialog.get('form').trigger('submit')
    await flushPromises()

    expect(buildingsService.update).toHaveBeenCalledWith(1, {
      name: 'Gedung Teratai Revisi',
      description: 'Gedung Ruang Operasi Bedah',
    })
    expect(toastMessages()).toContain('Data gedung berhasil diperbarui.')
  })

  it('validasi 422 menampilkan error pada field, menjaga tabel tetap tampil, dan tidak memicu toast sukses', async () => {
    buildingsService.create.mockRejectedValue({
      response: {
        status: 422,
        data: {
          message: 'The given data was invalid.',
          errors: {
            name: ['Nama gedung sudah digunakan.'],
          },
        },
      },
    })

    const wrapper = await mountView()
    await findButtonByText(wrapper, 'Tambah Gedung').trigger('click')
    await flushPromises()

    const dialog = wrapper.get('[role="dialog"]')
    await findControlByLabel(dialog, 'Nama Gedung').setValue('Gedung Teratai')
    await dialog.get('form').trigger('submit')
    await flushPromises()

    const modal = wrapper.findComponent(BuildingFormModal)
    expect(buildingsService.create).toHaveBeenCalledTimes(1)
    expect(modal.props('open')).toBe(true)
    expect(modal.props('serverErrors')).toEqual({ name: 'Nama gedung sudah digunakan.' })
    expect(wrapper.get('[role="dialog"]').text()).toContain('Nama gedung sudah digunakan.')
    expect(wrapper.findAll('tbody tr')).toHaveLength(2)
    expect(toastMessages()).toContain('The given data was invalid.')
    expect(toastMessages()).not.toContain('Data gedung berhasil ditambahkan.')
  })

  it('validasi sisi klien mencegah submit bila nama gedung kosong', async () => {
    const wrapper = await mountView()
    await findButtonByText(wrapper, 'Tambah Gedung').trigger('click')
    await flushPromises()

    const dialog = wrapper.get('[role="dialog"]')
    await dialog.get('form').trigger('submit')
    await flushPromises()

    expect(wrapper.get('[role="dialog"]').text()).toContain('Nama gedung wajib diisi.')
    expect(buildingsService.create).not.toHaveBeenCalled()
  })

  it('mencegah double submit pada form tambah/edit selama request berjalan', async () => {
    let finishCreate
    buildingsService.create.mockReturnValue(new Promise((resolve) => (finishCreate = resolve)))

    const wrapper = await mountView()
    await findButtonByText(wrapper, 'Tambah Gedung').trigger('click')
    await flushPromises()

    const dialog = wrapper.get('[role="dialog"]')
    await findControlByLabel(dialog, 'Nama Gedung').setValue('Gedung Anggrek')
    await dialog.get('form').trigger('submit')
    await dialog.get('form').trigger('submit')
    await flushPromises()

    expect(buildingsService.create).toHaveBeenCalledTimes(1)
    const submitButton = findButtonByText(wrapper.get('[role="dialog"]'), 'Simpan')
    expect(submitButton.attributes('disabled')).toBeDefined()

    finishCreate({ data: { id: 4, name: 'Gedung Anggrek', description: null } })
    await flushPromises()
    expect(toastMessages()).toContain('Data gedung berhasil ditambahkan.')
  })

  it('menghapus gedung melalui BaseConfirmDialog dan mencegah double delete', async () => {
    let finishDelete
    buildingsService.remove.mockReturnValue(new Promise((resolve) => (finishDelete = resolve)))

    const wrapper = await mountView()
    await wrapper.get('button[aria-label="Hapus Gedung Teratai"]').trigger('click')
    await flushPromises()

    const confirmDialog = wrapper.findComponent(BaseConfirmDialog)
    expect(confirmDialog.props('open')).toBe(true)
    expect(confirmDialog.props('message')).toBe(
      'Apakah Anda yakin ingin menghapus gedung “Gedung Teratai”?',
    )

    await findButtonByText(wrapper, 'Hapus').trigger('click')
    await flushPromises()

    expect(confirmDialog.props('loading')).toBe(true)
    expect(findButtonByText(wrapper, 'Hapus').attributes('disabled')).toBeDefined()
    expect(buildingsService.remove).toHaveBeenCalledTimes(1)
    expect(buildingsService.remove).toHaveBeenCalledWith(1)

    finishDelete({})
    await flushPromises()
    expect(toastMessages()).toContain('Data gedung berhasil dihapus.')
  })

  it('menampilkan toast error saat hapus gagal tanpa menghilangkan tabel', async () => {
    buildingsService.remove.mockRejectedValue({
      response: {
        status: 422,
        data: { message: 'Gedung tidak dapat dihapus karena masih digunakan oleh data lantai.' },
      },
    })

    const wrapper = await mountView()
    await wrapper.get('button[aria-label="Hapus Gedung Teratai"]').trigger('click')
    await flushPromises()

    await findButtonByText(wrapper, 'Hapus').trigger('click')
    await flushPromises()

    expect(toastMessages()).toContain(
      'Gedung tidak dapat dihapus karena masih digunakan oleh data lantai.',
    )
    expect(wrapper.findAll('tbody tr')).toHaveLength(2)
  })
})
