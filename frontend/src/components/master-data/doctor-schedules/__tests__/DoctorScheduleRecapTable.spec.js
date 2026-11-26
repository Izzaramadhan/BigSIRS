import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import DoctorScheduleRecapTable from '../DoctorScheduleRecapTable.vue'

describe('DoctorScheduleRecapTable.vue', () => {
  const sampleSchedules = [
    {
      id: 1,
      polyclinic_id: 10,
      polyclinic: { id: 10, name: 'Poli Penyakit Dalam' },
      doctor_id: 101,
      doctor: { id: 101, name: 'dr. Andi Sp.PD', specialization: 'Spesialis Penyakit Dalam' },
      day_of_week: 1,
      start_time: '08:00:00',
      end_time: '12:00:00',
      is_holiday: false,
    },
    {
      id: 2,
      polyclinic_id: 10,
      polyclinic: { id: 10, name: 'Poli Penyakit Dalam' },
      doctor_id: 101,
      doctor: { id: 101, name: 'dr. Andi Sp.PD', specialization: 'Spesialis Penyakit Dalam' },
      day_of_week: 1,
      start_time: '13:00:00',
      end_time: '16:00:00',
      is_holiday: false,
    },
    {
      id: 3,
      polyclinic_id: 10,
      polyclinic: { id: 10, name: 'Poli Penyakit Dalam' },
      doctor_id: 102,
      doctor: { id: 102, name: 'dr. Budi Sp.PD', specialization: 'Spesialis Penyakit Dalam' },
      day_of_week: 2,
      start_time: '09:00:00',
      end_time: '13:00:00',
      is_holiday: false,
    },
    {
      id: 4,
      polyclinic_id: 20,
      polyclinic: { id: 20, name: 'Poli Jantung' },
      doctor_id: 101,
      doctor: { id: 101, name: 'dr. Andi Sp.PD', specialization: 'Spesialis Penyakit Dalam' },
      day_of_week: 3,
      start_time: '10:00:00',
      end_time: '14:00:00',
      is_holiday: true,
    },
  ]

  it('merender header dua tingkat "Jadwal Hari" beserta Senin sampai Minggu', () => {
    const wrapper = mount(DoctorScheduleRecapTable, {
      props: { schedules: sampleSchedules },
    })

    expect(wrapper.get('h2').text()).toBe('Data Jadwal Dokter')

    const topHeaders = wrapper.findAll('thead tr:first-child th')
    expect(topHeaders[0].text()).toBe('Nama Poliklinik')
    expect(topHeaders[1].text()).toBe('Nama Dokter')
    expect(topHeaders[2].text()).toBe('Jadwal Hari')
    expect(topHeaders[2].attributes('colspan')).toBe('7')

    const dayHeaders = wrapper.findAll('thead tr:nth-child(2) th').map((th) => th.text())
    expect(dayHeaders).toEqual(['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'])
  })

  it('merender empty state saat schedules kosong', () => {
    const wrapper = mount(DoctorScheduleRecapTable, {
      props: { schedules: [], loading: false },
    })

    expect(wrapper.text()).toContain('Belum ada jadwal dokter')
    expect(wrapper.find('table').exists()).toBe(false)
  })

  it('merender loading state saat loading dan belum ada data', () => {
    const wrapper = mount(DoctorScheduleRecapTable, {
      props: { schedules: [], loading: true },
    })

    expect(wrapper.text()).toContain('Memuat data rekap jadwal dokter...')
  })

  it('merender error state melalui BaseAlert dan memancarkan retry saat tombol diklik', async () => {
    const wrapper = mount(DoctorScheduleRecapTable, {
      props: { schedules: [], error: 'Gagal memuat rekap jadwal' },
    })

    const alert = wrapper.get('[role="alert"]')
    expect(alert.text()).toContain('Gagal memuat rekap jadwal')

    await wrapper.findAll('button').find((b) => b.text().trim() === 'Coba Lagi').trigger('click')
    expect(wrapper.emitted('retry')).toBeTruthy()
  })

  it('mengelompokkan jadwal per poliklinik dan dokter dengan rowspan yang benar', () => {
    const wrapper = mount(DoctorScheduleRecapTable, {
      props: { schedules: sampleSchedules },
    })

    const rowspanCells = wrapper.findAll('tbody td[rowspan]')
    expect(rowspanCells).toHaveLength(2)

    const dalamCell = rowspanCells.find((c) => c.text().includes('Poli Penyakit Dalam'))
    expect(dalamCell.attributes('rowspan')).toBe('2')

    const andiMentions = wrapper.findAll('tbody tr').filter((tr) => tr.text().includes('dr. Andi Sp.PD'))
    expect(andiMentions).toHaveLength(2)
  })

  it('memformat rentang jam HH:mm – HH:mm dan menampilkan em-dash pada hari kosong', () => {
    const wrapper = mount(DoctorScheduleRecapTable, {
      props: { schedules: sampleSchedules },
    })

    const text = wrapper.text()
    expect(text).toContain('08:00 – 12:00')
    expect(text).toContain('13:00 – 16:00')
    expect(text).toContain('09:00 – 13:00')
    expect(text).toContain('10:00 – 14:00')
    expect(text).toContain('—')
  })

  it('mengurutkan beberapa sesi pada hari yang sama berdasarkan start_time', () => {
    const reversedSessions = [
      {
        id: 2,
        polyclinic_id: 10,
        polyclinic: { id: 10, name: 'Poli Penyakit Dalam' },
        doctor_id: 101,
        doctor: { id: 101, name: 'dr. Andi Sp.PD' },
        day_of_week: 1,
        start_time: '13:00:00',
        end_time: '16:00:00',
        is_holiday: false,
      },
      {
        id: 1,
        polyclinic_id: 10,
        polyclinic: { id: 10, name: 'Poli Penyakit Dalam' },
        doctor_id: 101,
        doctor: { id: 101, name: 'dr. Andi Sp.PD' },
        day_of_week: 1,
        start_time: '08:00:00',
        end_time: '12:00:00',
        is_holiday: false,
      },
    ]

    const wrapper = mount(DoctorScheduleRecapTable, {
      props: { schedules: reversedSessions },
    })

    const mondayCellText = wrapper.find('tbody tr:first-child td:nth-child(3)').text()
    expect(mondayCellText.indexOf('08:00 – 12:00')).toBeLessThan(
      mondayCellText.indexOf('13:00 – 16:00'),
    )
  })

  it('menandai sesi libur dengan badge Libur dan kelas token danger', () => {
    const wrapper = mount(DoctorScheduleRecapTable, {
      props: { schedules: sampleSchedules },
    })

    const holidayPill = wrapper.get('span[title="Jadwal Libur"]')
    expect(holidayPill.text()).toBe('Libur')
    expect(wrapper.get('.bg-danger-subtle').text()).toContain('10:00 – 14:00')
  })
})
