import { flushPromises, mount } from '@vue/test-utils'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import EmployeeView from '../EmployeeView.vue'
import { useToast } from '@/composables/core/useToast'
import employeeService from '@/services/employee.service'
import lookupService from '@/services/lookup.service'
import axios from '@/utils/axios'

vi.mock('@/services/employee.service', async (importOriginal) => {
  const actual = await importOriginal()
  const actualService = actual.employeeService
  return {
    employeeService: actualService,
    default: actualService,
  }
})

vi.mock('@/services/lookup.service', () => ({
  default: {
    getEducations: vi.fn(),
    getOccupations: vi.fn(),
    getProvinces: vi.fn(),
    getRegencies: vi.fn(),
    getDistricts: vi.fn(),
    getVillages: vi.fn(),
  },
}))

vi.mock('@/utils/axios', () => ({
  default: {
    get: vi.fn(),
    post: vi.fn(),
    patch: vi.fn(),
    delete: vi.fn(),
  },
}))

const mockRows = [
  {
    id: 1,
    code: '19900101',
    name: 'dr. Budi Santoso',
    position: { id: 10, name: 'Dokter Umum' },
  },
  {
    id: 2,
    code: '19920202',
    name: 'Ns. Siti Aminah',
    position: null,
  },
]

function rawPaginatorResponse(data = mockRows, overrides = {}) {
  return {
    data: {
      current_page: 1,
      last_page: 1,
      per_page: 15,
      total: data.length,
      from: data.length > 0 ? 1 : 0,
      to: data.length,
      data,
      ...overrides,
    },
  }
}

const toast = useToast()
const toastMessages = () => toast.toasts.value.map((item) => item.message)

async function mountView() {
  const wrapper = mount(EmployeeView, {
    global: {
      stubs: {
        Teleport: true,
      },
    },
  })
  await flushPromises()
  return wrapper
}

function findButtonByText(wrapper, label) {
  return wrapper.findAll('button').find((button) => button.text().trim() === label)
}

function findControlByLabel(wrapper, labelText) {
  const label = wrapper
    .findAll('label')
    .find((node) => node.text().replace('*', '').trim() === labelText)
  return wrapper.get(`[id="${label.attributes('for')}"]`)
}

function tableCells(wrapper) {
  return wrapper.findAll('tbody tr').map((row) => row.findAll('td').map((cell) => cell.text()))
}

