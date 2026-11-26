import { flushPromises, mount } from '@vue/test-utils'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import DoctorView from '../DoctorView.vue'
import { useToast } from '@/composables/core/useToast'
import lookupService from '@/services/lookup.service'
import doctorService from '@/services/master-data/doctors.service'
import axios from '@/utils/axios'

vi.mock('@/services/master-data/doctors.service', async (importOriginal) => {
  const actual = await importOriginal()
  const service = actual.doctorService
  return {
    doctorService: service,
    default: service,
  }
})

vi.mock('@/services/lookup.service', () => ({
  default: {
    getSpecializations: vi.fn(),
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

const mockDoctors = [
  {
    id: 1,
    name: 'dr. Budi Santoso, Sp.A',
    specialization: { id: 10, name: 'Spesialis Anak' },
    is_active: true,
  },
  {
    id: 2,
    name: 'dr. Siti Aminah',
    specialization: null,
    is_active: true,
  },
]

const listResponse = (data = mockDoctors, metaOverrides = {}) => ({
  data: {
    data,
    meta: {
      current_page: 1,
      last_page: 1,
      per_page: 15,
      total: data.length,
      from: data.length > 0 ? 1 : 0,
      to: data.length,
      ...metaOverrides,
    },
  },
})

const toast = useToast()
const toastMessages = () => toast.toasts.value.map((item) => item.message)

async function mountView() {
  const wrapper = mount(DoctorView, {
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

describe('DoctorView', () => {
  beforeEach(() => {
    vi.resetAllMocks()
    vi.useRealTimers()
    toast.clear()

    axios.get.mockImplementation((url) => {
      if (url === '/master-data/doctors') {
        return Promise.resolve(listResponse())
      }
      if (url === '/master-data/doctors/1') {
        return Promise.resolve({
          data: {
            data: {
              id: 1,
              employee_id: 22,
              specialization_id: 10,
              specialization: { id: 10, name: 'Spesialis Anak' },
              str_number: 'STR-001',
              sip_number: 'SIP-001',
              employee: {
                id: 22,
                name: 'dr. Budi Santoso, Sp.A',
                gender: 'L',
                phone: '081234567890',
                allergies: 'Tidak ada',
              },
            },
          },
        })
      }
      return Promise.resolve({ data: {} })
    })

    lookupService.getSpecializations.mockResolvedValue([{ id: 10, name: 'Spesialis Anak' }])
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

  it('merender header, breadcrumbs, tombol tambah, dan 4 kolom standar Dokter tanpa Status/STR/SIP', async () => {
    const wrapper = await mountView()

    expect(wrapper.get('h1').text()).toBe('Dokter')
    expect(wrapper.text()).toContain(
      'Kelola data dokter, spesialisasi, dan informasi profesional lainnya.',
    )

    const breadcrumbs = wrapper
      .get('nav[aria-label="Breadcrumb"]')
      .findAll('li')
      .map((li) => li.text().replace('/', '').trim())
    expect(breadcrumbs).toEqual(['Dashboard', 'Master Data', 'Data Dokter', 'Dokter'])

    expect(wrapper.findAll('thead th').map((th) => th.text())).toEqual([
      'No',
      'Nama Dokter',
      'Spesialisasi',
      'Aksi',
    ])

    const rows = tableCells(wrapper)
    expect(rows[0].slice(0, 3)).toEqual(['1', 'dr. Budi Santoso, Sp.A', 'Spesialis Anak'])
    expect(rows[1].slice(0, 3)).toEqual(['2', 'dr. Siti Aminah', '—'])
  })

  it('mengirim sort_by/sort_desc, mendukung paginasi halaman berikutnya, debounce pencarian 400ms, dan reset', async () => {
    vi.useFakeTimers()
    axios.get.mockImplementation((url, config) => {
      if (url === '/master-data/doctors') {
        const page = config?.params?.page ?? 1
        if (config?.params?.search === 'Siti') {
          return Promise.resolve(listResponse([], { total: 0, from: 0, to: 0 }))
        }
        return Promise.resolve(
          listResponse(
            page === 2 ? [{ id: 16, name: 'dr. Halaman Dua', specialization: null }] : mockDoctors,
            { current_page: page, last_page: 2, total: 16, from: page === 2 ? 16 : 1, to: page === 2 ? 16 : 15 },
          ),
        )
      }
      return Promise.resolve({ data: {} })
    })

    const wrapper = await mountView()
    expect(axios.get).toHaveBeenCalledWith('/master-data/doctors', {
      params: { page: 1, per_page: 15, sort_by: 'created_at', sort_desc: 1 },
    })

    await wrapper.get('button[aria-label="Halaman berikutnya"]').trigger('click')
    await flushPromises()
    expect(tableCells(wrapper)[0].slice(0, 2)).toEqual(['16', 'dr. Halaman Dua'])

    const searchInput = wrapper.get('input[placeholder="Cari nama dokter atau kode..."]')
    await searchInput.setValue('Siti')
    await vi.advanceTimersByTimeAsync(400)
    await flushPromises()

    expect(wrapper.text()).toContain('Dokter tidak ditemukan')
    await findButtonByText(wrapper, 'Reset Pencarian').trigger('click')
    await flushPromises()
    expect(tableCells(wrapper)).toHaveLength(2)
  })

  it('menampilkan detail dokter (termasuk Nomor HP dan Alergi) dan mendukung transisi ke Edit', async () => {
    const wrapper = await mountView()

    await wrapper.get('button[aria-label="Lihat detail dr. Budi Santoso, Sp.A"]').trigger('click')
    await flushPromises()

    expect(wrapper.text()).toContain('Detail Dokter')
    expect(wrapper.text()).toContain('081234567890')
    expect(wrapper.text()).toContain('STR-001')
    expect(wrapper.text()).toContain('SIP-001')

    await findButtonByText(wrapper, 'Edit Dokter').trigger('click')
    await flushPromises()

    expect(wrapper.text()).toContain('Edit Data Dokter')
    expect(findControlByLabel(wrapper, 'Nomor HP').element.value).toBe('081234567890')
    expect(findControlByLabel(wrapper, 'Alergi').element.value).toBe('Tidak ada')
  })

  it('mendukung tambah dokter, edit dokter, hapus dokter, sanitasi error SQL, dan FormData adapter', async () => {
    axios.post.mockResolvedValue({ data: { data: { id: 99 } } })
    axios.delete.mockResolvedValue({ data: {} })

    const wrapper = await mountView()

    await findButtonByText(wrapper, 'Tambah Dokter').trigger('click')
    await flushPromises()

    await findControlByLabel(wrapper, 'Nama Lengkap (beserta gelar)').setValue('dr. Baru')
    await findControlByLabel(wrapper, 'Jenis Kelamin').setValue('P')
    await findControlByLabel(wrapper, 'Nomor HP').setValue('0899887766')
    await findControlByLabel(wrapper, 'Spesialisasi').setValue('10')
    await wrapper.get('form').trigger('submit.prevent')
    await flushPromises()

    expect(axios.post).toHaveBeenCalledWith(
      '/master-data/doctors',
      expect.any(FormData),
      { headers: { 'Content-Type': 'multipart/form-data' } },
    )
    expect(toastMessages()).toContain('Data dokter berhasil ditambahkan.')

    // Edit dengan simulasi SQL error 500 -> fallback aman
    axios.post.mockRejectedValueOnce({
      response: { status: 500, data: { message: 'SQLSTATE[42000]: Syntax error' } },
    })
    await wrapper.get('button[aria-label="Edit dr. Budi Santoso, Sp.A"]').trigger('click')
    await flushPromises()
    await wrapper.get('form').trigger('submit.prevent')
    await flushPromises()

    expect(toastMessages()).toContain('Data dokter gagal diperbarui.')
    await findButtonByText(wrapper, 'Batal').trigger('click')

    // Hapus dokter
    await wrapper.get('button[aria-label="Hapus dr. Siti Aminah"]').trigger('click')
    await flushPromises()
    await findButtonByText(wrapper, 'Ya, Hapus').trigger('click')
    await flushPromises()

    expect(axios.delete).toHaveBeenCalledWith('/master-data/doctors/2')
    expect(toastMessages()).toContain('Data dokter berhasil dihapus.')

    // Pastikan buildFormData menjaga kompatibilitas nested person/professional + _method=PUT
    // dan mengirim string kosong eksplisit saat field nullable diset null (bukan string "null")
    const fd = doctorService.buildFormData(
      {
        employee_id: 22,
        person: { name: 'dr. Test', phone: '0812', allergies: null, birth_place: undefined },
        professional: { specialization_id: 10, str_number: null, is_active: true },
        remove_signature: true,
      },
      'PUT',
    )
    expect(fd.get('_method')).toBe('PUT')
    expect(fd.get('employee_id')).toBe('22')
    expect(fd.get('person[phone]')).toBe('0812')
    expect(fd.get('person[allergies]')).toBe('')
    expect(fd.has('person[birth_place]')).toBe(false)
    expect(fd.get('professional[str_number]')).toBe('')
    expect(fd.get('professional[is_active]')).toBe('1')
    expect(fd.get('remove_signature')).toBe('1')
  })

  it('menangani state gagal memuat data dokter dengan tombol Muat Ulang dan empty state awal', async () => {
    axios.get.mockRejectedValueOnce({ response: { status: 500, data: { message: 'Boom' } } })
    const wrapper = await mountView()

    expect(wrapper.text()).toContain('Gagal memuat data dokter')

    axios.get.mockResolvedValueOnce(listResponse([], { total: 0, from: 0, to: 0 }))
    await findButtonByText(wrapper, 'Muat Ulang').trigger('click')
    await flushPromises()

    expect(wrapper.text()).toContain('Belum ada data dokter')
    expect(findButtonByText(wrapper, 'Tambah Dokter')).toBeTruthy()
  })
})
