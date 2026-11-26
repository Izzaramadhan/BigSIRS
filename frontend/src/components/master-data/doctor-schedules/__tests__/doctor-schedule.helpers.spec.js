import { describe, expect, it } from 'vitest'
import {
  buildScheduleRecapGroups,
  createDoctorScheduleForm,
  EMPTY,
  ensureSavedDoctorOptions,
  ensureSavedPolyclinicOptions,
  formatDayName,
  formatScheduleSummary,
  formatScheduleTime,
  formatScheduleTimeRange,
  getSafeDoctorScheduleErrorMessage,
  normalizeDayOfWeek,
  toDoctorSchedulePayload,
  validateDoctorScheduleForm,
} from '../doctor-schedule.helpers'

describe('doctor-schedule.helpers', () => {
  it('menormalisasi hari praktik, jam, dan ringkasan jadwal dengan benar', () => {
    expect(normalizeDayOfWeek(1)).toBe(1)
    expect(normalizeDayOfWeek('Rabu')).toBe(3)
    expect(normalizeDayOfWeek('minggu')).toBe(7)
    expect(normalizeDayOfWeek(99)).toBe('')

    expect(formatDayName(2)).toBe('Selasa')
    expect(formatDayName('Kamis')).toBe('Kamis')
    expect(formatDayName(null)).toBe(EMPTY)

    expect(formatScheduleTime('08:00:00')).toBe('08:00')
    expect(formatScheduleTime('')).toBe('')
    expect(formatScheduleTimeRange('08:00:00', '12:30:00')).toBe('08:00 – 12:30')
    expect(formatScheduleTimeRange('', '')).toBe(EMPTY)

    expect(
      formatScheduleSummary({
        doctor: { name: 'dr. Andi' },
        polyclinic: { name: 'Poli Anak' },
        day_of_week: 1,
        start_time: '08:00:00',
        end_time: '12:00:00',
      }),
    ).toBe('dr. Andi di Poli Anak (Senin, 08:00 – 12:00)')
  })

  it('menjaga opsi dokter dan poliklinik tersimpan pada mode Edit meskipun tidak termasuk hasil lookup aktif', () => {
    const doctors = ensureSavedDoctorOptions(
      [{ id: 10, name: 'dr. Aktif', specialization_name: 'Anak', is_active: true }],
      {
        doctor_id: 99,
        doctor: { id: 99, name: 'dr. Nonaktif Lama', specialization: 'Bedah' },
      },
    )
    expect(doctors.map((d) => d.value)).toEqual([99, 10])
    expect(doctors[0].label).toBe('dr. Nonaktif Lama — Bedah')
    expect(doctors[0].isActive).toBe(false)

    const polyclinics = ensureSavedPolyclinicOptions([{ id: 5, name: 'Poli Umum' }], {
      polyclinic_id: 77,
      polyclinic: { id: 77, name: 'Poli Lama' },
    })
    expect(polyclinics.map((p) => p.value)).toEqual([77, 5])
    expect(polyclinics[0].label).toBe('Poli Lama')
  })

  it('mengelompokkan rekap mingguan per poliklinik -> dokter -> hari dan mengurutkan sesi', () => {
    const groups = buildScheduleRecapGroups([
      {
        id: 2,
        polyclinic_id: 10,
        polyclinic: { id: 10, name: 'Poli Penyakit Dalam' },
        doctor_id: 101,
        doctor: { id: 101, name: 'dr. Andi' },
        day_of_week: 1,
        start_time: '13:00:00',
        end_time: '16:00:00',
      },
      {
        id: 1,
        polyclinic_id: 10,
        polyclinic: { id: 10, name: 'Poli Penyakit Dalam' },
        doctor_id: 101,
        doctor: { id: 101, name: 'dr. Andi' },
        day_of_week: 1,
        start_time: '08:00:00',
        end_time: '12:00:00',
      },
      {
        id: 3,
        polyclinic_id: 20,
        polyclinic: { id: 20, name: 'Poli Anak' },
        doctor_id: 102,
        doctor: { id: 102, name: 'dr. Budi' },
        day_of_week: 'Rabu',
        start_time: '09:00',
        end_time: '11:00',
      },
    ])

    expect(groups.map((g) => g.name)).toEqual(['Poli Anak', 'Poli Penyakit Dalam'])
    expect(groups[0].doctors[0].days[3]).toHaveLength(1)
    expect(groups[1].doctors[0].days[1].map((s) => s.id)).toEqual([1, 2])
  })

  it('memvalidasi field wajib, jam selesai > jam mulai, kuota, membentuk payload, dan menyamarkan SQL error', () => {
    expect(validateDoctorScheduleForm(createDoctorScheduleForm())).toEqual({
      doctor_id: 'Dokter wajib dipilih.',
      polyclinic_id: 'Poliklinik wajib dipilih.',
      day_of_week: 'Hari praktik wajib dipilih.',
      start_time: 'Jam mulai wajib diisi.',
      end_time: 'Jam selesai wajib diisi.',
    })

    const invalidTime = {
      doctor_id: 1,
      polyclinic_id: 2,
      day_of_week: 3,
      start_time: '14:00',
      end_time: '12:00',
      online_quota: -1,
    }
    expect(validateDoctorScheduleForm(invalidTime)).toEqual({
      end_time: 'Jam selesai harus lebih besar dari jam mulai.',
      online_quota: 'Kuota online harus berupa bilangan bulat minimal 0.',
    })

    const payload = toDoctorSchedulePayload({
      doctor_id: '10',
      polyclinic_id: '5',
      day_of_week: '2',
      start_time: '08:00:00',
      end_time: '12:00:00',
      is_holiday: false,
      online_quota: '',
    })
    expect(payload).toEqual({
      doctor_id: 10,
      polyclinic_id: 5,
      day_of_week: 2,
      start_time: '08:00',
      end_time: '12:00',
      is_holiday: false,
      online_quota: null,
    })

    expect(
      getSafeDoctorScheduleErrorMessage(
        { status: 500, message: 'SQLSTATE[42S22]: Unknown column' },
        'Jadwal dokter gagal disimpan.',
      ),
    ).toBe('Jadwal dokter gagal disimpan.')
    expect(
      getSafeDoctorScheduleErrorMessage(
        { status: 422, message: 'Dokter sudah memiliki jadwal yang bertabrakan.' },
        'Jadwal dokter gagal disimpan.',
      ),
    ).toBe('Dokter sudah memiliki jadwal yang bertabrakan.')
  })
})