describe('EmployeeView', () => {
  beforeEach(() => {
    vi.resetAllMocks()
    vi.useRealTimers()
    toast.clear()

    axios.get.mockImplementation((url) => {
      if (url === '/master-data/employees') {
        return Promise.resolve(rawPaginatorResponse())
      }
      if (url === '/lookups/positions') {
        return Promise.resolve({ data: { data: [{ id: 10, name: 'Dokter Umum' }] } })
      }
      return Promise.resolve({ data: {} })
    })
    lookupService.getEducations.mockResolvedValue([])
    lookupService.getOccupations.mockResolvedValue([])
    lookupService.getProvinces.mockResolvedValue([])
    lookupService.getRegencies.mockResolvedValue([])
    lookupService.getDistricts.mockResolvedValue([])
    lookupService.getVillages.mockResolvedValue([])
  })

  afterEach(() => {
    vi.useRealTimers()
    document.body.innerHTML = ''
  })

  it('merender header, breadcrumbs, tombol tambah, 5 kolom standar, dan fallback jabatan kosong', async () => {
    const wrapper = await mountView()

    expect(wrapper.get('h1').text()).toBe('Pegawai')
    expect(wrapper.text()).toContain(
      'Kelola data pegawai, unit kerja, jabatan, dan informasi profil lainnya.',
    )

    const breadcrumbs = wrapper
      .get('nav[aria-label="Breadcrumb"]')
      .findAll('li')
      .map((li) => li.text().replace('/', '').trim())
    expect(breadcrumbs).toEqual(['Dashboard', 'Master Data', 'Data Pegawai', 'Pegawai'])

    const headers = wrapper.findAll('thead th').map((th) => th.text())
    expect(headers).toEqual(['No', 'NIP / ID', 'Nama Pegawai', 'Jabatan', 'Aksi'])

    const rows = tableCells(wrapper)
    expect(rows[0].slice(0, 4)).toEqual(['1', '19900101', 'dr. Budi Santoso', 'Dokter Umum'])
    expect(rows[1].slice(0, 4)).toEqual(['2', '19920202', 'Ns. Siti Aminah', '—'])
  })

  it('mengirim parameter sort_by dan sort_desc serta mendukung paginasi raw Laravel paginator', async () => {
    axios.get.mockImplementation((url, config) => {
      if (url === '/master-data/employees') {
        const page = config?.params?.page ?? 1
        return Promise.resolve(
          rawPaginatorResponse(
            page === 2 ? [{ id: 16, code: '1999', name: 'Pegawai 16', position: null }] : mockRows,
            { current_page: page, last_page: 2, total: 16, from: page === 2 ? 16 : 1, to: page === 2 ? 16 : 15 },
          ),
        )
      }
      return Promise.resolve({ data: [] })
    })

    const wrapper = await mountView()

    expect(axios.get).toHaveBeenCalledWith('/master-data/employees', {
      params: { page: 1, per_page: 15, sort_by: 'created_at', sort_desc: 1 },
    })
    expect(wrapper.text()).toContain('Menampilkan 1–15 dari 16 data')

    await wrapper.get('button[aria-label="Halaman berikutnya"]').trigger('click')
    await flushPromises()

    expect(axios.get).toHaveBeenLastCalledWith('/master-data/employees', {
      params: { page: 2, per_page: 15, sort_by: 'created_at', sort_desc: 1 },
    })
    expect(tableCells(wrapper)[0].slice(0, 3)).toEqual(['16', '1999', 'Pegawai 16'])
  })

  it('melakukan debounce pencarian 400ms dan menampilkan empty state pencarian serta tombol reset', async () => {
    vi.useFakeTimers()
    const wrapper = await mountView()
    axios.get.mockClear()

    axios.get.mockResolvedValue(rawPaginatorResponse([], { total: 0, from: 0, to: 0 }))

    const searchInput = wrapper.get('input[placeholder="Cari NIP / Nama..."]')
    await searchInput.setValue('Andi')
    expect(axios.get).not.toHaveBeenCalled()

    await vi.advanceTimersByTimeAsync(400)
    await flushPromises()

    expect(axios.get).toHaveBeenCalledWith('/master-data/employees', {
      params: {
        page: 1,
        per_page: 15,
        sort_by: 'created_at',
        sort_desc: 1,
        search: 'Andi',
      },
    })
    expect(wrapper.text()).toContain('Pegawai tidak ditemukan')

    axios.get.mockResolvedValue(rawPaginatorResponse())
    await findButtonByText(wrapper, 'Reset Pencarian').trigger('click')
    await flushPromises()
    expect(tableCells(wrapper)).toHaveLength(2)
  })

  it('menangani empty state awal dan state gagal memuat dengan tombol muat ulang', async () => {
    axios.get.mockRejectedValueOnce({ response: { status: 500, data: { message: 'Server error' } } })
    const wrapper = await mountView()

    expect(wrapper.text()).toContain('Gagal memuat data pegawai')

    axios.get.mockResolvedValueOnce(rawPaginatorResponse([], { total: 0, from: 0, to: 0 }))
    await findButtonByText(wrapper, 'Muat Ulang').trigger('click')
    await flushPromises()

    expect(wrapper.text()).toContain('Belum ada data pegawai')
    expect(findButtonByText(wrapper, 'Tambah Pegawai')).toBeTruthy()
  })

  it('mendukung tambah pegawai, edit, hapus, serta sanitasi error server SQL/422', async () => {
    const postSpy = axios.post.mockResolvedValue({ data: { id: 99 } })
    const delSpy = axios.delete.mockResolvedValue({ data: { message: 'deleted' } })

    const wrapper = await mountView()

    await findButtonByText(wrapper, 'Tambah Pegawai').trigger('click')
    await flushPromises()

    await findControlByLabel(wrapper, 'Nomor KTP (NIK)').setValue('3578010101010009')
    await findControlByLabel(wrapper, 'Nama Lengkap').setValue('Budi Baru')
    await findControlByLabel(wrapper, 'NIP (Nomor Induk Pegawai)').setValue('2024001')
    await findControlByLabel(wrapper, 'Jabatan').setValue('10')

    await wrapper.get('form').trigger('submit.prevent')
    await flushPromises()

    expect(postSpy).toHaveBeenCalledWith(
      '/master-data/employees',
      expect.any(FormData),
      { headers: { 'Content-Type': 'multipart/form-data' } },
    )
    expect(toastMessages()).toContain('Data pegawai berhasil ditambahkan.')

    // Edit error 500 SQL harus disamarkan menjadi fallback aman
    axios.get.mockImplementation((url) => {
      if (url === '/master-data/employees/1') {
        return Promise.resolve({
          data: {
            id: 1,
            national_id: '357801',
            name: 'dr. Budi Santoso',
            code: '19900101',
            position_id: 10,
          },
        })
      }
      if (url === '/lookups/positions') {
        return Promise.resolve({ data: { data: [{ id: 10, name: 'Dokter Umum' }] } })
      }
      return Promise.resolve(rawPaginatorResponse())
    })
    axios.post.mockRejectedValueOnce({
      response: { status: 500, data: { message: 'SQLSTATE[42S22]: Column not found' } },
    })

    await wrapper.get('button[aria-label="Edit dr. Budi Santoso"]').trigger('click')
    await flushPromises()
    await wrapper.get('form').trigger('submit.prevent')
    await flushPromises()

    expect(toastMessages()).toContain('Data pegawai gagal diperbarui.')
    await findButtonByText(wrapper, 'Batal').trigger('click')

    // Hapus pegawai
    await wrapper.get('button[aria-label="Hapus Ns. Siti Aminah"]').trigger('click')
    await flushPromises()
    expect(wrapper.text()).toContain('Ns. Siti Aminah')
    await findButtonByText(wrapper, 'Ya, Hapus').trigger('click')
    await flushPromises()

    expect(delSpy).toHaveBeenCalledWith('/master-data/employees/2')
    expect(toastMessages()).toContain('Data pegawai berhasil dihapus.')

    // Pastikan helper buildFormData tetap berfungsi untuk _method=PUT
    const putData = employeeService.buildFormData(
      { code: '1990', remove_signature: true, signature: null },
      'PUT',
    )
    expect(putData.get('_method')).toBe('PUT')
    expect(putData.get('remove_signature')).toBe('1')
  })
})
