import { flushPromises, mount } from '@vue/test-utils'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import DoctorFormModal from '@/components/master-data/doctors/DoctorFormModal.vue'
import lookupService from '@/services/lookup.service'
import doctorService from '@/services/master-data/doctors.service'

vi.mock('@/services/master-data/doctors.service', () => ({
  default: {
    getDoctor: vi.fn(),
  },
}))

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

const mockSpecializations = [
  { id: 10, legacy_id: 2, name: 'Spesialis Anak' },
  { id: 11, legacy_id: 5, name: 'Penyakit Dalam' },
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
  const wrapper = mount(DoctorFormModal, {
    props: {
      open: true,
      doctor: null,
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

describe('DoctorFormModal', () => {
  beforeEach(() => {
    vi.resetAllMocks()
    lookupService.getSpecializations.mockResolvedValue(mockSpecializations)
    lookupService.getEducations.mockResolvedValue([{ id: 1, name: 'Spesialis' }])
    lookupService.getOccupations.mockResolvedValue([{ id: 2, name: 'Dokter' }])
    lookupService.getProvinces.mockResolvedValue([{ id: 35, name: 'Jawa Timur' }])
    lookupService.getRegencies.mockResolvedValue([{ id: 3578, name: 'Kota Surabaya' }])
    lookupService.getDistricts.mockResolvedValue([{ id: 357801, name: 'Gubeng' }])
    lookupService.getVillages.mockResolvedValue([{ id: 3578011, name: 'Airlangga' }])

    vi.stubGlobal('URL', {
      createObjectURL: vi.fn(() => 'blob:mock-doc-sig'),
      revokeObjectURL: vi.fn(),
    })
  })

  afterEach(() => {
    vi.unstubAllGlobals()
    document.body.innerHTML = ''
  })

  it('merender 3 section termasuk Nomor HP dan Alergi, label Laki-laki -> L, dan memvalidasi wajib sebelum submit', async () => {
    const wrapper = await mountModal()

    expect(wrapper.text()).toContain('1. Identitas Person / Pegawai')
    expect(wrapper.text()).toContain('2. Alamat & Kontak')
    expect(wrapper.text()).toContain('3. Profil Profesional (Dokter)')

    const genderSelect = findControlByLabel(wrapper, 'Jenis Kelamin')
    const lakiOption = genderSelect.findAll('option').find((opt) => opt.text() === 'Laki-laki')
    expect(lakiOption.element.value).toBe('L')

    expect(findControlByLabel(wrapper, 'Nomor HP').exists()).toBe(true)
    expect(findControlByLabel(wrapper, 'Alergi').exists()).toBe(true)

    await wrapper.get('form').trigger('submit.prevent')
    expect(wrapper.text()).toContain('Nama lengkap wajib diisi.')
    expect(wrapper.text()).toContain('Jenis kelamin wajib dipilih.')
    expect(wrapper.text()).toContain('Spesialisasi wajib dipilih.')
    expect(wrapper.emitted('submit')).toBeFalsy()
  })

  it('mengirim payload create terstruktur berisi person (gender, phone, allergies) dan professional', async () => {
    const wrapper = await mountModal()

    await findControlByLabel(wrapper, 'Nomor KTP (NIK)').setValue('3578010101850001')
    await findControlByLabel(wrapper, 'Nama Lengkap (beserta gelar)').setValue('dr. Budi, Sp.A')
    await findControlByLabel(wrapper, 'Jenis Kelamin').setValue('L')
    await findControlByLabel(wrapper, 'Alergi').setValue('Seafood')
    await findControlByLabel(wrapper, 'Nomor HP').setValue('081234567890')
    await findControlByLabel(wrapper, 'Spesialisasi').setValue('10')

    await wrapper.get('form').trigger('submit.prevent')

    const submitted = wrapper.emitted('submit')[0][0]
    expect(submitted.person).toMatchObject({
      national_id: '3578010101850001',
      name: 'dr. Budi, Sp.A',
      gender: 'L',
      allergies: 'Seafood',
      phone: '081234567890',
    })
    expect(submitted.professional.specialization_id).toBe(10)
    expect(submitted.remove_signature).toBe(false)
  })

  it('menghidrasi edit dokter dari getDoctor, menjaga legacy_id spesialisasi, relasi employee_id, wilayah, dan tanda tangan tersimpan', async () => {
    doctorService.getDoctor.mockResolvedValue({
      id: 9,
      employee_id: 44,
      id_spesialisasi: 5,
      str_number: 'STR-99',
      sip_number: 'SIP-99',
      signature_url: 'http://localhost/storage/signatures/dokter9.png',
      employee: {
        id: 44,
        name: 'dr. Sari',
        gender: 'P',
        phone: '0811223344',
        allergies: 'Debu',
        province_id: 35,
        regency_id: 3578,
        district_id: 357801,
        village_id: 3578011,
      },
    })

    const wrapper = await mountModal({
      doctor: { id: 9, name: 'dr. Sari', specialization: null },
    })

    expect(doctorService.getDoctor).toHaveBeenCalledWith(9)
    expect(findControlByLabel(wrapper, 'Spesialisasi').element.value).toBe('11')
    expect(findControlByLabel(wrapper, 'Jenis Kelamin').element.value).toBe('P')
    expect(findControlByLabel(wrapper, 'Nomor HP').element.value).toBe('0811223344')
    expect(findControlByLabel(wrapper, 'Alergi').element.value).toBe('Debu')
    expect(findControlByLabel(wrapper, 'Provinsi').element.value).toBe('35')
    expect(findControlByLabel(wrapper, 'Kabupaten / Kota').element.value).toBe('3578')

    // Edit tanpa mengganti tanda tangan tetap remove_signature = false
    await wrapper.get('form').trigger('submit.prevent')
    const submitted = wrapper.emitted('submit')[0][0]
    expect(submitted.employee_id).toBe(44)
    expect(submitted.signature).toBeNull()
    expect(submitted.remove_signature).toBe(false)
  })

  it('menampilkan error 422 nested person.gender pada field Jenis Kelamin dan mencegah close saat saving', async () => {
    const wrapper = await mountModal({
      serverErrors: {
        'person.gender': 'The person.gender field is required.',
      },
      saving: true,
    })

    const genderSelect = findControlByLabel(wrapper, 'Jenis Kelamin')
    expect(genderSelect.attributes('aria-invalid')).toBe('true')
    expect(wrapper.text()).toContain('The person.gender field is required.')

    await findButtonByText(wrapper, 'Batal').trigger('click')
    expect(wrapper.emitted('close')).toBeFalsy()
  })

  it('memvalidasi tipe/ukuran tanda tangan, mendukung preview, batalkan pilihan, dan hapus tanda tangan', async () => {
    const wrapper = await mountModal({
      doctor: {
        id: 5,
        employee_id: 12,
        specialization_id: 10,
        employee: { id: 12, name: 'dr. Eka', gender: 'L' },
        signature_url: 'http://localhost/storage/signatures/saved.png',
      },
    })

    const fileInput = findControlByLabel(wrapper, 'Tanda Tangan')
    const badFile = new File(['bad'], 'file.pdf', { type: 'application/pdf' })
    Object.defineProperty(fileInput.element, 'files', { value: [badFile], configurable: true })
    await fileInput.trigger('change')
    expect(wrapper.text()).toContain('Format file tanda tangan harus PNG atau JPG.')

    const bigFile = new File(['big'], 'big.png', { type: 'image/png' })
    Object.defineProperty(bigFile, 'size', { value: 3 * 1024 * 1024 })
    Object.defineProperty(fileInput.element, 'files', { value: [bigFile], configurable: true })
    await fileInput.trigger('change')
    expect(wrapper.text()).toContain('Ukuran file tanda tangan maksimal 2MB.')

    const validFile = new File(['ok'], 'baru.png', { type: 'image/png' })
    Object.defineProperty(fileInput.element, 'files', { value: [validFile], configurable: true })
    await fileInput.trigger('change')
    expect(wrapper.get('img[alt="Preview tanda tangan"]').attributes('src')).toBe('blob:mock-doc-sig')

    await findButtonByText(wrapper, 'Batalkan Pilihan').trigger('click')
    expect(wrapper.get('img[alt="Preview tanda tangan"]').attributes('src')).toBe(
      'http://localhost/storage/signatures/saved.png',
    )

    await findButtonByText(wrapper, 'Hapus Tanda Tangan').trigger('click')
    expect(wrapper.text()).toContain('Tanda tangan tersimpan akan dihapus saat data disimpan.')
    await wrapper.get('form').trigger('submit.prevent')
    expect(wrapper.emitted('submit')[0][0].remove_signature).toBe(true)
  })

  it('mengizinkan pengosongan nilai alergi saat edit dokter tanpa menghilangkan input lain', async () => {
    const wrapper = await mountModal({
      doctor: {
        id: 15,
        employee_id: 40,
        specialization_id: 10,
        employee: {
          id: 40,
          name: 'dr. Rina',
          gender: 'P',
          phone: '08123456789',
          allergies: 'Penisilin',
        },
      },
    })

    const allergyControl = findControlByLabel(wrapper, 'Alergi')
    expect(allergyControl.element.value).toBe('Penisilin')

    await allergyControl.setValue('')
    await wrapper.get('form').trigger('submit.prevent')

    const submitted = wrapper.emitted('submit')[0][0]
    expect(submitted.person.allergies).toBeNull()
    expect(submitted.person.phone).toBe('08123456789')
    expect(submitted.person.name).toBe('dr. Rina')
  })
})
