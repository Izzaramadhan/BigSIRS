import { flushPromises, mount } from '@vue/test-utils'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import EmployeeFormModal from '../EmployeeFormModal.vue'
import employeeService from '@/services/employee.service'
import lookupService from '@/services/lookup.service'

vi.mock('@/services/employee.service', () => ({
  default: {
    getEmployee: vi.fn(),
    getPositions: vi.fn(),
  },
}))

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

const mockPositions = [
  { id: 10, legacy_id: 1, name: 'Dokter Spesialis' },
  { id: 11, legacy_id: 2, name: 'Perawat' },
]

function findControlByLabel(wrapper, labelText) {
  const label = wrapper
    .findAll('label')
    .find((node) => node.text().replace('*', '').trim() === labelText)
  if (!label) throw new Error(`Label not found: ${labelText}`)
  return wrapper.get(`[id="${label.attributes('for')}"]`)
}

function findButtonByText(wrapper, text) {
  return wrapper.findAll('button').find((btn) => btn.text().trim() === text)
}

async function mountModal(props = {}) {
  const wrapper = mount(EmployeeFormModal, {
    props: {
      open: true,
      employee: null,
      saving: false,
      serverErrors: {},
      generalError: '',
      ...props,
    },
    global: {
      stubs: {
        Teleport: true,
      },
    },
  })
  await flushPromises()
  return wrapper
}

