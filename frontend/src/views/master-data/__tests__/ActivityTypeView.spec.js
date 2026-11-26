import { flushPromises, mount } from '@vue/test-utils'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import ActivityTypeView from '../ActivityTypeView.vue'
import { useToast } from '@/composables/core/useToast'
import activityTypeService from '@/services/activityType.service'

vi.mock('@/services/activityType.service', () => {
  const mockService = {
    list: vi.fn(),
    create: vi.fn(),
    update: vi.fn(),
    remove: vi.fn(),
    lookup: vi.fn(),
  }
  return {
    activityTypeService: mockService,
    default: mockService,
  }
})

const mockListResponse = {
  data: [
    { id: 1, name: 'Pelayanan Rawat Jalan', parent_id: null, parent: null, is_active: true },
    {
      id: 2,
      name: 'Penyakit Dalam',
      parent_id: 1,
      parent: { id: 1, name: 'Pelayanan Rawat Jalan' },
      is_active: true,
    },
  ],
  meta: {
    current_page: 1,
    last_page: 2,
    per_page: 10,
    total: 12,
    from: 1,
    to: 2,
  },
}

const mockPageTwoResponse = {
  data: [
    { id: 11, name: 'Kesehatan Anak', parent_id: null, parent: null, is_active: true },
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
  const wrapper = mount(ActivityTypeView, {
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

describe('ActivityTypeView', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    toast.clear()
    activityTypeService.list.mockResolvedValue(mockListResponse)
    activityTypeService.lookup.mockResolvedValue({
      data: [{ id: 1, name: 'Pelayanan Rawat Jalan' }],
    })
  })

  afterEach(() => {
    vi.useRealTimers()
  })

  it('fetches paginated activity types on mount and renders 4 exact columns with child indentation', async () => {
    const wrapper = await mountView()

    expect(activityTypeService.list).toHaveBeenCalledWith({
      page: 1,
      per_page: 10,
      sort: 'name',
      direction: 'asc',
    })
    expect(wrapper.get('h1').text()).toBe('Jenis Kegiatan')

    const headers = wrapper.findAll('thead th').map((th) => th.text().trim())
    expect(headers).toEqual(['No', 'Nama Jenis Kegiatan', 'Induk', 'Aksi'])

    const rows = wrapper.findAll('tbody tr')
    expect(rows[0].text()).toContain('Pelayanan Rawat Jalan')
    expect(rows[0].text()).toContain('—')
    expect(rows[1].text()).toContain('Penyakit Dalam')
    expect(rows[1].find('span.pl-5').exists()).toBe(true)
  })

  it('supports pagination offset numbering, manual refresh, debounced search, and race condition guard', async () => {
    vi.useFakeTimers()
    const wrapper = await mountView()

    expect(wrapper.get('tbody tr td:first-child').text()).toBe('1')
    expect(wrapper.text()).toContain('Menampilkan 1–2 dari 12 data')

    activityTypeService.list.mockResolvedValueOnce(mockPageTwoResponse)
    await wrapper.get('button[aria-label="Halaman berikutnya"]').trigger('click')
    await flushPromises()

    expect(activityTypeService.list).toHaveBeenLastCalledWith(
      expect.objectContaining({ page: 2, per_page: 10 }),
    )
    expect(wrapper.get('tbody tr td:first-child').text()).toBe('11')
    expect(wrapper.text()).toContain('Kesehatan Anak')

    activityTypeService.list.mockResolvedValueOnce(mockListResponse)
    await wrapper.get('button[aria-label="Muat ulang data"]').trigger('click')
    await flushPromises()
    expect(activityTypeService.list).toHaveBeenCalledTimes(3)

    // Simulasi race condition: page 2 lambat, user mengetik pencarian yg resolve lebih cepat
    let resolveSlow
    const slowPromise = new Promise((resolve) => {
      resolveSlow = resolve
    })
    activityTypeService.list.mockReturnValueOnce(slowPromise)
    await wrapper.get('button[aria-label="Halaman berikutnya"]').trigger('click')

    activityTypeService.list.mockResolvedValueOnce(mockEmptyResponse)
    await wrapper.get('input[type="search"]').setValue('TidakAda')
    await vi.advanceTimersByTimeAsync(450)
    await flushPromises()

    expect(wrapper.text()).toContain('Jenis kegiatan tidak ditemukan')

    resolveSlow(mockPageTwoResponse)
    await flushPromises()
    expect(wrapper.text()).toContain('Jenis kegiatan tidak ditemukan')
    expect(wrapper.text()).not.toContain('Kesehatan Anak')

    activityTypeService.list.mockResolvedValueOnce(mockListResponse)
    await findButtonByText(wrapper, 'Reset Pencarian').trigger('click')
    await flushPromises()
    expect(wrapper.text()).toContain('Pelayanan Rawat Jalan')
  })

  it('sanitizes load error 5xx with retry button and renders empty state when no records exist', async () => {
    activityTypeService.list.mockRejectedValueOnce({
      response: { status: 500, data: { message: 'SQLSTATE[42S02]: table missing' } },
    })
    const wrapper = await mountView()

    expect(wrapper.text()).toContain('Gagal memuat data jenis kegiatan')
    expect(wrapper.text()).toContain('Data jenis kegiatan gagal dimuat.')
    expect(wrapper.text()).not.toContain('SQLSTATE')

    activityTypeService.list.mockResolvedValueOnce(mockEmptyResponse)
    await findButtonByText(wrapper, 'Coba Lagi').trigger('click')
    await flushPromises()

    expect(wrapper.text()).toContain('Belum ada data jenis kegiatan')
  })

  it('performs create, update, and maps 422 validation errors without losing table rows', async () => {
    activityTypeService.create
      .mockRejectedValueOnce({
        response: {
          status: 422,
          data: {
            message: 'Nama jenis kegiatan sudah digunakan.',
            errors: { name: ['Nama jenis kegiatan sudah digunakan.'] },
          },
        },
      })
      .mockResolvedValueOnce({ data: { id: 3, name: 'Radiologi Umum', parent_id: 1 } })
    activityTypeService.update.mockResolvedValueOnce({
      data: { id: 2, name: 'Penyakit Dalam V2', parent_id: null },
    })

    const wrapper = await mountView()

    await findButtonByText(wrapper, 'Tambah Jenis Kegiatan').trigger('click')
    await flushPromises()
    await wrapper.get('input#name').setValue('Penyakit Dalam')
    await wrapper.get('form').trigger('submit.prevent')
    await flushPromises()

    expect(wrapper.text()).toContain('Nama jenis kegiatan sudah digunakan.')
    expect(wrapper.text()).toContain('Pelayanan Rawat Jalan')
    expect(toastEntries().at(-1)?.tone).toBe('danger')

    await wrapper.get('input#name').setValue('Radiologi Umum')
    await wrapper.get('select#parent_id').setValue('1')
    await wrapper.get('form').trigger('submit.prevent')
    await flushPromises()

    expect(activityTypeService.create).toHaveBeenLastCalledWith({
      name: 'Radiologi Umum',
      parent_id: 1,
    })
    expect(toastEntries().at(-1)?.tone).toBe('success')
    expect(toastEntries().at(-1)?.message).toBe('Data jenis kegiatan berhasil ditambahkan.')

    await wrapper.get('button[aria-label="Edit Penyakit Dalam"]').trigger('click')
    await flushPromises()
    await wrapper.get('input#name').setValue('Penyakit Dalam V2')
    await wrapper.get('select#parent_id').setValue('')
    await wrapper.get('form').trigger('submit.prevent')
    await flushPromises()

    expect(activityTypeService.update).toHaveBeenCalledWith(2, {
      name: 'Penyakit Dalam V2',
      parent_id: null,
    })
    expect(toastEntries().at(-1)?.message).toBe('Data jenis kegiatan berhasil diperbarui.')
  })

  it('handles delete success with page step-back and displays 422 parent-in-use guard cleanly', async () => {
    activityTypeService.list
      .mockResolvedValueOnce(mockPageTwoResponse)
      .mockResolvedValueOnce(mockListResponse)
    activityTypeService.remove.mockResolvedValueOnce({ message: 'Jenis kegiatan berhasil dihapus' })

    const wrapper = await mountView()

    await wrapper.get('button[aria-label="Hapus Kesehatan Anak"]').trigger('click')
    await findButtonByText(wrapper, 'Ya, Hapus').trigger('click')
    await flushPromises()

    expect(activityTypeService.remove).toHaveBeenCalledWith(11)
    expect(activityTypeService.list).toHaveBeenLastCalledWith(
      expect.objectContaining({ page: 1 }),
    )
    expect(toastEntries().at(-1)?.tone).toBe('success')
    expect(toastEntries().at(-1)?.message).toBe('Data jenis kegiatan berhasil dihapus.')

    activityTypeService.remove.mockRejectedValueOnce({
      response: {
        status: 422,
        data: {
          message: 'The given data was invalid.',
          errors: {
            id: ['Jenis kegiatan ini masih digunakan sebagai induk. Pindahkan atau hapus data turunannya terlebih dahulu.'],
          },
        },
      },
    })

    await wrapper.get('button[aria-label="Hapus Pelayanan Rawat Jalan"]').trigger('click')
    await findButtonByText(wrapper, 'Ya, Hapus').trigger('click')
    await flushPromises()

    expect(toastEntries().at(-1)?.tone).toBe('danger')
    expect(toastEntries().at(-1)?.message).toBe(
      'Jenis kegiatan ini masih digunakan sebagai induk. Pindahkan atau hapus data turunannya terlebih dahulu.',
    )
  })
})
