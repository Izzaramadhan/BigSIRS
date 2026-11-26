import { nextTick } from 'vue'
import { flushPromises, mount } from '@vue/test-utils'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import EducationView from '../EducationView.vue'
import { useToast } from '@/composables/core/useToast'
import { useEducations } from '@/composables/useEducations'
import { educationsService } from '@/services/master-data/educations.service'

vi.mock('@/services/master-data/educations.service', async (importOriginal) => {
  const actual = await importOriginal()
  return {
    educationsService: {
      ...actual.educationsService,
      list: vi.fn(),
      get: vi.fn(),
      create: vi.fn(),
      update: vi.fn(),
      remove: vi.fn(),
    },
  }
})

const mockEducations = [
  { id: 1, name: 'SD', is_active: true },
  { id: 2, name: 'S1 Kedokteran Umum', is_active: true },
]

const listResponse = (data = mockEducations, metaOverrides = {}) => ({
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
  const wrapper = mount(EducationView, {
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

describe('EducationView', () => {
  beforeEach(() => {
    vi.resetAllMocks()
    vi.useRealTimers()
    toast.clear()
    educationsService.list.mockResolvedValue(listResponse())
  })

  afterEach(() => {
    vi.useRealTimers()
    document.body.innerHTML = ''
  })

  it('menampilkan header, breadcrumbs, tombol tambah, dan 3 kolom standar', async () => {
    const wrapper = await mountView()

    expect(wrapper.get('h1').text()).toBe('Pendidikan')
    expect(wrapper.text()).toContain('Kelola data dasar tingkat pendidikan.')

    const breadcrumbItems = wrapper.get('nav[aria-label="Breadcrumb"]').findAll('li')
    expect(breadcrumbItems.map((li) => li.text().replace('/', '').trim())).toEqual([
      'Dashboard',
      'Data Dasar',
      'Pendidikan',
    ])

    expect(findButtonByText(wrapper, 'Tambah Pendidikan')).toBeDefined()

    const headers = wrapper.findAll('thead th').map((th) => th.text())
    expect(headers).toEqual(['No', 'Pendidikan', 'Aksi'])

    const [first, second] = tableCells(wrapper)
    expect(first.slice(0, 2)).toEqual(['1', 'SD'])
    expect(second.slice(0, 2)).toEqual(['2', 'S1 Kedokteran Umum'])
  })

  it('menampilkan loading state dan empty state (data kosong maupun hasil pencarian kosong)', async () => {
    let resolveList
    educationsService.list.mockReturnValueOnce(new Promise((resolve) => (resolveList = resolve)))

    const wrapper = mount(EducationView, { global: { stubs: { Teleport: true } } })
    await flushPromises()
    expect(wrapper.get('tbody').attributes('aria-busy')).toBe('true')

    resolveList(listResponse([]))
    await flushPromises()
    expect(wrapper.get('tbody').attributes('aria-busy')).toBeUndefined()
    expect(wrapper.text()).toContain('Belum ada data pendidikan')

    vi.useFakeTimers()
    educationsService.list.mockResolvedValue(listResponse([]))
    await wrapper.get('input[aria-label="Cari tingkat pendidikan"]').setValue('Doktor')
    await nextTick()
    vi.advanceTimersByTime(400)
    await flushPromises()

    expect(wrapper.text()).toContain('Pendidikan tidak ditemukan')
    expect(wrapper.text()).toContain('Tidak ada tingkat pendidikan yang cocok dengan “Doktor”.')
  })

  it('mengirim pencarian API debounced, mendukung muat ulang, dan mengabaikan respons lama', async () => {
    vi.useFakeTimers()
    const wrapper = await mountView()

    await wrapper.get('input[aria-label="Cari tingkat pendidikan"]').setValue('S1')
    await nextTick()
    vi.advanceTimersByTime(400)
    await flushPromises()

    expect(educationsService.list).toHaveBeenLastCalledWith(
      expect.objectContaining({ page: 1, search: 'S1' }),
    )

    await wrapper.get('button[aria-label="Muat ulang data"]').trigger('click')
    await flushPromises()
    expect(educationsService.list).toHaveBeenCalledTimes(3)

    let resolveSlow
    let resolveFast
    educationsService.list
      .mockImplementationOnce(() => new Promise((resolve) => (resolveSlow = resolve)))
      .mockImplementationOnce(() => new Promise((resolve) => (resolveFast = resolve)))

    const searchInput = wrapper.get('input[aria-label="Cari tingkat pendidikan"]')
    await searchInput.setValue('Lama')
    await nextTick()
    vi.advanceTimersByTime(400)
    await nextTick()

    await searchInput.setValue('Baru')
    await nextTick()
    vi.advanceTimersByTime(400)
    await nextTick()

    resolveFast(listResponse([{ id: 9, name: 'S2 Kedokteran' }]))
    await flushPromises()

    resolveSlow(listResponse([{ id: 8, name: 'Pendidikan Usang' }]))
    await flushPromises()

    const rows = tableCells(wrapper)
    expect(rows).toHaveLength(1)
    expect(rows[0][1]).toBe('S2 Kedokteran')
  })

  it('mendukung pagination lintas halaman dan mundur halaman saat item terakhir dihapus', async () => {
    educationsService.list
      .mockResolvedValueOnce(
        listResponse(
          [mockEducations[0]],
          { current_page: 1, last_page: 2, per_page: 10, total: 11, from: 1, to: 10 },
        ),
      )
      .mockResolvedValueOnce(
        listResponse(
          [{ id: 11, name: 'Sp1 Bedah' }],
          { current_page: 2, last_page: 2, per_page: 10, total: 11, from: 11, to: 11 },
        ),
      )
      .mockResolvedValueOnce(
        listResponse(
          [mockEducations[0]],
          { current_page: 1, last_page: 1, per_page: 10, total: 10, from: 1, to: 10 },
        ),
      )
    educationsService.remove.mockResolvedValue({})

    const wrapper = await mountView()
    await wrapper.get('button[aria-label="Halaman berikutnya"]').trigger('click')
    await flushPromises()

    expect(educationsService.list).toHaveBeenLastCalledWith(expect.objectContaining({ page: 2 }))
    expect(tableCells(wrapper)[0][0]).toBe('11')

    await wrapper.get('button[aria-label="Hapus Sp1 Bedah"]').trigger('click')
    await flushPromises()
    await findButtonByText(wrapper.get('[role="dialog"]'), 'Hapus').trigger('click')
    await flushPromises()

    expect(educationsService.list).toHaveBeenLastCalledWith(expect.objectContaining({ page: 1 }))
  })

  it('mendukung default sorting dan setSort melalui useEducations()', async () => {
    const { setSort, sort, fetchItems } = useEducations()
    await fetchItems()

    expect(educationsService.list).toHaveBeenLastCalledWith(
      expect.objectContaining({ sort: 'id', direction: 'asc' }),
    )

    await setSort('name')
    expect(sort.column).toBe('name')
    expect(sort.direction).toBe('asc')
    expect(educationsService.list).toHaveBeenLastCalledWith(
      expect.objectContaining({ page: 1, sort: 'name', direction: 'asc' }),
    )
  })

  it('menampilkan error state aman tanpa SQLSTATE bila daftar gagal dimuat dan mendukung retry', async () => {
    educationsService.list.mockRejectedValueOnce({
      response: { status: 500, data: { message: 'SQLSTATE[HY000]: Connection refused' } },
    })

    const wrapper = await mountView()

    expect(wrapper.text()).toContain('Data pendidikan gagal dimuat')
    expect(wrapper.text()).not.toContain('SQLSTATE')

    await findButtonByText(wrapper, 'Coba lagi').trigger('click')
    await flushPromises()
    expect(wrapper.findAll('tbody tr')).toHaveLength(2)
  })

  it('memvalidasi form tambah dan menambah data pendidikan baru dengan notifikasi toast', async () => {
    educationsService.create.mockResolvedValue({ data: { id: 3, name: 'D3 Keperawatan' } })

    const wrapper = await mountView()
    await findButtonByText(wrapper, 'Tambah Pendidikan').trigger('click')
    await flushPromises()

    const dialog = wrapper.get('[role="dialog"]')
    await dialog.get('form').trigger('submit')
    await flushPromises()

    expect(dialog.text()).toContain('Nama pendidikan wajib diisi.')
    expect(educationsService.create).not.toHaveBeenCalled()

    await findControlByLabel(dialog, 'Nama Pendidikan').setValue('  D3   Keperawatan  ')
    await dialog.get('form').trigger('submit')
    await flushPromises()

    expect(educationsService.create).toHaveBeenCalledWith({ name: 'D3 Keperawatan' })
    expect(toastMessages()).toContain('Data pendidikan berhasil ditambahkan.')
    expect(wrapper.find('[role="dialog"]').exists()).toBe(false)
  })

  it('mengedit pendidikan, menangani error 422/500 dengan aman, dan mencegah double submit', async () => {
    let resolveUpdate
    educationsService.update
      .mockRejectedValueOnce({
        response: {
          status: 422,
          data: {
            message: 'The given data was invalid.',
            errors: { name: ['Nama Pendidikan sudah digunakan.'] },
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
    await wrapper.get('button[aria-label="Edit SD"]').trigger('click')
    await flushPromises()

    const dialog = wrapper.get('[role="dialog"]')
    expect(dialog.text()).toContain('Edit Pendidikan')
    expect(findControlByLabel(dialog, 'Nama Pendidikan').element.value).toBe('SD')

    // Validasi server 422 menjaga modal dan tabel tetap tampil
    await dialog.get('form').trigger('submit')
    await flushPromises()
    expect(wrapper.get('[role="dialog"]').text()).toContain('Nama Pendidikan sudah digunakan.')
    expect(wrapper.findAll('tbody tr')).toHaveLength(2)

    // Error 500 disanitasi di alert modal
    await findControlByLabel(wrapper.get('[role="dialog"]'), 'Nama Pendidikan').setValue('Sekolah Dasar')
    await wrapper.get('[role="dialog"] form').trigger('submit')
    await flushPromises()
    expect(wrapper.get('[role="dialog"]').text()).toContain('Data pendidikan gagal diperbarui.')
    expect(wrapper.get('[role="dialog"]').text()).not.toContain('PDOException')

    // Submit sukses + blokir double submit saat saving true
    await wrapper.get('[role="dialog"] form').trigger('submit')
    await nextTick()
    await wrapper.get('[role="dialog"] form').trigger('submit')
    expect(educationsService.update).toHaveBeenCalledTimes(3)

    resolveUpdate({ data: { id: 1, name: 'Sekolah Dasar' } })
    await flushPromises()

    expect(educationsService.update).toHaveBeenLastCalledWith(1, { name: 'Sekolah Dasar' })
    expect(toastMessages()).toContain('Data pendidikan berhasil diperbarui.')
  })

  it('menghapus pendidikan melalui konfirmasi, mencegah double delete, dan menangani konflik 409', async () => {
    let resolveDelete
    educationsService.remove
      .mockRejectedValueOnce({
        response: {
          status: 409,
          data: {
            message: 'Pendidikan tidak dapat dihapus karena sedang digunakan oleh data Pegawai/Dokter.',
          },
        },
      })
      .mockReturnValueOnce(new Promise((resolve) => (resolveDelete = resolve)))

    const wrapper = await mountView()

    // Konflik 409 saat digunakan oleh Pegawai/Dokter
    await wrapper.get('button[aria-label="Hapus SD"]').trigger('click')
    await flushPromises()
    const confirmDialog = wrapper.get('[role="dialog"]')
    expect(confirmDialog.text()).toContain('Apakah Anda yakin ingin menghapus pendidikan “SD”?')

    await findButtonByText(confirmDialog, 'Hapus').trigger('click')
    await flushPromises()
    expect(educationsService.remove).toHaveBeenCalledWith(1)
    expect(toastMessages()).toContain(
      'Pendidikan tidak dapat dihapus karena sedang digunakan oleh data Pegawai/Dokter.',
    )

    // Hapus berhasil + cegah double delete saat saving
    await wrapper.get('button[aria-label="Hapus S1 Kedokteran Umum"]').trigger('click')
    await flushPromises()
    const confirmBtn = findButtonByText(wrapper.get('[role="dialog"]'), 'Hapus')
    await confirmBtn.trigger('click')
    await nextTick()
    await confirmBtn.trigger('click')
    expect(educationsService.remove).toHaveBeenCalledTimes(2)

    resolveDelete({})
    await flushPromises()
    expect(toastMessages()).toContain('Data pendidikan berhasil dihapus.')
  })
})
