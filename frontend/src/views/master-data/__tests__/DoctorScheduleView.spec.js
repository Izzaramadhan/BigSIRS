import { flushPromises, mount } from '@vue/test-utils'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import DoctorScheduleView from '../DoctorScheduleView.vue'
import { useToast } from '@/composables/core/useToast'
import lookupService from '@/services/lookup.service'
import doctorScheduleService from '@/services/master-data/doctor-schedules.service'
import { polyclinicService } from '@/services/polyclinic'

vi.mock('@/services/master-data/doctor-schedules.service', () => ({
  default: {
    getDoctorSchedules: vi.fn(),
    getRecap: vi.fn(),
    getDoctorSchedule: vi.fn(),
    createDoctorSchedule: vi.fn(),
    updateDoctorSchedule: vi.fn(),
    deleteDoctorSchedule: vi.fn(),
  },
}))

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

const mockPaginatedResponse = {
  data: [
    {
      id: 1,
      doctor_id: 101,
      doctor: { id: 101, name: 'dr. Andi Sp.PD' },
      polyclinic_id: 10,
      polyclinic: { id: 10, name: 'Poli Penyakit Dalam' },
      day_of_week: 1,
      start_time: '08:00',
      end_time: '12:00',
      is_holiday: false,
      online_quota: 20,
    },
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

const mockRecapResponse = {
  data: [
    {
      id: 1,
      doctor_id: 101,
      doctor: { id: 101, name: 'dr. Andi Sp.PD', specialization: 'Spesialis Penyakit Dalam' },
      polyclinic_id: 10,
      polyclinic: { id: 10, name: 'Poli Penyakit Dalam' },
      day_of_week: 1,
      start_time: '08:00',
      end_time: '12:00',
      is_holiday: false,
    },
    {
      id: 2,
      doctor_id: 102,
      doctor: { id: 102, name: 'dr. Budi Sp.A', specialization: 'Spesialis Anak' },
      polyclinic_id: 20,
      polyclinic: { id: 20, name: 'Poli Anak' },
      day_of_week: 2,
      start_time: '09:00',
      end_time: '13:00',
      is_holiday: false,
    },
  ],
}

const toast = useToast()
const toastEntries = () => toast.toasts.value

async function mountView() {
  const wrapper = mount(DoctorScheduleView, {
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

describe('DoctorScheduleView.vue', () => {
  beforeEach(() => {
    vi.resetAllMocks()
    vi.useRealTimers()
    toast.clear()

    doctorScheduleService.getDoctorSchedules.mockResolvedValue(mockPaginatedResponse)
    doctorScheduleService.getRecap.mockResolvedValue(mockRecapResponse)
    lookupService.getDoctors.mockResolvedValue({
      data: [
        { id: 101, name: 'dr. Andi Sp.PD', specialization_name: 'Penyakit Dalam', is_active: true },
      ],
    })
    polyclinicService.getPolyclinics.mockResolvedValue({
      data: {
        data: [{ id: 10, name: 'Poli Penyakit Dalam' }],
      },
    })
  })

  afterEach(() => {
    vi.useRealTimers()
    document.body.innerHTML = ''
  })

  it('memuat daftar berpaginasi dan rekap mingguan saat mount, merender 8 kolom standar, serta tanpa filter yang dihapus', async () => {
    const wrapper = await mountView()

    expect(doctorScheduleService.getDoctorSchedules).toHaveBeenCalledTimes(1)
    expect(doctorScheduleService.getRecap).toHaveBeenCalledTimes(1)

    expect(wrapper.get('h1').text()).toBe('Data Jadwal Dokter')
    const headers = wrapper
      .findAll('table')
      .at(0)
      .findAll('thead th')
      .map((th) => th.text())
    expect(headers).toEqual([
      'No',
      'Nama Dokter',
      'Poliklinik',
      'Hari Praktik',
      'Jam Mulai',
      'Jam Selesai',
      'Status',
      'Aksi',
    ])

    const text = wrapper.text()
    expect(text).not.toContain('Semua Hari')
    expect(text).not.toContain('Semua Status')
    expect(text).not.toContain('Reset Filter')
  })

  it('perpindahan halaman paginasi memuat halaman baru tanpa me-refetch rekap, sedangkan refresh memuat ulang keduanya', async () => {
    doctorScheduleService.getDoctorSchedules
      .mockResolvedValueOnce(mockPaginatedResponse)
      .mockResolvedValueOnce({
        data: [
          {
            id: 11,
            doctor_id: 101,
            doctor: { id: 101, name: 'dr. Halaman Dua' },
            polyclinic_id: 10,
            polyclinic: { id: 10, name: 'Poli Penyakit Dalam' },
            day_of_week: 5,
            start_time: '08:00',
            end_time: '10:00',
            is_holiday: false,
          },
        ],
        meta: { current_page: 2, last_page: 2, per_page: 10, total: 12, from: 11, to: 12 },
      })
      .mockResolvedValueOnce(mockPaginatedResponse)

    const wrapper = await mountView()

    await wrapper.get('button[aria-label="Halaman berikutnya"]').trigger('click')
    await flushPromises()

    expect(doctorScheduleService.getDoctorSchedules).toHaveBeenCalledTimes(2)
    expect(doctorScheduleService.getDoctorSchedules).toHaveBeenLastCalledWith(
      expect.objectContaining({ page: 2 }),
    )
    expect(doctorScheduleService.getRecap).toHaveBeenCalledTimes(1)
    expect(wrapper.findAll('table').at(0).get('tbody tr td:first-child').text()).toBe('11')

    await wrapper.get('button[aria-label="Muat ulang data"]').trigger('click')
    await flushPromises()

    expect(doctorScheduleService.getDoctorSchedules).toHaveBeenCalledTimes(3)
    expect(doctorScheduleService.getRecap).toHaveBeenCalledTimes(2)
  })

  it('melakukan debounce pencarian 400ms, menampilkan empty state pencarian, dan mencegah stale response menimpa data terbaru', async () => {
    vi.useFakeTimers()
    const wrapper = await mountView()

    let resolveSlow
    const slowPromise = new Promise((resolve) => {
      resolveSlow = resolve
    })
    doctorScheduleService.getDoctorSchedules.mockReturnValueOnce(slowPromise)

    const searchInput = wrapper.get('input[placeholder="Cari dokter atau poliklinik..."]')
    await searchInput.setValue('Andi')
    await vi.advanceTimersByTimeAsync(400)

    // Pencarian kedua selesai lebih cepat (hasil kosong)
    doctorScheduleService.getDoctorSchedules.mockResolvedValueOnce({
      data: [],
      meta: { current_page: 1, last_page: 1, per_page: 10, total: 0, from: 0, to: 0 },
    })
    await searchInput.setValue('TidakAda')
    await vi.advanceTimersByTimeAsync(400)
    await flushPromises()

    expect(wrapper.text()).toContain('Jadwal dokter tidak ditemukan')

    // Request pertama yang lambat resolve belakangan — harus diabaikan
    resolveSlow(mockPaginatedResponse)
    await flushPromises()
    expect(wrapper.text()).toContain('Jadwal dokter tidak ditemukan')

    // Reset Pencarian memuat ulang data normal
    doctorScheduleService.getDoctorSchedules.mockResolvedValueOnce(mockPaginatedResponse)
    await findButtonByText(wrapper, 'Reset Pencarian').trigger('click')
    await flushPromises()
    expect(wrapper.text()).toContain('dr. Andi Sp.PD')
  })

  it('menyinkronkan tabel utama dan rekap setelah tambah, edit, hapus, serta menampilkan toast warning jika rekap gagal', async () => {
    doctorScheduleService.createDoctorSchedule.mockResolvedValue({ data: { id: 3 } })
    doctorScheduleService.updateDoctorSchedule.mockResolvedValue({ data: { id: 1 } })
    doctorScheduleService.deleteDoctorSchedule.mockResolvedValue({})

    const wrapper = await mountView()

    // 1. Tambah jadwal baru -> sinkron list + recap + toast sukses
    await findButtonByText(wrapper, 'Tambah Jadwal Dokter').trigger('click')
    await flushPromises()

    await findControlByLabel(wrapper, 'Nama Dokter').setValue('101')
    await findControlByLabel(wrapper, 'Poliklinik').setValue('10')
    await findControlByLabel(wrapper, 'Hari Praktik').setValue('3')
    await findControlByLabel(wrapper, 'Jam Mulai').setValue('10:00')
    await findControlByLabel(wrapper, 'Jam Selesai').setValue('14:00')
    await wrapper.get('form').trigger('submit.prevent')
    await flushPromises()

    expect(doctorScheduleService.createDoctorSchedule).toHaveBeenCalledWith({
      doctor_id: 101,
      polyclinic_id: 10,
      day_of_week: 3,
      start_time: '10:00',
      end_time: '14:00',
      is_holiday: false,
      online_quota: null,
    })
    expect(doctorScheduleService.getDoctorSchedules).toHaveBeenCalledTimes(2)
    expect(doctorScheduleService.getRecap).toHaveBeenCalledTimes(2)
    expect(toastEntries().at(-1)).toMatchObject({
      tone: 'success',
      message: 'Jadwal dokter berhasil ditambahkan.',
    })

    // 2. Edit jadwal + simulasi gagal muat rekap -> warning toast
    doctorScheduleService.getRecap.mockRejectedValueOnce(new Error('Network error on recap'))
    await wrapper
      .get('button[aria-label="Edit dr. Andi Sp.PD di Poli Penyakit Dalam (Senin, 08:00 – 12:00)"]')
      .trigger('click')
    await flushPromises()

    await findControlByLabel(wrapper, 'Hari Praktik').setValue('4')
    await wrapper.get('form').trigger('submit.prevent')
    await flushPromises()

    expect(doctorScheduleService.updateDoctorSchedule).toHaveBeenCalledWith(
      1,
      expect.objectContaining({ day_of_week: 4 }),
    )
    expect(toastEntries().slice(-2)).toMatchObject([
      { tone: 'success', message: 'Jadwal dokter berhasil diperbarui.' },
      { tone: 'danger', message: 'Data rekap jadwal gagal dimuat ulang. Silakan klik Coba Lagi pada rekap.' },
    ])

    // 3. Hapus jadwal -> sinkron list + recap
    await wrapper
      .get('button[aria-label="Hapus dr. Andi Sp.PD di Poli Penyakit Dalam (Senin, 08:00 – 12:00)"]')
      .trigger('click')
    await flushPromises()
    await findButtonByText(wrapper, 'Ya, Hapus').trigger('click')
    await flushPromises()

    expect(doctorScheduleService.deleteDoctorSchedule).toHaveBeenCalledWith(1)
    expect(doctorScheduleService.getDoctorSchedules).toHaveBeenCalledTimes(4)
    expect(doctorScheduleService.getRecap).toHaveBeenCalledTimes(4)
    expect(toastEntries().at(-1)).toMatchObject({
      tone: 'success',
      message: 'Jadwal dokter berhasil dihapus.',
    })
  })

  it('menampilkan error 422 bentrok jadwal dan state gagal memuat dengan tombol Muat Ulang', async () => {
    doctorScheduleService.getDoctorSchedules.mockRejectedValueOnce({
      response: { status: 500, data: { message: 'SQLSTATE[42000]: syntax' } },
    })

    const wrapper = await mountView()
    expect(wrapper.text()).toContain('Gagal memuat jadwal dokter')
    expect(wrapper.text()).toContain('Data jadwal dokter gagal dimuat.')

    doctorScheduleService.getDoctorSchedules.mockResolvedValueOnce(mockPaginatedResponse)
    await findButtonByText(wrapper, 'Muat Ulang').trigger('click')
    await flushPromises()

    doctorScheduleService.createDoctorSchedule.mockRejectedValueOnce({
      response: {
        status: 422,
        data: {
          message: 'Dokter sudah memiliki jadwal yang bertabrakan pada hari dan waktu tersebut.',
          errors: {
            start_time: ['Jadwal bertabrakan dengan jadwal yang sudah ada.'],
          },
        },
      },
    })

    await findButtonByText(wrapper, 'Tambah Jadwal Dokter').trigger('click')
    await flushPromises()
    await findControlByLabel(wrapper, 'Nama Dokter').setValue('101')
    await findControlByLabel(wrapper, 'Poliklinik').setValue('10')
    await findControlByLabel(wrapper, 'Hari Praktik').setValue('1')
    await findControlByLabel(wrapper, 'Jam Mulai').setValue('08:00')
    await findControlByLabel(wrapper, 'Jam Selesai').setValue('10:00')
    await wrapper.get('form').trigger('submit.prevent')
    await flushPromises()

    expect(wrapper.text()).toContain('Jadwal bertabrakan dengan jadwal yang sudah ada.')
    expect(toastEntries().at(-1)).toMatchObject({
      tone: 'danger',
      message: 'Dokter sudah memiliki jadwal yang bertabrakan pada hari dan waktu tersebut.',
    })
  })
})
