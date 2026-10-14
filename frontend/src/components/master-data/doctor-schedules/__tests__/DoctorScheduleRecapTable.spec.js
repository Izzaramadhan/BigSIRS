import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import DoctorScheduleRecapTable from '../DoctorScheduleRecapTable.vue';

describe('DoctorScheduleRecapTable.vue', () => {
  const sampleSchedules = [
    {
      id: 1,
      polyclinic_id: 10,
      polyclinic: { id: 10, name: 'Poli Penyakit Dalam' },
      doctor_id: 101,
      doctor: { id: 101, name: 'dr. Andi Sp.PD', specialization: 'Spesialis Penyakit Dalam' },
      day_of_week: 1, // Senin
      start_time: '08:00:00',
      end_time: '12:00:00',
      is_holiday: false
    },
    {
      id: 2,
      polyclinic_id: 10,
      polyclinic: { id: 10, name: 'Poli Penyakit Dalam' },
      doctor_id: 101,
      doctor: { id: 101, name: 'dr. Andi Sp.PD', specialization: 'Spesialis Penyakit Dalam' },
      day_of_week: 1, // Senin session 2 (later time)
      start_time: '13:00:00',
      end_time: '16:00:00',
      is_holiday: false
    },
    {
      id: 3,
      polyclinic_id: 10,
      polyclinic: { id: 10, name: 'Poli Penyakit Dalam' },
      doctor_id: 102,
      doctor: { id: 102, name: 'dr. Budi Sp.PD', specialization: 'Spesialis Penyakit Dalam' },
      day_of_week: 2, // Selasa
      start_time: '09:00:00',
      end_time: '13:00:00',
      is_holiday: false
    },
    {
      id: 4,
      polyclinic_id: 20,
      polyclinic: { id: 20, name: 'Poli Jantung' },
      doctor_id: 101, // dr. Andi also in Poli Jantung
      doctor: { id: 101, name: 'dr. Andi Sp.PD', specialization: 'Spesialis Penyakit Dalam' },
      day_of_week: 3, // Rabu - Libur
      start_time: '10:00:00',
      end_time: '14:00:00',
      is_holiday: true
    }
  ];

  it('renders table headers with two-tier header "Jadwal Hari"', () => {
    const wrapper = mount(DoctorScheduleRecapTable, {
      props: { schedules: sampleSchedules }
    });

    expect(wrapper.find('.recap-title').text()).toBe('Data Jadwal Dokter');
    expect(wrapper.find('th.col-polyclinic').text()).toBe('Nama Poliklinik');
    expect(wrapper.find('th.col-doctor').text()).toBe('Nama Dokter');
    
    const scheduleHeader = wrapper.find('th.col-schedule-header');
    expect(scheduleHeader.exists()).toBe(true);
    expect(scheduleHeader.text()).toBe('Jadwal Hari');
    expect(scheduleHeader.attributes('colspan')).toBe('7');

    const dayHeaders = wrapper.findAll('th.col-day');
    expect(dayHeaders).toHaveLength(7);
    expect(dayHeaders[0].text()).toBe('Senin');
    expect(dayHeaders[1].text()).toBe('Selasa');
    expect(dayHeaders[2].text()).toBe('Rabu');
    expect(dayHeaders[3].text()).toBe('Kamis');
    expect(dayHeaders[4].text()).toBe('Jumat');
    expect(dayHeaders[5].text()).toBe('Sabtu');
    expect(dayHeaders[6].text()).toBe('Minggu');
  });

  it('renders empty state when schedules list is empty', () => {
    const wrapper = mount(DoctorScheduleRecapTable, {
      props: { schedules: [], loading: false }
    });

    expect(wrapper.find('.empty-state').exists()).toBe(true);
    expect(wrapper.find('.empty-title').text()).toContain('Belum ada jadwal dokter');
    expect(wrapper.find('.data-table').exists()).toBe(false);
  });

  it('renders loading state when loading and schedules is empty', () => {
    const wrapper = mount(DoctorScheduleRecapTable, {
      props: { schedules: [], loading: true }
    });

    expect(wrapper.find('.loading-state').exists()).toBe(true);
    expect(wrapper.text()).toContain('Memuat data rekap jadwal dokter...');
  });

  it('renders error state and emits retry when button clicked', async () => {
    const wrapper = mount(DoctorScheduleRecapTable, {
      props: { schedules: [], error: 'Gagal memuat rekap jadwal' }
    });

    expect(wrapper.find('.error-state').exists()).toBe(true);
    expect(wrapper.find('.state-text').text()).toContain('Gagal memuat rekap jadwal');

    const retryBtn = wrapper.find('.btn-retry');
    expect(retryBtn.exists()).toBe(true);
    await retryBtn.trigger('click');

    expect(wrapper.emitted('retry')).toBeTruthy();
  });

  it('groups schedules by polyclinic_id and doctor_id with correct rowspan', () => {
    const wrapper = mount(DoctorScheduleRecapTable, {
      props: { schedules: sampleSchedules }
    });

    // Poli Penyakit Dalam has 2 doctors: dr. Andi and dr. Budi
    // So the polyclinic cell should have rowspan="2"
    const polyCells = wrapper.findAll('td.cell-polyclinic');
    expect(polyCells).toHaveLength(2); // Poli Jantung, Poli Penyakit Dalam (alphabetical)

    // Poli Jantung (1 doctor) -> rowspan="1"
    // Poli Penyakit Dalam (2 doctors) -> rowspan="2"
    const dalamCell = polyCells.find(c => c.text().includes('Poli Penyakit Dalam'));
    expect(dalamCell).toBeDefined();
    expect(dalamCell.attributes('rowspan')).toBe('2');

    // Doctor practicing in multiple polyclinics (dr. Andi) appears in both polyclinics
    const doctorNames = wrapper.findAll('.doctor-name').map(el => el.text());
    expect(doctorNames.filter(name => name === 'dr. Andi Sp.PD')).toHaveLength(2);
  });

  it('formats time range as HH:mm – HH:mm and displays em-dash for empty days', () => {
    const wrapper = mount(DoctorScheduleRecapTable, {
      props: { schedules: sampleSchedules }
    });

    // Empty cells contain em-dash "—"
    const emptyCells = wrapper.findAll('.cell-empty');
    expect(emptyCells.length).toBeGreaterThan(0);
    expect(emptyCells[0].text()).toBe('—');

    // Formatted time ranges
    const timeRanges = wrapper.findAll('.time-range').map(el => el.text());
    expect(timeRanges).toContain('08:00 – 12:00');
    expect(timeRanges).toContain('13:00 – 16:00');
    expect(timeRanges).toContain('09:00 – 13:00');
    expect(timeRanges).toContain('10:00 – 14:00');
  });

  it('displays multiple sessions on separate rows within the day cell sorted by start_time', () => {
    // Pass session 2 first to verify sorting by start_time
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
        is_holiday: false
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
        is_holiday: false
      }
    ];

    const wrapper = mount(DoctorScheduleRecapTable, {
      props: { schedules: reversedSessions }
    });

    const chips = wrapper.findAll('.session-chip');
    expect(chips).toHaveLength(2);
    // 08:00 should come first even though 13:00 was listed first
    expect(chips[0].text()).toContain('08:00 – 12:00');
    expect(chips[1].text()).toContain('13:00 – 16:00');
  });

  it('clearly styles holiday sessions with Libur badge and is-holiday class', () => {
    const wrapper = mount(DoctorScheduleRecapTable, {
      props: { schedules: sampleSchedules }
    });

    const holidayChips = wrapper.findAll('.session-chip.is-holiday');
    expect(holidayChips).toHaveLength(1);

    const holidayPill = wrapper.find('.holiday-pill');
    expect(holidayPill.exists()).toBe(true);
    expect(holidayPill.text()).toBe('Libur');
  });
});