describe('EmployeeFormModal', () => {
  beforeEach(() => {
    vi.resetAllMocks()
    employeeService.getPositions.mockResolvedValue(mockPositions)
    lookupService.getEducations.mockResolvedValue([{ id: 1, name: 'S1 Kedokteran' }])
    lookupService.getOccupations.mockResolvedValue([{ id: 2, name: 'Dokter' }])
    lookupService.getProvinces.mockResolvedValue([{ id: 35, name: 'Jawa Timur' }])
    lookupService.getRegencies.mockResolvedValue([{ id: 3578, name: 'Kota Surabaya' }])
    lookupService.getDistricts.mockResolvedValue([{ id: 357801, name: 'Gubeng' }])
    lookupService.getVillages.mockResolvedValue([{ id: 3578011, name: 'Airlangga' }])

    vi.stubGlobal('URL', {
      createObjectURL: vi.fn(() => 'blob:mock-signature'),
      revokeObjectURL: vi.fn(),
    })
  })

  afterEach(() => {
    vi.unstubAllGlobals()
    document.body.innerHTML = ''
  })

  it('merender 3 section form tambah pegawai dan memvalidasi field wajib sebelum submit', async () => {
    const wrapper = await mountModal()

    expect(wrapper.text()).toContain('Tambah Data Pegawai')
    expect(wrapper.text()).toContain('1. Identitas Pribadi')
    expect(wrapper.text()).toContain('2. Alamat & Kontak')
    expect(wrapper.text()).toContain('3. Informasi Kepegawaian')

    await wrapper.get('form').trigger('submit.prevent')

    expect(wrapper.text()).toContain('Nomor KTP (NIK) wajib diisi.')
    expect(wrapper.text()).toContain('Nama lengkap wajib diisi.')
    expect(wrapper.text()).toContain('NIP wajib diisi.')
    expect(wrapper.text()).toContain('Jabatan wajib dipilih.')
    expect(wrapper.emitted('submit')).toBeFalsy()
  })

  it('mengirim payload bersih saat form tambah valid disubmit', async () => {
    const wrapper = await mountModal()

    await findControlByLabel(wrapper, 'Nomor KTP (NIK)').setValue('3578010101900001')
    await findControlByLabel(wrapper, 'Nama Lengkap').setValue('dr. Budi Santoso')
    await findControlByLabel(wrapper, 'NIP (Nomor Induk Pegawai)').setValue('19900101')
    await findControlByLabel(wrapper, 'Jabatan').setValue('10')

    await wrapper.get('form').trigger('submit.prevent')

    const emitted = wrapper.emitted('submit')
    expect(emitted).toHaveLength(1)
    expect(emitted[0][0]).toMatchObject({
      national_id: '3578010101900001',
      name: 'dr. Budi Santoso',
      code: '19900101',
      position_id: 10,
      nationality: 'WNI',
      remove_signature: false,
    })
  })

  it('melakukan hidrasi detail pegawai, jabatan legacy id_jabatan, wilayah bertingkat, dan hapus tanda tangan', async () => {
    employeeService.getEmployee.mockResolvedValue({
      id: 77,
      national_id: '3578010202880002',
      name: 'Ns. Siti Aminah',
      code: '19880202',
      id_jabatan: 2,
      education_id: 1,
      occupation_id: 2,
      province_id: 35,
      regency_id: 3578,
      district_id: 357801,
      village_id: 3578011,
      signature_url: 'http://localhost/storage/signatures/siti.png',
    })

    const wrapper = await mountModal({
      employee: { id: 77, name: 'Ns. Siti Aminah' },
    })

    expect(employeeService.getEmployee).toHaveBeenCalledWith(77)
    expect(findControlByLabel(wrapper, 'Jabatan').element.value).toBe('11')
    expect(findControlByLabel(wrapper, 'Provinsi').element.value).toBe('35')
    expect(findControlByLabel(wrapper, 'Kabupaten / Kota').element.value).toBe('3578')
    expect(findControlByLabel(wrapper, 'Kecamatan').element.value).toBe('357801')
    expect(findControlByLabel(wrapper, 'Kelurahan / Desa').element.value).toBe('3578011')

    const preview = wrapper.get('img[alt="Preview tanda tangan"]')
    expect(preview.attributes('src')).toBe('http://localhost/storage/signatures/siti.png')

    await findButtonByText(wrapper, 'Hapus Tanda Tangan').trigger('click')
    expect(wrapper.text()).toContain('Tanda tangan tersimpan akan dihapus saat data disimpan.')

    await wrapper.get('form').trigger('submit.prevent')
    expect(wrapper.emitted('submit')[0][0].remove_signature).toBe(true)
  })

  it('memvalidasi tipe dan ukuran file tanda tangan serta mendukung pembatalan file baru', async () => {
    const wrapper = await mountModal()
    const fileInput = findControlByLabel(wrapper, 'Tanda Tangan Digital')

    const invalidPdf = new File(['pdf'], 'surat.pdf', { type: 'application/pdf' })
    Object.defineProperty(fileInput.element, 'files', {
      value: [invalidPdf],
      configurable: true,
    })
    await fileInput.trigger('change')
    expect(wrapper.text()).toContain('Format file tanda tangan harus PNG atau JPG.')

    const validPng = new File(['png'], 'ttd.png', { type: 'image/png' })
    Object.defineProperty(fileInput.element, 'files', {
      value: [validPng],
      configurable: true,
    })
    await fileInput.trigger('change')

    expect(wrapper.get('img[alt="Preview tanda tangan"]').attributes('src')).toBe(
      'blob:mock-signature',
    )
    expect(wrapper.text()).toContain('ttd.png')

    await findButtonByText(wrapper, 'Batalkan Pilihan').trigger('click')
    expect(wrapper.find('img[alt="Preview tanda tangan"]').exists()).toBe(false)
    expect(globalThis.URL.revokeObjectURL).toHaveBeenCalledWith('blob:mock-signature')
  })

  it('mereset kabupaten/kecamatan/kelurahan saat provinsi diubah user dan mengunci dialog saat saving', async () => {
    employeeService.getEmployee.mockResolvedValue({
      id: 77,
      national_id: '3578',
      name: 'Siti',
      code: '002',
      position_id: 11,
      province_id: 35,
      regency_id: 3578,
      district_id: 357801,
      village_id: 3578011,
    })

    const wrapper = await mountModal({
      employee: { id: 77 },
      saving: true,
    })

    await findControlByLabel(wrapper, 'Provinsi').setValue('')
    await flushPromises()
    expect(findControlByLabel(wrapper, 'Kabupaten / Kota').element.value).toBe('')
    expect(findControlByLabel(wrapper, 'Kecamatan').element.value).toBe('')

    await findButtonByText(wrapper, 'Batal').trigger('click')
    expect(wrapper.emitted('close')).toBeFalsy()
  })

  it('merender BaseAlert dengan tone danger dan warning yang benar tanpa regresi validasi', async () => {
    employeeService.getPositions.mockRejectedValueOnce(new Error('Lookup timeout'))
    const warningWrapper = await mountModal({
      generalError: 'Gagal menyimpan data pegawai.',
    })

    const dangerAlert = warningWrapper.get('[role="alert"]')
    expect(dangerAlert.classes()).toContain('bg-danger-subtle')
    expect(dangerAlert.text()).toContain('Gagal menyimpan data pegawai.')

    const warningAlert = warningWrapper.get('[role="status"]')
    expect(warningAlert.classes()).toContain('bg-warning-subtle')
    expect(warningAlert.text()).toContain('Sebagian data referensi gagal dimuat.')

    employeeService.getPositions.mockResolvedValueOnce(mockPositions)
    employeeService.getEmployee.mockRejectedValueOnce(new Error('Not found'))
    const detailErrorWrapper = await mountModal({
      employee: { id: 404 },
    })
    const detailAlert = detailErrorWrapper.get('[role="alert"]')
    expect(detailAlert.classes()).toContain('bg-danger-subtle')
    expect(detailAlert.text()).toContain('Detail data pegawai gagal dimuat.')
  })
})
