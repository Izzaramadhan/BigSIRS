import { flushPromises, mount } from '@vue/test-utils'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import DoctorScheduleFormModal from '../DoctorScheduleFormModal.vue'
import lookupService from '@/services/lookup.service'
import { polyclinicService } from '@/services/polyclinic'

vi.mock('@/services/lookup.service', () => ({
  default: {
    getDoctors: vi.fn(),
  },
}))

vi.mock('@/services/polyclinic', () => ({
  polyclinicService: {
    getPolyclinics: vi.fn(),
  },
}))

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
  const wrapper = mount(DoctorScheduleFormModal, {
    props: {
      open: true,
      schedule: null,
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

describe('DoctorScheduleFormModal', () => {
  beforeEach(() => {
    vi.resetAllMocks()
    lookupService.getDoctors.mockResolvedValue({
      data: [
        { id: 101, name: 'dr. Andi Sp.PD', specialization_name: 'Penyakit Dalam', is_active: true },
        { id: 102, name: 'dr. Budi Sp.A', specialization_name: 'Anak', is_active: true },
      ],
    })
    polyclinicService.getPolyclinics.mockResolvedValue({
      data: {
        data: [
          { id: 10, name: 'Poli Penyakit Dalam' },
          { id: 20, name: 'Poli Anak' },
        ],
      },
    })
  })

  afterEach(() => {
    document.body.innerHTML = ''
  })

  it('memvalidasi field wajib dan aturan end_time > start_time sebelum submit', async () => {
    const wrapper = await mountModal()

    await wrapper.get('form').trigger('submit.prevent')

    expect(wrapper.text()).toContain('Dokter wajib dipilih.')
    expect(wrapper.text()).toContain('Poliklinik wajib dipilih.')
    expect(wrapper.text()).toContain('Hari praktik wajib dipilih.')
    expect(wrapper.text()).toContain('Jam mulai wajib diisi.')
    expect(wrapper.text()).toContain('Jam selesai wajib diisi.')
    expect(wrapper.emitted('submit')).toBeFalsy()

    await findControlByLabel(wrapper, 'Nama Dokter').setValue('101')
    await findControlByLabel(wrapper, 'Poliklinik').setValue('10')
    await findControlByLabel(wrapper, 'Hari Praktik').setValue('1')
    await findControlByLabel(wrapper, 'Jam Mulai').setValue('14:00')
    await findControlByLabel(wrapper, 'Jam Selesai').setValue('12:00')
    await wrapper.get('form').trigger('submit.prevent')

    expect(wrapper.text()).toContain('Jam selesai harus lebih besar dari jam mulai.')
    expect(wrapper.emitted('submit')).toBeFalsy()
  })

  it('mengirim payload bersih saat form tambah jadwal valid disubmit', async () => {
    const wrapper = await mountModal()

    await findControlByLabel(wrapper, 'Nama Dokter').setValue('101')
    await findControlByLabel(wrapper, 'Poliklinik').setValue('20')
    await findControlByLabel(wrapper, 'Hari Praktik').setValue('3')
    await findControlByLabel(wrapper, 'Jam Mulai').setValue('08:00')
    await findControlByLabel(wrapper, 'Jam Selesai').setValue('12:00')
    await findControlByLabel(wrapper, 'Kuota Online').setValue('25')

    await wrapper.get('form').trigger('submit.prevent')

    expect(wrapper.emitted('submit')[0][0]).toEqual({
      doctor_id: 101,
      polyclinic_id: 20,
      day_of_week: 3,
      start_time: '08:00',
      end_time: '12:00',
      is_holiday: false,
      online_quota: 25,
    })
  })

  it('menghidrasi nilai lama saat edit termasuk dokter/poliklinik nonaktif yang tidak ada di halaman lookup aktif', async () => {
    lookupService.getDoctors.mockResolvedValue({
      data: [{ id: 101, name: 'dr. Andi Sp.PD', is_active: true }],
    })
    polyclinicService.getPolyclinics.mockResolvedValue({
      data: { data: [{ id: 10, name: 'Poli Penyakit Dalam' }] },
    })

    const wrapper = await mountModal({
      schedule: {
        id: 5,
        doctor_id: 999,
        doctor: { id: 999, name: 'dr. Nonaktif Tersimpan', specialization: 'Saraf' },
        polyclinic_id: 888,
        polyclinic: { id: 888, name: 'Poli Saraf Lama' },
        day_of_week: 4,
        start_time: '09:00:00',
        end_time: '11:30:00',
        is_holiday: true,
        online_quota: null,
      },
    })

    expect(lookupService.getDoctors).toHaveBeenCalledWith(
      expect.objectContaining({ is_active: 1, ids: 999 }),
    )
    expect(findControlByLabel(wrapper, 'Nama Dokter').element.value).toBe('999')
    expect(findControlByLabel(wrapper, 'Poliklinik').element.value).toBe('888')
    expect(findControlByLabel(wrapper, 'Hari Praktik').element.value).toBe('4')
    expect(findControlByLabel(wrapper, 'Jam Mulai').element.value).toBe('09:00')
    expect(findControlByLabel(wrapper, 'Jam Selesai').element.value).toBe('11:30')
    expect(findControlByLabel(wrapper, 'Status (Libur)').element.value).toBe('1')
  })

  it('menampilkan error 422 bentrok jadwal per field, alert error umum/lookup, dan mengunci close saat saving', async () => {
    lookupService.getDoctors.mockRejectedValueOnce(new Error('Network down'))
    const wrapper = await mountModal({
      saving: true,
      generalError: 'Dokter sudah memiliki jadwal yang bertabrakan pada hari dan waktu tersebut.',
      serverErrors: {
        start_time: 'Jadwal bertabrakan dengan jadwal yang sudah ada.',
        end_time: 'Jadwal bertabrakan dengan jadwal yang sudah ada.',
      },
    })

    expect(wrapper.get('[role="alert"]').text()).toContain('bertabrakan')
    expect(wrapper.get('[role="status"]').text()).toContain('data referensi')
    expect(findControlByLabel(wrapper, 'Jam Mulai').attributes('aria-invalid')).toBe('true')

    await findButtonByText(wrapper, 'Batal').trigger('click')
    expect(wrapper.emitted('close')).toBeFalsy()
  })
})
