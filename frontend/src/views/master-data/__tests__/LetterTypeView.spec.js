import { flushPromises, mount } from '@vue/test-utils'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import LetterTypeView from '../LetterTypeView.vue'
import { useToast } from '@/composables/core/useToast'
import letterTypeService from '@/services/letterType.service'

vi.mock('@/services/letterType.service', () => {
  const mockService = {
    list: vi.fn(),
    create: vi.fn(),
    update: vi.fn(),
    remove: vi.fn(),
  }
  return {
    letterTypeService: mockService,
    default: mockService,
  }
})

const mockListResponse = {
  data: [
    {
      id: 1,
      name: 'Surat Keterangan Sehat',
      description: 'Deskripsi Sehat',
      legacy_resource: 'surat_sehat.php',
      is_active: true,
    },
    {
      id: 2,
      name: 'Surat Rujukan',
      description: null,
      legacy_resource: null,
      is_active: false,
    },
    {
      id: 3,
      name: 'Surat Tidak Aman',
      description: 'Payload berbahaya',
      legacy_resource: 'javascript:alert(1)',
      is_active: true,
    },
  ],
  meta: {
    current_page: 1,
    last_page: 2,
    per_page: 10,
    total: 12,
    from: 1,
    to: 3,
  },
}

const mockPageTwoResponse = {
  data: [
    {
      id: 11,
      name: 'Surat Bebas Narkoba',
      description: 'Pemeriksaan lab',
      legacy_resource: 'surat_narkoba.php',
      is_active: true,
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
  const wrapper = mount(LetterTypeView, {
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

describe('LetterTypeView.vue', () => {
  let openSpy

  beforeEach(() => {
    vi.clearAllMocks()
    toast.clear()
    letterTypeService.list.mockResolvedValue(mockListResponse)
    openSpy = vi.spyOn(window, 'open').mockImplementation(() => null)
  })

  afterEach(() => {
    vi.useRealTimers()
    openSpy.mockRestore()
  })

  it('fetches paginated letter types on mount, renders exact 5 columns, and guards Lihat Format Lama', async () => {
    const wrapper = await mountView()

    expect(letterTypeService.list).toHaveBeenCalledWith({
      page: 1,
      per_page: 10,
      sort: 'name',
      direction: 'asc',
    })
    expect(wrapper.get('h1').text()).toBe('Surat-Surat')

    const headers = wrapper.findAll('thead th').map((th) => th.text().trim())
    expect(headers).toEqual(['No', 'Nama', 'Deskripsi', 'Resource', 'Aksi'])
    expect(headers).not.toContain('Status')

    expect(wrapper.text()).toContain('Surat Keterangan Sehat')
    expect(wrapper.text()).toContain('surat_sehat.php')
    expect(wrapper.text()).toContain('—')

    // Valid resource has preview button & opens safely with noopener,noreferrer
    const validPreviewBtn = wrapper.get('button[aria-label="Lihat Format Lama Surat Keterangan Sehat"]')
    await validPreviewBtn.trigger('click')
    expect(openSpy).toHaveBeenCalledWith(
      '/simrs_lama/surat_sehat.php',
      '_blank',
      'noopener,noreferrer',
    )

    // Empty and unsafe resources do NOT render preview button
    expect(wrapper.find('button[aria-label="Lihat Format Lama Surat Rujukan"]').exists()).toBe(false)
    expect(wrapper.find('button[aria-label="Lihat Format Lama Surat Tidak Aman"]').exists()).toBe(false)

    // Even if preview event were emitted directly on unsafe resource, window.open stays uncalled
    openSpy.mockClear()
    const tableComp = wrapper.findComponent({ name: 'LetterTypeTable' })
    tableComp.vm.$emit('preview-legacy', mockListResponse.data[2])
    await flushPromises()
    expect(openSpy).not.toHaveBeenCalled()
  })

  it('supports loading skeleton, pagination offset, manual refresh, debounced search, and race condition guard', async () => {
    vi.useFakeTimers()

    let resolveInitial
    letterTypeService.list.mockReturnValueOnce(
      new Promise((resolve) => {
        resolveInitial = resolve
      }),
    )
    const wrapper = mount(LetterTypeView, {
      global: { stubs: { Teleport: true } },
    })
    await flushPromises()
    expect(wrapper.findAll('tbody .animate-pulse').length).toBeGreaterThan(0)

    resolveInitial(mockListResponse)
    await flushPromises()

    expect(wrapper.get('tbody tr td:first-child').text()).toBe('1')
    expect(wrapper.text()).toContain('Menampilkan 1–3 dari 12 data')

    letterTypeService.list.mockResolvedValueOnce(mockPageTwoResponse)
    await wrapper.get('button[aria-label="Halaman berikutnya"]').trigger('click')
    await flushPromises()

    expect(letterTypeService.list).toHaveBeenLastCalledWith(
      expect.objectContaining({ page: 2, per_page: 10 }),
    )
    expect(wrapper.get('tbody tr td:first-child').text()).toBe('11')
    expect(wrapper.text()).toContain('Surat Bebas Narkoba')

    letterTypeService.list.mockResolvedValueOnce(mockListResponse)
    await wrapper.get('button[aria-label="Muat ulang data"]').trigger('click')
    await flushPromises()
    expect(letterTypeService.list).toHaveBeenCalledTimes(3)

    // Race condition: slow page 2 resolves after fast search
    let resolveSlow
    letterTypeService.list.mockReturnValueOnce(
      new Promise((resolve) => {
        resolveSlow = resolve
      }),
    )
    await wrapper.get('button[aria-label="Halaman berikutnya"]').trigger('click')

    letterTypeService.list.mockResolvedValueOnce(mockEmptyResponse)
    await wrapper.get('input[type="search"]').setValue('TidakAda')
    await vi.advanceTimersByTimeAsync(450)
    await flushPromises()

    expect(wrapper.text()).toContain('Data surat tidak ditemukan')

    resolveSlow(mockPageTwoResponse)
    await flushPromises()
    expect(wrapper.text()).toContain('Data surat tidak ditemukan')
    expect(wrapper.text()).not.toContain('Surat Bebas Narkoba')

    letterTypeService.list.mockResolvedValueOnce(mockListResponse)
    await findButtonByText(wrapper, 'Reset Pencarian').trigger('click')
    await flushPromises()
    expect(wrapper.text()).toContain('Surat Keterangan Sehat')
  })

  it('sanitizes 5xx load error with retry and renders initial empty state', async () => {
    letterTypeService.list.mockRejectedValueOnce({
      response: { status: 500, data: { message: 'SQLSTATE[42S02]: table missing' } },
    })
    const wrapper = await mountView()

    expect(wrapper.text()).toContain('Gagal memuat data surat-surat')
    expect(wrapper.text()).toContain('Data surat gagal dimuat.')
    expect(wrapper.text()).not.toContain('SQLSTATE')

    letterTypeService.list.mockResolvedValueOnce(mockEmptyResponse)
    await findButtonByText(wrapper, 'Coba Lagi').trigger('click')
    await flushPromises()

    expect(wrapper.text()).toContain('Belum ada data surat')
  })

  it('performs create, update, handles 422 validation, 403 forbidden, and 404 not found safely', async () => {
    letterTypeService.create
      .mockRejectedValueOnce({
        response: {
          status: 422,
          data: {
            message: 'Nama surat sudah digunakan.',
            errors: { name: ['Nama surat sudah digunakan.'] },
          },
        },
      })
      .mockResolvedValueOnce({
        data: { id: 4, name: 'Surat Izin', description: 'Izin cuti', legacy_resource: 'izin.php' },
      })

    letterTypeService.update
      .mockRejectedValueOnce({
        response: { status: 403, data: { message: 'This action is unauthorized.' } },
      })
      .mockRejectedValueOnce({
        response: { status: 404, data: { message: 'No query results for model [App\\Models\\LetterType] 1' } },
      })
      .mockResolvedValueOnce({
        data: { id: 1, name: 'Surat Sehat Revisi', description: null, legacy_resource: 'surat_sehat.php' },
      })

    const wrapper = await mountView()

    // 422 create error preserves rows & modal
    await findButtonByText(wrapper, 'Tambah Surat').trigger('click')
    await wrapper.get('input#name').setValue('Surat Keterangan Sehat')
    await wrapper.get('form').trigger('submit.prevent')
    await flushPromises()

    expect(wrapper.text()).toContain('Nama surat sudah digunakan.')
    expect(wrapper.text()).toContain('Surat Keterangan Sehat')
    expect(toastEntries().at(-1)?.tone).toBe('danger')

    // Valid create
    await wrapper.get('input#name').setValue('Surat Izin')
    await wrapper.get('textarea#description').setValue('Izin cuti')
    await wrapper.get('input#legacy_resource').setValue('izin.php')
    await wrapper.get('form').trigger('submit.prevent')
    await flushPromises()

    expect(letterTypeService.create).toHaveBeenLastCalledWith({
      name: 'Surat Izin',
      description: 'Izin cuti',
      legacy_resource: 'izin.php',
    })
    expect(toastEntries().at(-1)?.tone).toBe('success')
    expect(toastEntries().at(-1)?.message).toBe('Data surat berhasil ditambahkan.')

    // Edit -> 403 Forbidden shows clear alert (not field error) & retains form data
    await wrapper.get('button[aria-label="Edit Surat Keterangan Sehat"]').trigger('click')
    await wrapper.get('input#name').setValue('Surat Sehat Revisi')
    await wrapper.get('form').trigger('submit.prevent')
    await flushPromises()

    expect(wrapper.text()).toContain(
      'Anda tidak memiliki izin untuk mengakses atau mengubah data surat.',
    )
    expect(wrapper.get('input#name').element.value).toBe('Surat Sehat Revisi')
    expect(toastEntries().at(-1)?.tone).toBe('danger')

    // Edit -> 404 Not Found shows clear message
    await wrapper.get('form').trigger('submit.prevent')
    await flushPromises()
    expect(wrapper.text()).toContain('Data surat tidak ditemukan atau sudah dihapus.')

    // Edit -> 200 OK
    await wrapper.get('textarea#description').setValue('')
    await wrapper.get('form').trigger('submit.prevent')
    await flushPromises()

    expect(letterTypeService.update).toHaveBeenLastCalledWith(1, {
      name: 'Surat Sehat Revisi',
      description: null,
      legacy_resource: 'surat_sehat.php',
    })
    expect(toastEntries().at(-1)?.tone).toBe('success')
    expect(toastEntries().at(-1)?.message).toBe('Data surat berhasil diperbarui.')
  })

  it('handles delete success with page step-back and surfaces 403 error safely', async () => {
    letterTypeService.list
      .mockResolvedValueOnce(mockPageTwoResponse)
      .mockResolvedValueOnce(mockListResponse)
    letterTypeService.remove.mockResolvedValueOnce({ message: 'Surat berhasil dihapus' })

    const wrapper = await mountView()

    await wrapper.get('button[aria-label="Hapus Surat Bebas Narkoba"]').trigger('click')
    await findButtonByText(wrapper, 'Ya, Hapus').trigger('click')
    await flushPromises()

    expect(letterTypeService.remove).toHaveBeenCalledWith(11)
    expect(letterTypeService.list).toHaveBeenLastCalledWith(
      expect.objectContaining({ page: 1 }),
    )
    expect(toastEntries().at(-1)?.tone).toBe('success')
    expect(toastEntries().at(-1)?.message).toBe('Data surat berhasil dihapus.')

    letterTypeService.remove.mockRejectedValueOnce({
      response: { status: 403, data: { message: 'This action is unauthorized.' } },
    })

    await wrapper.get('button[aria-label="Hapus Surat Keterangan Sehat"]').trigger('click')
    await findButtonByText(wrapper, 'Ya, Hapus').trigger('click')
    await flushPromises()

    expect(toastEntries().at(-1)?.tone).toBe('danger')
    expect(toastEntries().at(-1)?.message).toBe(
      'Anda tidak memiliki izin untuk mengakses atau mengubah data surat.',
    )
  })
})
