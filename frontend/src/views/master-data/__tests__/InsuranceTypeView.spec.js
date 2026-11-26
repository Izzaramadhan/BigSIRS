import { flushPromises, mount } from '@vue/test-utils'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import InsuranceTypeView from '../InsuranceTypeView.vue'
import { useToast } from '@/composables/core/useToast'
import { guarantorsService } from '@/services/master-data/guarantors.service'

vi.mock('@/services/master-data/guarantors.service', () => {
  const mockService = {
    list: vi.fn(),
    create: vi.fn(),
    update: vi.fn(),
    remove: vi.fn(),
  }
  return {
    guarantorsService: mockService,
    default: mockService,
  }
})

const mockListResponse = {
  data: [
    { id: 1, code: null, type: 'self_pay', name: 'UMUM', is_government: false, inacbg_id: null },
    { id: 2, code: 'JKN', type: 'bpjs', name: 'JKN', is_government: true, inacbg_id: '3' },
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
    {
      id: 11,
      code: 'ASR-11',
      type: 'private_insurance',
      name: 'Asuransi Mandiri Inhealth',
      is_government: false,
      inacbg_id: '9',
    },
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
  const wrapper = mount(InsuranceTypeView, {
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

describe('InsuranceTypeView', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    toast.clear()
    guarantorsService.list.mockResolvedValue(mockListResponse)
  })

  afterEach(() => {
    vi.useRealTimers()
  })

  it('fetches sorted paginated guarantors on mount and renders 5 exact columns without Status column', async () => {
    const wrapper = await mountView()

    expect(guarantorsService.list).toHaveBeenCalledWith({
      page: 1,
      per_page: 10,
      sort: 'name',
      direction: 'asc',
    })
    expect(wrapper.get('h1').text()).toBe('Jenis Asuransi')

    const headers = wrapper.findAll('thead th').map((th) => th.text().trim())
    expect(headers).toEqual(['No', 'Jenis Asuransi', 'Asuransi Pemerintah', 'ID INACBG', 'Aksi'])
    expect(headers).not.toContain('Status')

    expect(wrapper.text()).toContain('UMUM')
    expect(wrapper.text()).toContain('Tidak')
    expect(wrapper.text()).toContain('JKN')
    expect(wrapper.text()).toContain('Ya')
    expect(wrapper.text()).toContain('3')
    expect(wrapper.find('button[title="Nonaktifkan"]').exists()).toBe(false)
  })

  it('supports pagination offset numbering, manual refresh, debounced search, and race condition guard', async () => {
    vi.useFakeTimers()
    const wrapper = await mountView()

    expect(wrapper.get('tbody tr td:first-child').text()).toBe('1')
    expect(wrapper.text()).toContain('Menampilkan 1–2 dari 12 data')

    guarantorsService.list.mockResolvedValueOnce(mockPageTwoResponse)
    await wrapper.get('button[aria-label="Halaman berikutnya"]').trigger('click')
    await flushPromises()

    expect(guarantorsService.list).toHaveBeenLastCalledWith(
      expect.objectContaining({ page: 2, per_page: 10 }),
    )
    expect(wrapper.get('tbody tr td:first-child').text()).toBe('11')
    expect(wrapper.text()).toContain('Asuransi Mandiri Inhealth')

    guarantorsService.list.mockResolvedValueOnce(mockListResponse)
    await wrapper.get('button[aria-label="Muat ulang data"]').trigger('click')
    await flushPromises()
    expect(guarantorsService.list).toHaveBeenCalledTimes(3)

    // Simulasi race condition: halaman 2 lambat, lalu user ketik pencarian yang selesai lebih cepat
    let resolveSlow
    const slowPromise = new Promise((resolve) => {
      resolveSlow = resolve
    })
    guarantorsService.list.mockReturnValueOnce(slowPromise)
    await wrapper.get('button[aria-label="Halaman berikutnya"]').trigger('click')

    guarantorsService.list.mockResolvedValueOnce(mockEmptyResponse)
    await wrapper.get('input[type="search"]').setValue('TidakAda')
    await vi.advanceTimersByTimeAsync(450)
    await flushPromises()

    expect(wrapper.text()).toContain('Jenis asuransi tidak ditemukan')

    resolveSlow(mockPageTwoResponse)
    await flushPromises()
    expect(wrapper.text()).toContain('Jenis asuransi tidak ditemukan')
    expect(wrapper.text()).not.toContain('Asuransi Mandiri Inhealth')

    guarantorsService.list.mockResolvedValueOnce(mockListResponse)
    await findButtonByText(wrapper, 'Reset Pencarian').trigger('click')
    await flushPromises()
    expect(wrapper.text()).toContain('UMUM')
  })

  it('sanitizes load error 5xx with retry button and displays empty state when database has no records', async () => {
    guarantorsService.list.mockRejectedValueOnce({
      response: { status: 500, data: { message: 'SQLSTATE[42S02]: missing table' } },
    })
    const wrapper = await mountView()

    expect(wrapper.text()).toContain('Gagal memuat data jenis asuransi')
    expect(wrapper.text()).toContain('Data jenis asuransi gagal dimuat.')
    expect(wrapper.text()).not.toContain('SQLSTATE')

    guarantorsService.list.mockResolvedValueOnce(mockEmptyResponse)
    await findButtonByText(wrapper, 'Coba Lagi').trigger('click')
    await flushPromises()

    expect(wrapper.text()).toContain('Belum ada data jenis asuransi')
  })

  it('performs create, edit preserving legacy metadata, and maps 422 errors cleanly', async () => {
    guarantorsService.create
      .mockRejectedValueOnce({
        response: {
          status: 422,
          data: {
            message: 'Data tidak valid.',
            errors: { name: ['Nama jenis asuransi sudah digunakan.'] },
          },
        },
      })
      .mockResolvedValueOnce({ data: { id: 3, name: 'Asuransi Prudential' } })
    guarantorsService.update.mockResolvedValueOnce({
      data: { id: 2, name: 'JKN PBI', is_government: true, inacbg_id: '3' },
    })

    const wrapper = await mountView()

    await findButtonByText(wrapper, 'Tambah Jenis Asuransi').trigger('click')
    await wrapper.get('input#name').setValue('JKN')
    await wrapper.get('form').trigger('submit.prevent')
    await flushPromises()

    expect(wrapper.text()).toContain('Nama jenis asuransi sudah digunakan.')
    expect(wrapper.text()).toContain('UMUM')
    expect(toastEntries().at(-1)?.tone).toBe('danger')

    await wrapper.get('input#name').setValue('Asuransi Prudential')
    await wrapper.get('input#inacbg_id').setValue('15')
    await wrapper.get('form').trigger('submit.prevent')
    await flushPromises()

    expect(guarantorsService.create).toHaveBeenLastCalledWith({
      name: 'Asuransi Prudential',
      is_government: false,
      inacbg_id: '15',
    })
    expect(toastEntries().at(-1)?.tone).toBe('success')
    expect(toastEntries().at(-1)?.message).toBe('Data jenis asuransi berhasil ditambahkan.')

    await wrapper.get('button[aria-label="Edit JKN"]').trigger('click')
    await wrapper.get('input#name').setValue('JKN PBI')
    await wrapper.get('form').trigger('submit.prevent')
    await flushPromises()

    expect(guarantorsService.update).toHaveBeenCalledWith(2, {
      name: 'JKN PBI',
      is_government: true,
      inacbg_id: '3',
      code: 'JKN',
      type: 'bpjs',
    })
    expect(toastEntries().at(-1)?.message).toBe('Data jenis asuransi berhasil diperbarui.')
  })

  it('handles delete success, steps back when last row on page > 1 is removed, and handles legacy admission conflict', async () => {
    guarantorsService.list
      .mockResolvedValueOnce(mockPageTwoResponse)
      .mockResolvedValueOnce(mockListResponse)
    guarantorsService.remove.mockResolvedValueOnce(null)

    const wrapper = await mountView()

    await wrapper.get('button[aria-label="Hapus Asuransi Mandiri Inhealth"]').trigger('click')
    await findButtonByText(wrapper, 'Ya, Hapus').trigger('click')
    await flushPromises()

    expect(guarantorsService.remove).toHaveBeenCalledWith(11)
    expect(guarantorsService.list).toHaveBeenLastCalledWith(
      expect.objectContaining({ page: 1 }),
    )
    expect(toastEntries().at(-1)?.tone).toBe('success')
    expect(toastEntries().at(-1)?.message).toBe('Data jenis asuransi berhasil dihapus.')

    guarantorsService.remove.mockRejectedValueOnce({
      response: {
        status: 422,
        data: {
          message: 'Penjamin tidak dapat dihapus karena sudah digunakan pada data admisi pasien.',
        },
      },
    })

    await wrapper.get('button[aria-label="Hapus UMUM"]').trigger('click')
    await findButtonByText(wrapper, 'Ya, Hapus').trigger('click')
    await flushPromises()

    expect(toastEntries().at(-1)?.tone).toBe('danger')
    expect(toastEntries().at(-1)?.message).toBe(
      'Penjamin tidak dapat dihapus karena sudah digunakan pada data admisi pasien.',
    )
  })
})
