import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount, flushPromises } from '@vue/test-utils';
import PatientDetailModal from '../components/PatientDetailModal.vue';
import { patientService } from '@/services/patient.service';

vi.mock('@/services/patient.service', () => ({
  patientService: {
    getPatientAdmissions: vi.fn(),
    getPatientTimeline: vi.fn(),
  }
}));

describe('PatientDetailModal.vue', () => {
  const mockPatient = {
    id: 777,
    medical_record_number: '77-16-16',
    nik: '3305081212000001',
    name: 'Bambang Pamungkas',
    gender: 'L',
    birth_place: 'Kebumen',
    birth_date: '2000-12-12',
    nationality: 'WNI',
    blood_type: 'O',
    religion: 'Islam',
    marital_status: 'Belum Kawin',
    phone: '081234567890',
    address: 'Desa Tlogorejo RT 01 RW 02',
    postal_code: '54395',
    region: {
      province: { id: '33', name: 'JAWA TENGAH' },
      regency: { id: '3305', name: 'KABUPATEN KEBUMEN' },
      district: { id: '3305081', name: 'BONOROWO' },
      village: { id: '32743', name: 'TLOGOREJO' },
    },
    education: { id: 7, name: 'S1' },
    occupation: { id: 11, name: 'Karyawan Swasta' },
    guarantor: { id: 2, name: 'UMUM', type: 'UMUM' },
    insurance_number: null,
    allergies: {
      status: 'ada',
      notes: 'Alergi Dingin & Debu',
    },
    fast_track: true,
    emergency_contact: {
      name: 'Person Wali',
      relationship: 'Orang Tua',
      phone: '08111111111',
      address: 'Kebumen',
    },
    registered_at: '2024-11-14 08:33:00',
    is_active: true,
  };

  const mockAdmissions = [
    {
      id: 464,
      patient_id: 777,
      admission_time: '2024-11-14 08:33:00',
      reason: 'Pemeriksaan rutin keluhan pusing',
      entry_mode: 'poliklinik',
      status: '1',
      status_label: 'Aktif',
      guarantor: { id: 2, name: 'UMUM' },
      visits: [
        {
          id: 695,
          polyclinic: { id: 14, name: 'Poliklinik Penyakit Dalam' },
          doctor: { id: 62, name: 'dr. Dokter Spesialis' },
          case_type: 'pemeriksaan kasus baru',
          service_status: 'Ya',
          service_status_label: 'Selesai Dilayani',
          billing_status: 'sudah bayar',
          examination_time: '2024-11-14 08:45:00',
        }
      ]
    }
  ];

  const mockTimelineEvents = [
    {
      id: 'billing-301',
      type: 'billing',
      category: 'Billing',
      title: 'Tagihan Invoice INV-2024-11-001',
      timestamp: '2024-11-14 10:00:00',
      unit: 'Kasir / Billing',
      practitioner: null,
      status: 'Lunas',
      status_variant: 'success',
      notes: 'No. Inv: INV-2024-11-001 • Total: Rp 175.000',
      admission_id: 464,
      visit_id: 695,
    },
    {
      id: 'lab-201',
      type: 'laboratorium',
      category: 'Laboratorium',
      title: 'Pemeriksaan Lab: Hematologi Lengkap',
      timestamp: '2024-11-14 09:30:00',
      unit: 'Laboratorium',
      practitioner: 'dr. Dokter Spesialis',
      status: 'Hasil Selesai',
      status_variant: 'success',
      notes: 'No. Reg: LAB-2024-001 • Resume: Hb normal',
      admission_id: 464,
      visit_id: 695,
    },
    {
      id: 'resep-101',
      type: 'farmasi',
      category: 'Farmasi',
      title: 'Resep Farmasi #101',
      timestamp: '2024-11-14 09:15:00',
      unit: 'Farmasi (Poliklinik Penyakit Dalam)',
      practitioner: 'dr. Dokter Spesialis',
      status: 'Resep Selesai (Obat Diserahkan)',
      status_variant: 'success',
      notes: 'Paracetamol 500mg tab',
      admission_id: 464,
      visit_id: 695,
    },
    {
      id: 'visit-695',
      type: 'pemeriksaan',
      category: 'Pemeriksaan',
      title: 'Pelayanan Poliklinik Penyakit Dalam',
      timestamp: '2024-11-14 08:45:00',
      unit: 'Poliklinik Penyakit Dalam',
      practitioner: 'dr. Dokter Spesialis',
      status: 'Selesai Dilayani',
      status_variant: 'success',
      notes: 'Kasus: pemeriksaan kasus baru',
      admission_id: 464,
      visit_id: 695,
    },
    {
      id: 'admisi-464',
      type: 'admisi',
      category: 'Admisi',
      title: 'Admisi Pasien',
      timestamp: '2024-11-14 08:33:00',
      unit: 'Pendaftaran / Admisi',
      practitioner: null,
      status: 'Aktif',
      status_variant: 'primary',
      notes: 'Cara Masuk: Poliklinik • Alasan: Pemeriksaan rutin keluhan pusing',
      admission_id: 464,
      visit_id: null,
    }
  ];

  beforeEach(() => {
    vi.clearAllMocks();
    patientService.getPatientAdmissions.mockResolvedValue({
      data: { data: mockAdmissions }
    });
    patientService.getPatientTimeline.mockResolvedValue({
      data: { data: mockTimelineEvents }
    });
  });

  it('renders Tab 1 (Info) by default with complete patient data', () => {
    const wrapper = mount(PatientDetailModal, {
      props: {
        isOpen: true,
        patient: mockPatient,
      }
    });

    // Check Header
    expect(wrapper.find('.rm-tag').text()).toBe('77-16-16');
    expect(wrapper.find('.status-pill').text()).toBe('Pasien Aktif');
    expect(wrapper.find('#patient-detail-title').text()).toBe('Bambang Pamungkas');
    expect(wrapper.find('.badge-fast-track').exists()).toBe(true);

    // Active tab should be Info
    expect(wrapper.find('#tab-info').classes()).toContain('active');
    expect(wrapper.find('#tabpanel-info').exists()).toBe(true);

    // Identitas
    expect(wrapper.text()).toContain('Kebumen');
    expect(wrapper.text()).toContain('WNI');
    expect(wrapper.text()).toContain('Ada Alergi: Alergi Dingin & Debu');

    // Wilayah
    expect(wrapper.text()).toContain('TLOGOREJO');
    expect(wrapper.text()).toContain('BONOROWO');
    expect(wrapper.text()).toContain('KABUPATEN KEBUMEN');
    expect(wrapper.text()).toContain('JAWA TENGAH');
    expect(wrapper.text()).toContain('Person Wali');

    // Penjamin
    expect(wrapper.text()).toContain('UMUM');
  });

  it('switches to Tab 2 (Riwayat), fetches admissions, and renders admission card with visits', async () => {
    const wrapper = mount(PatientDetailModal, {
      props: {
        isOpen: true,
        patient: mockPatient,
      }
    });

    // Click Tab 2: Riwayat
    await wrapper.find('#tab-riwayat').trigger('click');
    await flushPromises();

    expect(patientService.getPatientAdmissions).toHaveBeenCalledWith(777, { per_page: 50 });
    expect(wrapper.find('#tabpanel-riwayat').exists()).toBe(true);

    // Verify Admission Card
    const admCard = wrapper.find('.admission-card');
    expect(admCard.exists()).toBe(true);
    expect(admCard.text()).toContain('Admisi #464');
    expect(admCard.text()).toContain('Pemeriksaan rutin keluhan pusing');
    expect(admCard.text()).toContain('Poliklinik Penyakit Dalam');
    expect(admCard.text()).toContain('dr. Dokter Spesialis');
    expect(admCard.text()).toContain('Selesai Dilayani');
    expect(admCard.text()).toContain('Billing: sudah bayar');
  });

  it('renders clean empty state in Tab 2 if patient has no admissions', async () => {
    patientService.getPatientAdmissions.mockResolvedValueOnce({
      data: { data: [] }
    });

    const wrapper = mount(PatientDetailModal, {
      props: {
        isOpen: true,
        patient: mockPatient,
      }
    });

    await wrapper.find('#tab-riwayat').trigger('click');
    await flushPromises();

    expect(wrapper.find('.empty-state').exists()).toBe(true);
    expect(wrapper.text()).toContain('Belum Ada Riwayat Kunjungan');
  });

  it('switches to Tab 3 (Timeline), fetches timeline events, and renders vertical timeline', async () => {
    const wrapper = mount(PatientDetailModal, {
      props: {
        isOpen: true,
        patient: mockPatient,
      }
    });

    // Click Tab 3: Timeline
    await wrapper.find('#tab-timeline').trigger('click');
    await flushPromises();

    expect(patientService.getPatientTimeline).toHaveBeenCalledWith(777);
    expect(wrapper.find('#tabpanel-timeline').exists()).toBe(true);

    // Check timeline events
    const rows = wrapper.findAll('.timeline-event-row');
    expect(rows.length).toBe(5);

    // Verify first event is Billing (newest)
    expect(rows[0].text()).toContain('Tagihan Invoice INV-2024-11-001');
    expect(rows[0].text()).toContain('Lunas');

    // Verify second event is Lab
    expect(rows[1].text()).toContain('Pemeriksaan Lab: Hematologi Lengkap');
    expect(rows[1].text()).toContain('Hasil Selesai');

    // Verify third event is Farmasi
    expect(rows[2].text()).toContain('Resep Farmasi #101');
    expect(rows[2].text()).toContain('Paracetamol 500mg tab');
  });

  it('filters timeline events when category pills are clicked', async () => {
    const wrapper = mount(PatientDetailModal, {
      props: {
        isOpen: true,
        patient: mockPatient,
      }
    });

    await wrapper.find('#tab-timeline').trigger('click');
    await flushPromises();

    // Click 'Laboratorium' category pill
    const labPill = wrapper.find('.pill-lab');
    expect(labPill.exists()).toBe(true);
    await labPill.trigger('click');

    const filteredRows = wrapper.findAll('.timeline-event-row');
    expect(filteredRows.length).toBe(1);
    expect(filteredRows[0].text()).toContain('Hematologi Lengkap');

    // Click 'Semua' pill
    await wrapper.find('.timeline-filter-bar button:first-child').trigger('click');
    expect(wrapper.findAll('.timeline-event-row').length).toBe(5);
  });

  it('resets to Info tab and clears previous patient caches when patient prop changes', async () => {
    const wrapper = mount(PatientDetailModal, {
      props: {
        isOpen: true,
        patient: mockPatient,
      }
    });

    // Switch to Timeline
    await wrapper.find('#tab-timeline').trigger('click');
    await flushPromises();
    expect(wrapper.find('#tabpanel-timeline').exists()).toBe(true);

    // Change patient
    const newPatient = {
      ...mockPatient,
      id: 888,
      name: 'Pasien Baru Kedua',
      medical_record_number: '88-18-18',
    };
    await wrapper.setProps({ patient: newPatient });
    await flushPromises();

    // Should reset to Info tab
    expect(wrapper.find('#tab-info').classes()).toContain('active');
    expect(wrapper.find('#tabpanel-info').exists()).toBe(true);
    expect(wrapper.find('#patient-detail-title').text()).toBe('Pasien Baru Kedua');
  });
});
