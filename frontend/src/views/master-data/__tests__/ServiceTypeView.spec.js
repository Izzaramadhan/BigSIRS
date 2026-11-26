import { flushPromises, mount } from '@vue/test-utils'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import ServiceTypeView from '../ServiceTypeView.vue'
import { useToast } from '@/composables/core/useToast'
import serviceTypeService from '@/services/serviceType.service'

vi.mock('@/services/serviceType.service', () => {
  const mockService = {
    list: vi.fn(),
    create: vi.fn(),
    update: vi.fn(),
    remove: vi.fn(),
  }
  return {
    serviceTypeService: mockService,
    default: mockService,
  }
})

const mockListResponse = {
  data: [
    { id: 1, name: 'Penyakit Dalam', is_active: true },
    { id: 2, name: 'Kesehatan Anak', is_active: true },
  ],
  meta: {
    current_page: 1,
    last_page: 2,
    per_page: 10,
    total: 12,
    from: 1,
    to: 10,
  },
}

const mockPageTwoResponse = {
  data: [
    { id: 11, name: 'Rawat Intensif', is_active: true },
  ],
  meta: {
    current_page: 2,
    last_page: 2,
    per_page: 10,
    total: 11,
    from: 11,
    to: 11,
  },
}

const mockEmptyResponse = {
  data: [],
  meta: {
    current_page: 1,
    last_page: 1,
    per_page: 10,
    total: 0,
    from: 0,
    to: 0,
  },
}

const toast = useToast()
const toastEntries = () => toast.toasts.value

async function mountView() {
  const wrapper = mount(ServiceTypeView, {
    global: {
      stubs: {
        Teleport: true,
      },
    },
  })
  await flushPromises()
  return wrapper
}

function findButtonByText(wrapper, text) {
  return wrapper.findAll('button').find((button) => button.text().trim() === text)
}

