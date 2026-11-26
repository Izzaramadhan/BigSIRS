import { flushPromises, mount } from '@vue/test-utils'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import DietTypeView from '../DietTypeView.vue'
import { useToast } from '@/composables/core/useToast'
import dietTypeService from '@/services/dietType.service'

vi.mock('@/services/dietType.service', () => {
  const mockService = {
    list: vi.fn(),
    create: vi.fn(),
    update: vi.fn(),
    remove: vi.fn(),
  }
  return {
    dietTypeService: mockService,
    default: mockService,
  }
})

const mockListResponse = {
  data: [
    { id: 1, name: 'Diet Jantung', description: 'Rendah lemak dan garam', is_active: true },
    { id: 2, name: 'Diet Lunak', description: null, is_active: true },
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
    { id: 11, name: 'Diet Tinggi Protein', description: 'Pemulihan pasca operasi', is_active: true },
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
  const wrapper = mount(DietTypeView, {
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

describe('DietTypeView', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    toast.clear()
    dietTypeService.list.mockResolvedValue(mockListResponse)
  })

  afterEach(() => {
    vi.useRealTimers()
  })

  it('fetches paginated diet types on mount and renders 4 exact columns without Status column', async () => {
    const wrapper = await mountView()

    expect(dietTypeService.list).toHaveBeenCalledWith({
      page: 1,
      per_page: 10,
      sort: 'name',
      direction: 'asc',
    })
    expect(wrapper.get('h1').text()).toBe('Asuhan Gizi')

    const headers = wrapper.findAll('thead th').map((th) => th.text().trim())
    expect(headers).toEqual(['No', 'Asuhan Gizi', 'Deskripsi', 'Aksi'])
    expect(headers).not.toContain('Status')

    expect(wrapper.text()).toContain('Diet Jantung')
    expect(wrapper.text()).toContain('Rendah lemak dan garam')
    expect(wrapper.text()).toContain('Diet Lunak')
    expect(wrapper.text()).toContain('—')
  })

  it('supports pagination offset numbering, manual refresh, debounced search, and race condition guard', async () => {
    vi.useFakeTimers()
    const wrapper = await mountView()

    expect(wrapper.get('tbody tr td:first-child').text()).toBe('1')
    expect(wrapper.text()).toContain('Menampilkan 1–2 dari 12 data')

    dietTypeService.list.mockResolvedValueOnce(mockPageTwoResponse)
    await wrapper.get('button[aria-label="Halaman berikutnya"]').trigger('click')
    await flushPromises()

    expect(dietTypeService.list).toHaveBeenLastCalledWith(
      expect.objectContaining({ page: 2, per_page: 10 }),
    )
    expect(wrapper.get('tbody tr td:first-child').text()).toBe('11')
    expect(wrapper.text()).toContain('Diet Tinggi Protein')

    dietTypeService.list.mockResolvedValueOnce(mockListResponse)
    await wrapper.get('button[aria-label="Muat ulang data"]').trigger('click')
    await flushPromises()
    expect(dietTypeService.list).toHaveBeenCalledTimes(3)

    // Simulasi race condition: page 2 lambat, user mengetik pencarian yg resolve lebih cepat
    let resolveSlow
    const slowPromise = new Promise((resolve) => {
      resolveSlow = resolve
    })
    dietTypeService.list.mockReturnValueOnce(slowPromise)
    await wrapper.get('button[aria-label="Halaman berikutnya"]').trigger('click')

    dietTypeService.list.mockResolvedValueOnce(mockEmptyResponse)
    await wrapper.get('input[type="search"]').setValue('TidakAda')
    await vi.advanceTimersByTimeAsync(450)
    await flushPromises()

    expect(wrapper.text()).toContain('Asuhan gizi tidak ditemukan')

    resolveSlow(mockPageTwoResponse)
    await flushPromises()
    expect(wrapper.text()).toContain('Asuhan gizi tidak ditemukan')
    expect(wrapper.text()).not.toContain('Diet Tinggi Protein')

    dietTypeService.list.mockResolvedValueOnce(mockListResponse)
    await findButtonByText(wrapper, 'Reset Pencarian').trigger('click')
    await flushPromises()
    expect(wrapper.text()).toContain('Diet Jantung')
  })

  it('sanitizes load error 5xx with retry button and renders empty state when no records exist', async () => {
    dietTypeService.list.mockRejectedValueOnce({
      response: { status: 500, data: { message: 'SQLSTATE[42S02]: table missing' } },
    })
    const wrapper = await mountView()

    expect(wrapper.text()).toContain('Gagal memuat data asuhan gizi')
    expect(wrapper.text()).toContain('Data asuhan gizi gagal dimuat.')
    expect(wrapper.text()).not.toContain('SQLSTATE')

    dietTypeService.list.mockResolvedValueOnce(mockEmptyResponse)
    await findButtonByText(wrapper, 'Coba Lagi').trigger('click')
    await flushPromises()

    expect(wrapper.text()).toContain('Belum ada data asuhan gizi')
  })

  it('performs create, update, and maps 422 validation errors without losing table rows', async () => {
    dietTypeService.create
      .mockRejectedValueOnce({
        response: {
          status: 422,
          data: {
            message: 'Nama asuhan gizi sudah digunakan.',
            errors: { name: ['Nama asuhan gizi sudah digunakan.'] },
          },
        },
      })
      .mockResolvedValueOnce({ data: { id: 3, name: 'Diet Diabetes', description: 'Rendah gula' } })
    dietTypeService.update.mockResolvedValueOnce({
      data: { id: 1, name: 'Diet Jantung V2', description: null },
    })

    const wrapper = await mountView()

    await findButtonByText(wrapper, 'Tambah Asuhan Gizi').trigger('click')
    await wrapper.get('input#name').setValue('Diet Jantung')
    await wrapper.get('form').trigger('submit.prevent')
    await flushPromises()

    expect(wrapper.text()).toContain('Nama asuhan gizi sudah digunakan.')
    expect(wrapper.text()).toContain('Diet Jantung')
    expect(toastEntries().at(-1)?.tone).toBe('danger')

    await wrapper.get('input#name').setValue('Diet Diabetes')
    await wrapper.get('textarea#description').setValue('Rendah gula')
    await wrapper.get('form').trigger('submit.prevent')
    await flushPromises()

    expect(dietTypeService.create).toHaveBeenLastCalledWith({
      name: 'Diet Diabetes',
      description: 'Rendah gula',
    })
    expect(toastEntries().at(-1)?.tone).toBe('success')
    expect(toastEntries().at(-1)?.message).toBe('Data asuhan gizi berhasil ditambahkan.')

    await wrapper.get('button[aria-label="Edit Diet Jantung"]').trigger('click')
    await wrapper.get('input#name').setValue('Diet Jantung V2')
    await wrapper.get('textarea#description').setValue('')
    await wrapper.get('form').trigger('submit.prevent')
    await flushPromises()

    expect(dietTypeService.update).toHaveBeenCalledWith(1, {
      name: 'Diet Jantung V2',
      description: null,
    })
    expect(toastEntries().at(-1)?.message).toBe('Data asuhan gizi berhasil diperbarui.')
  })

  it('handles delete success, steps back from last row on page > 1, and shows delete error safely', async () => {
    dietTypeService.list
      .mockResolvedValueOnce(mockPageTwoResponse)
      .mockResolvedValueOnce(mockListResponse)
    dietTypeService.remove.mockResolvedValueOnce({ message: 'Asuhan gizi berhasil dihapus' })

    const wrapper = await mountView()

    await wrapper.get('button[aria-label="Hapus Diet Tinggi Protein"]').trigger('click')
    await findButtonByText(wrapper, 'Ya, Hapus').trigger('click')
    await flushPromises()

    expect(dietTypeService.remove).toHaveBeenCalledWith(11)
    expect(dietTypeService.list).toHaveBeenLastCalledWith(
      expect.objectContaining({ page: 1 }),
    )
    expect(toastEntries().at(-1)?.tone).toBe('success')
    expect(toastEntries().at(-1)?.message).toBe('Data asuhan gizi berhasil dihapus.')

    dietTypeService.remove.mockRejectedValueOnce({
      response: {
        status: 409,
        data: { message: 'Data asuhan gizi masih digunakan.' },
      },
    })

    await wrapper.get('button[aria-label="Hapus Diet Jantung"]').trigger('click')
    await findButtonByText(wrapper, 'Ya, Hapus').trigger('click')
    await flushPromises()

    expect(toastEntries().at(-1)?.tone).toBe('danger')
    expect(toastEntries().at(-1)?.message).toBe('Data asuhan gizi masih digunakan.')
  })
})