describe('ServiceTypeView', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    toast.clear()
    serviceTypeService.list.mockResolvedValue(mockListResponse)
  })

  afterEach(() => {
    vi.useRealTimers()
  })

  it('fetches paginated data and renders 3 standardized columns without status column', async () => {
    const wrapper = await mountView()

    expect(serviceTypeService.list).toHaveBeenCalledWith({
      page: 1,
      per_page: 10,
      sort: 'name',
      direction: 'asc',
    })
    expect(wrapper.get('h1').text()).toBe('Jenis Layanan')

    const headers = wrapper.findAll('thead th').map((th) => th.text().trim())
    expect(headers).toEqual(['No', 'Jenis Layanan', 'Aksi'])
    expect(wrapper.text()).toContain('Penyakit Dalam')
    expect(wrapper.text()).toContain('Kesehatan Anak')
  })

  it('supports pagination, manual refresh, debounced search, and race condition guard', async () => {
    vi.useFakeTimers()
    const wrapper = await mountView()

    // Pagination -> page 2
    serviceTypeService.list.mockResolvedValueOnce(mockPageTwoResponse)
    await wrapper.get('button[aria-label="Halaman berikutnya"]').trigger('click')
    await flushPromises()

    expect(serviceTypeService.list).toHaveBeenLastCalledWith(
      expect.objectContaining({ page: 2 }),
    )
    expect(wrapper.get('tbody tr td:first-child').text()).toBe('11')

    // Refresh
    serviceTypeService.list.mockResolvedValueOnce(mockListResponse)
    await wrapper.get('button[aria-label="Muat ulang data"]').trigger('click')
    await flushPromises()
    expect(serviceTypeService.list).toHaveBeenCalledTimes(3)

    // Race condition during search: first slow response ignored when second resolves faster
    let resolveSlow
    const slowPromise = new Promise((resolve) => {
      resolveSlow = resolve
    })
    serviceTypeService.list.mockReturnValueOnce(slowPromise)

    const searchInput = wrapper.get('input[placeholder="Cari jenis layanan..."]')
    await searchInput.setValue('Lambat')
    await vi.advanceTimersByTimeAsync(400)

    serviceTypeService.list.mockResolvedValueOnce(mockEmptyResponse)
    await searchInput.setValue('TidakAda')
    await vi.advanceTimersByTimeAsync(400)
    await flushPromises()

    expect(wrapper.text()).toContain('Jenis layanan tidak ditemukan')

    resolveSlow(mockListResponse)
    await flushPromises()
    expect(wrapper.text()).toContain('Jenis layanan tidak ditemukan')

    serviceTypeService.list.mockResolvedValueOnce(mockListResponse)
    await findButtonByText(wrapper, 'Reset Pencarian').trigger('click')
    await flushPromises()
    expect(wrapper.text()).toContain('Penyakit Dalam')
  })

  it('handles load error safely with retry and empty state when database has no records', async () => {
    serviceTypeService.list.mockRejectedValueOnce({
      response: { status: 500, data: { message: 'SQLSTATE[42S02]: missing table' } },
    })
    const wrapper = await mountView()

    expect(wrapper.text()).toContain('Gagal memuat data jenis layanan')
    expect(wrapper.text()).toContain('Data jenis layanan gagal dimuat.')
    expect(wrapper.text()).not.toContain('SQLSTATE')

    serviceTypeService.list.mockResolvedValueOnce(mockEmptyResponse)
    await findButtonByText(wrapper, 'Coba Lagi').trigger('click')
    await flushPromises()

    expect(wrapper.text()).toContain('Belum ada data jenis layanan')
  })

  it('performs create, update, and 422 validation error handling without losing table data', async () => {
    serviceTypeService.create
      .mockRejectedValueOnce({
        response: {
          status: 422,
          data: {
            message: 'Nama jenis layanan sudah digunakan.',
            errors: { name: ['Nama jenis layanan sudah digunakan.'] },
          },
        },
      })
      .mockResolvedValueOnce({ data: { id: 3, name: 'IGD Terpadu' } })
    serviceTypeService.update.mockResolvedValueOnce({
      data: { id: 1, name: 'Penyakit Dalam V2' },
    })

    const wrapper = await mountView()

    // Create 422 validation failure keeps table intact
    await findButtonByText(wrapper, 'Tambah Jenis Layanan').trigger('click')
    await wrapper.get('input#name').setValue('Penyakit Dalam')
    await wrapper.get('form').trigger('submit.prevent')
    await flushPromises()

    expect(wrapper.text()).toContain('Nama jenis layanan sudah digunakan.')
    expect(toastEntries().at(-1)?.tone).toBe('danger')

    // Valid Create
    await wrapper.get('input#name').setValue('  IGD   Terpadu ')
    await wrapper.get('form').trigger('submit.prevent')
    await flushPromises()

    expect(serviceTypeService.create).toHaveBeenLastCalledWith({ name: 'IGD Terpadu' })
    expect(toastEntries().at(-1)?.tone).toBe('success')
    expect(toastEntries().at(-1)?.message).toBe('Data jenis layanan berhasil ditambahkan.')

    // Edit
    await wrapper.get('button[aria-label="Edit Penyakit Dalam"]').trigger('click')
    expect(wrapper.get('input#name').element.value).toBe('Penyakit Dalam')
    await wrapper.get('input#name').setValue('Penyakit Dalam V2')
    await wrapper.get('form').trigger('submit.prevent')
    await flushPromises()

    expect(serviceTypeService.update).toHaveBeenCalledWith(1, { name: 'Penyakit Dalam V2' })
    expect(toastEntries().at(-1)?.message).toBe('Data jenis layanan berhasil diperbarui.')
  })

  it('handles delete success, deletes last row on page > 1 properly, and shows usage conflict error', async () => {
    serviceTypeService.list
      .mockResolvedValueOnce(mockPageTwoResponse)
      .mockResolvedValueOnce(mockListResponse)
    serviceTypeService.remove.mockResolvedValueOnce(null)

    const wrapper = await mountView()

    await wrapper.get('button[aria-label="Hapus Rawat Intensif"]').trigger('click')
    await findButtonByText(wrapper, 'Ya, Hapus').trigger('click')
    await flushPromises()

    expect(serviceTypeService.remove).toHaveBeenCalledWith(11)
    expect(serviceTypeService.list).toHaveBeenLastCalledWith(
      expect.objectContaining({ page: 1 }),
    )
    expect(toastEntries().at(-1)?.tone).toBe('success')
    expect(toastEntries().at(-1)?.message).toBe('Data jenis layanan berhasil dihapus.')

    // Conflict (422 ValidationException when used in trx_visit)
    serviceTypeService.remove.mockRejectedValueOnce({
      response: {
        status: 422,
        data: {
          message: 'Jenis layanan tidak dapat dihapus karena sudah digunakan pada transaksi kunjungan.',
          errors: {
            id: ['Jenis layanan tidak dapat dihapus karena sudah digunakan pada transaksi kunjungan.'],
          },
        },
      },
    })

    await wrapper.get('button[aria-label="Hapus Penyakit Dalam"]').trigger('click')
    await findButtonByText(wrapper, 'Ya, Hapus').trigger('click')
    await flushPromises()

    expect(toastEntries().at(-1)?.tone).toBe('danger')
    expect(toastEntries().at(-1)?.message).toBe(
      'Jenis layanan tidak dapat dihapus karena sudah digunakan pada transaksi kunjungan.',
    )
  })
})
