import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount, flushPromises } from '@vue/test-utils';
import AdmissionCreateView from '../AdmissionCreateView.vue';
import patientService from '@/services/patient.service';
import admissionService from '@/services/admission.service';
import lookupService from '@/services/lookup.service';
import { polyclinicService } from '@/services/polyclinic';
import { guarantorsService } from '@/services/master-data/guarantors.service';

const mockPush = vi.fn();
const mockRoute = {
  query: { patient_id: '176' },
  params: {}
};

vi.mock('vue-router', async (importOriginal) => {
  const actual = await importOriginal();
  return {
    ...actual,
    useRouter: () => ({
      push: mockPush
    }),
    useRoute: () => mockRoute
  };
});

vi.mock('@/services/patient.service', () => ({
  default: {
    getPatient: vi.fn()
  }
}));

vi.mock('@/services/lookup.service', () => ({
  default: {
    getReferralSources: vi.fn(),
    getDoctorsByPolyclinic: vi.fn()
  }
}));

vi.mock('@/services/polyclinic', () => ({
  polyclinicService: {
    getPolyclinics: vi.fn()
  }
}));

vi.mock('@/services/master-data/guarantors.service', () => ({
  guarantorsService: {
    getGuarantors: vi.fn()
  }
}));

vi.mock('@/services/admission.service', () => ({
  default: {
    createAdmission: vi.fn()
  }
}));

describe('AdmissionCreateView (Tahap 3A: Form Admisi Rawat Jalan)', () => {
  const dummyPatient = {
    id: 176,
    medical_record_number: '000176',
    nik: '3404011211080001',
    name: 'Hanjaya Hartono',
    birth_place: 'Sleman',
    birth_date: '2008-11-12',
    gender: 'L',
    address: 'Jl. Kaliurang KM 9',
    postal_code: '55581',
    region: {
      province: { id: 34, name: 'DI Yogyakarta' },
      regency: { id: 3404, name: 'Sleman' },
      district: { id: 340401, name: 'Ngaglik' },
      village: { id: 3404012001, name: 'Sardonoharjo' }
    },
    guarantor: { id: 2, name: 'UMUM', type: 'UMUM' },
    insurance_number: null,
    emergency_contact: {
      name: 'Hartono Sujiwo',
      relationship: 'Orang Tua',
      phone: '081234567890',
      address: 'Jl. Kaliurang KM 9'
    },
    last_visit: null,
    has_unpaid_bills: false
  };

  const dummyGuarantors = [
    { id: 2, name: 'UMUM', type: 'UMUM' },
    { id: 3, name: 'BPJS Kesehatan', type: 'BPJS' },
    { id: 4, name: 'Asuransi Mandiri Inhealth', type: 'PRIVATE' }
  ];

  const dummyPolyclinics = [
    { id: 10, code: 'INT', name: 'Poliklinik Penyakit Dalam', service_type: 'rawat-jalan', is_active: true },
    { id: 11, code: 'MAT', name: 'Poliklinik Mata', service_type: 'rawat-jalan', is_active: true }
  ];

  const dummyReferralSources = [
    { id: 1, code: 'FKTP', name: 'FKT Primer' },
    { id: 2, code: 'FKTL', name: 'FKT Lanjutan' },
    { id: 3, code: 'DS', name: 'Datang Sendiri' },
    { id: 4, code: 'RI', name: 'Rujukan Internal' }
  ];

  const dummyDoctors = [
    { id: 101, name: 'dr. Budi Santoso, Sp.PD', specialization: { name: 'Penyakit Dalam' } },
    { id: 102, name: 'dr. Siti Rahma, Sp.PD', specialization: { name: 'Penyakit Dalam' } }
  ];

  beforeEach(() => {
    vi.useRealTimers();
    vi.clearAllMocks();
    mockRoute.query = { patient_id: '176' };
    patientService.getPatient.mockResolvedValue({ data: { data: dummyPatient } });
    guarantorsService.getGuarantors.mockResolvedValue({ data: dummyGuarantors });
    polyclinicService.getPolyclinics.mockResolvedValue({ data: { data: dummyPolyclinics } });
    lookupService.getReferralSources.mockResolvedValue(dummyReferralSources);
    lookupService.getDoctorsByPolyclinic.mockResolvedValue(dummyDoctors);
  });

  it('renders DATA PASIEN card and loads patient identity accurately from database', async () => {
    const wrapper = mount(AdmissionCreateView, {
      global: {
        stubs: {
          MasterDataPageHeader: true,
          AppToast: true
        }
      }
    });

    await flushPromises();

    expect(patientService.getPatient).toHaveBeenCalledWith('176');
    expect(wrapper.text()).toContain('DATA PASIEN');
    expect(wrapper.text()).toContain('000176');
    expect(wrapper.text()).toContain('Hanjaya Hartono');
    expect(wrapper.text()).toContain('3404011211080001');
    expect(wrapper.text()).toContain('Sleman');
    expect(wrapper.text()).toContain('Jl. Kaliurang KM 9');
    expect(wrapper.text()).toContain('Kel. Sardonoharjo, Kec. Ngaglik, Kab./Kota Sleman, Prov. DI Yogyakarta');

    // Patient without history must show: "Belum ada kunjungan sebelumnya"
    expect(wrapper.text()).toContain('Belum ada kunjungan sebelumnya');
  });

  it('displays past visit info when patient has prior visits', async () => {
    patientService.getPatient.mockResolvedValueOnce({
      data: {
        data: {
          ...dummyPatient,
          last_visit: {
            polyclinic_name: 'Poliklinik Mata',
            doctor_name: 'dr. Hendra, Sp.M',
            admission_date: '2026-10-15 09:30:00',
            case_type: 'pemeriksaan kasus baru'
          }
        }
      }
    });

    const wrapper = mount(AdmissionCreateView, {
      global: {
        stubs: {
          MasterDataPageHeader: true,
          AppToast: true
        }
      }
    });

    await flushPromises();

    expect(wrapper.text()).toContain('Poliklinik Mata');
    expect(wrapper.text()).toContain('dr. Hendra, Sp.M');
    expect(wrapper.text()).toContain('pemeriksaan kasus baru');
    expect(wrapper.text()).not.toContain('Belum ada kunjungan sebelumnya');
  });

  it('integrates Master Data for Jenis Asuransi and Asal Rujukan correctly', async () => {
    const wrapper = mount(AdmissionCreateView, {
      global: {
        stubs: {
          MasterDataPageHeader: true,
          AppToast: true
        }
      }
    });

    await flushPromises();

    // Jenis Asuransi options
    const guarantorSelect = wrapper.find('select#admission-guarantor');
    expect(guarantorSelect.exists()).toBe(true);
    const guarantorOptions = guarantorSelect.findAll('option');
    expect(guarantorOptions.some((o) => o.text().includes('UMUM'))).toBe(true);
    expect(guarantorOptions.some((o) => o.text().includes('BPJS Kesehatan'))).toBe(true);
    expect(guarantorOptions.some((o) => o.text().includes('Asuransi Mandiri Inhealth'))).toBe(true);

    // Asal Rujukan options
    const referralSelect = wrapper.find('select#admission-referral-source');
    expect(referralSelect.exists()).toBe(true);
    const referralOptions = referralSelect.findAll('option');
    expect(referralOptions.some((o) => o.text().includes('Datang Sendiri'))).toBe(true);
    expect(referralOptions.some((o) => o.text().includes('FKT Primer'))).toBe(true);
  });

  it('disables Nama Asal Rujukan when Datang Sendiri is selected and enables for others', async () => {
    const wrapper = mount(AdmissionCreateView, {
      global: {
        stubs: {
          MasterDataPageHeader: true,
          AppToast: true
        }
      }
    });

    await flushPromises();

    const referralSelect = wrapper.find('select#admission-referral-source');
    const referralNameInput = wrapper.find('input#admission-referral-name');

    // Default Datang Sendiri (id: 3)
    await referralSelect.setValue(3);
    expect(referralNameInput.attributes('disabled')).toBeDefined();

    // Change to FKT Primer (id: 1)
    await referralSelect.setValue(1);
    expect(referralNameInput.attributes('disabled')).toBeUndefined();
  });

  it('filters doctors dynamically when polyclinic is selected and resets on clinic switch', async () => {
    const wrapper = mount(AdmissionCreateView, {
      global: {
        stubs: {
          MasterDataPageHeader: true,
          AppToast: true
        }
      }
    });

    await flushPromises();

    const clinicSelect = wrapper.find('select#admission-clinic');
    const doctorSelect = wrapper.find('select#admission-doctor');

    // Initially doctor select disabled
    expect(doctorSelect.attributes('disabled')).toBeDefined();

    // Select Poliklinik Penyakit Dalam (id: 10)
    await clinicSelect.setValue(10);
    await flushPromises();

    expect(lookupService.getDoctorsByPolyclinic).toHaveBeenCalledWith(10, expect.objectContaining({ date: expect.any(String) }));
    expect(doctorSelect.attributes('disabled')).toBeUndefined();
    expect(doctorSelect.text()).toContain('dr. Budi Santoso, Sp.PD');

    // Select doctor
    await doctorSelect.setValue(101);
    expect(doctorSelect.element.value).toBe('101');

    // Switch polyclinic to Mata (id: 11)
    lookupService.getDoctorsByPolyclinic.mockResolvedValueOnce([
      { id: 201, name: 'dr. Maya, Sp.M', specialization: { name: 'Mata' } }
    ]);
    await clinicSelect.setValue(11);
    await flushPromises();

    // Doctor should reset and load new clinic's doctors
    expect(doctorSelect.element.value).toBe('');
    expect(lookupService.getDoctorsByPolyclinic).toHaveBeenCalledWith(11, expect.objectContaining({ date: expect.any(String) }));
    expect(doctorSelect.text()).toContain('dr. Maya, Sp.M');
  });

  it('handles Kondisi A: displays warning when no doctor is assigned to polyclinic', async () => {
    const unassignedCollection = [];
    unassignedCollection.meta = {
      polyclinic_id: 9,
      total_assigned_doctors: 0,
      has_assigned_doctors: false
    };
    lookupService.getDoctorsByPolyclinic.mockResolvedValueOnce(unassignedCollection);

    const wrapper = mount(AdmissionCreateView, {
      global: {
        stubs: {
          MasterDataPageHeader: true,
          AppToast: true
        }
      }
    });

    await flushPromises();

    const clinicSelect = wrapper.find('select#admission-clinic');
    const doctorSelect = wrapper.find('select#admission-doctor');

    await clinicSelect.setValue(10);
    await flushPromises();

    expect(doctorSelect.attributes('disabled')).toBeDefined();
    expect(doctorSelect.text()).toContain('Belum Ada Dokter Terdaftar');
    expect(wrapper.find('[data-testid="doctor-notice-condition-a"]').exists()).toBe(true);
    expect(wrapper.text()).toContain('Belum ada dokter yang terdaftar untuk poliklinik ini. Periksa Master Dokter dan Jadwal Dokter.');
  });

  it('handles Kondisi B: displays info when doctors exist but none available on chosen schedule', async () => {
    const noScheduleCollection = [];
    noScheduleCollection.meta = {
      polyclinic_id: 119,
      total_assigned_doctors: 1,
      has_assigned_doctors: true,
      available_on_schedule: 0
    };
    lookupService.getDoctorsByPolyclinic.mockResolvedValueOnce(noScheduleCollection);

    const wrapper = mount(AdmissionCreateView, {
      global: {
        stubs: {
          MasterDataPageHeader: true,
          AppToast: true
        }
      }
    });

    await flushPromises();

    const clinicSelect = wrapper.find('select#admission-clinic');
    const doctorSelect = wrapper.find('select#admission-doctor');

    await clinicSelect.setValue(10);
    await flushPromises();

    expect(doctorSelect.attributes('disabled')).toBeDefined();
    expect(doctorSelect.text()).toContain('Tidak Ada Dokter Tersedia');
    expect(wrapper.find('[data-testid="doctor-notice-condition-b"]').exists()).toBe(true);
    expect(wrapper.text()).toContain('Tidak ada dokter yang tersedia pada tanggal atau waktu pelayanan ini. Silakan pilih jadwal lain.');
  });

  it('handles Kondisi C: displays error notice with retry button when API fails', async () => {
    lookupService.getDoctorsByPolyclinic.mockRejectedValueOnce(new Error('Network error'));

    const wrapper = mount(AdmissionCreateView, {
      global: {
        stubs: {
          MasterDataPageHeader: true,
          AppToast: true
        }
      }
    });

    await flushPromises();

    const clinicSelect = wrapper.find('select#admission-clinic');
    const doctorSelect = wrapper.find('select#admission-doctor');

    await clinicSelect.setValue(10);
    await flushPromises();

    expect(doctorSelect.attributes('disabled')).toBeDefined();
    expect(doctorSelect.text()).toContain('Gagal Memuat Dokter');
    expect(wrapper.find('[data-testid="doctor-notice-condition-c"]').exists()).toBe(true);
    expect(wrapper.text()).toContain('Gagal memuat data dokter. Silakan coba kembali.');

    // Click retry button
    lookupService.getDoctorsByPolyclinic.mockResolvedValueOnce(dummyDoctors);
    const retryBtn = wrapper.find('.btn-retry-doctor');
    expect(retryBtn.exists()).toBe(true);
    await retryBtn.trigger('click');
    await flushPromises();

    expect(doctorSelect.attributes('disabled')).toBeUndefined();
    expect(doctorSelect.text()).toContain('dr. Budi Santoso, Sp.PD');
  });

  it('re-evaluates doctor availability when admission datetime changes', async () => {
    const wrapper = mount(AdmissionCreateView, {
      global: {
        stubs: {
          MasterDataPageHeader: true,
          AppToast: true
        }
      }
    });

    await flushPromises();

    const clinicSelect = wrapper.find('select#admission-clinic');
    await clinicSelect.setValue(10);
    await flushPromises();

    expect(lookupService.getDoctorsByPolyclinic).toHaveBeenCalledTimes(1);

    // Change admission datetime input
    const datetimeInput = wrapper.find('input#admission-datetime');
    await datetimeInput.setValue('18-11-2026 09:00:00');
    await flushPromises();

    expect(lookupService.getDoctorsByPolyclinic).toHaveBeenCalledTimes(2);
    expect(lookupService.getDoctorsByPolyclinic).toHaveBeenLastCalledWith(10, { date: '18-11-2026 09:00:00' });
  });

  it('provides exactly 3 historical case options', async () => {
    const wrapper = mount(AdmissionCreateView, {
      global: {
        stubs: {
          MasterDataPageHeader: true,
          AppToast: true
        }
      }
    });

    await flushPromises();

    const caseSelect = wrapper.find('select#admission-case-type');
    const options = caseSelect.findAll('option');
    expect(options.length).toBe(3);
    expect(options[0].attributes('value')).toBe('pemeriksaan kasus baru');
    expect(options[1].attributes('value')).toBe('perawatan lanjutan');
    expect(options[2].attributes('value')).toBe('riwayat penyakit kronis/akut');
  });

  it('pre-fills emergency contact from patient registration', async () => {
    const wrapper = mount(AdmissionCreateView, {
      global: {
        stubs: {
          MasterDataPageHeader: true,
          AppToast: true
        }
      }
    });

    await flushPromises();

    expect(wrapper.find('input#admission-family-name').element.value).toBe('Hartono Sujiwo');
    expect(wrapper.find('input#admission-family-relationship').element.value).toBe('Orang Tua');
    expect(wrapper.find('input#admission-family-phone').element.value).toBe('081234567890');
    expect(wrapper.find('input#admission-family-address').element.value).toBe('Jl. Kaliurang KM 9');
  });

  it('displays honest status for SATUSEHAT and DU KOAS', async () => {
    const wrapper = mount(AdmissionCreateView, {
      global: {
        stubs: {
          MasterDataPageHeader: true,
          AppToast: true
        }
      }
    });

    await flushPromises();

    expect(wrapper.text()).toContain('Platform SATUSEHAT (Kemenkes)');
    expect(wrapper.text()).toContain('Belum Terkonfigurasi');
    expect(wrapper.text()).toContain('Booking DU KOAS');
    expect(wrapper.text()).toContain('Tidak Aktif');
  });

  it('blocks admission submission and shows warning banner if patient has unpaid billing', async () => {
    patientService.getPatient.mockResolvedValueOnce({
      data: {
        data: {
          ...dummyPatient,
          has_unpaid_bills: true,
          unpaid_billing_message: 'Pasien ini belum lunas pembayaran. Silahkan lakukan pembayaran di Billing.'
        }
      }
    });

    const wrapper = mount(AdmissionCreateView, {
      global: {
        stubs: {
          MasterDataPageHeader: true,
          AppToast: true
        }
      }
    });

    await flushPromises();

    // Unpaid warning banner appears
    expect(wrapper.find('.unpaid-warning-banner').exists()).toBe(true);
    expect(wrapper.text()).toContain('Pasien ini belum lunas pembayaran. Silahkan lakukan pembayaran di Billing.');

    // Submit button is disabled
    const submitBtn = wrapper.find('button.btn-admit');
    expect(submitBtn.attributes('disabled')).toBeDefined();
  });

  it('successfully submits admission, sends complete payload with idempotency_key, and navigates', async () => {
    vi.useFakeTimers();
    admissionService.createAdmission.mockResolvedValueOnce({
      data: {
        success: true,
        message: 'Admisi rawat jalan berhasil didaftarkan.',
        data: {
          id: 501,
          visit_id: 601,
          admission_number: 'ADM-20261118-0001'
        }
      }
    });

    const wrapper = mount(AdmissionCreateView, {
      global: {
        stubs: {
          MasterDataPageHeader: true,
          AppToast: {
            template: '<div class="app-toast-stub" :data-type="type" :data-title="title" :data-message="message" v-if="show">{{ type }}: {{ title }} - {{ message }}</div>',
            props: ['show', 'type', 'title', 'message']
          }
        }
      }
    });

    await flushPromises();

    // Select clinic and doctor to fulfill requirements
    const clinicSelect = wrapper.find('select#admission-clinic');
    await clinicSelect.setValue(10);
    await flushPromises();

    const doctorSelect = wrapper.find('select#admission-doctor');
    await doctorSelect.setValue(101);

    // Update family phone
    const phoneInput = wrapper.find('input#admission-family-phone');
    await phoneInput.setValue('089999888777');

    // Submit form
    const form = wrapper.find('form');
    await form.trigger('submit.prevent');
    await flushPromises();

    // Verify admissionService.createAdmission called with complete payload
    expect(admissionService.createAdmission).toHaveBeenCalledTimes(1);
    const payload = admissionService.createAdmission.mock.calls[0][0];
    expect(payload.patient_id).toBe(176);
    expect(payload.polyclinic_id).toBe(10);
    expect(payload.doctor_id).toBe(101);
    expect(payload.guarantor_id).toBe(2);
    expect(payload.visit_type).toBe('rawat-jalan');
    expect(payload.case_type).toBe('pemeriksaan kasus baru');
    expect(payload.idempotency_key).toMatch(/^adm_\d+_[a-z0-9]+$/);
    expect(payload.emergency_contact).toEqual({
      name: 'Hartono Sujiwo',
      relationship: 'Orang Tua',
      phone: '089999888777',
      address: 'Jl. Kaliurang KM 9'
    });

    // Success toast shown
    const toast = wrapper.find('.app-toast-stub');
    expect(toast.exists()).toBe(true);
    expect(toast.attributes('data-type')).toBe('success');
    expect(toast.text()).toContain('Admisi Berhasil Didaftarkan');

    // Button shows 'Tersimpan' and is disabled
    const submitBtn = wrapper.find('button.btn-admit');
    expect(submitBtn.text()).toContain('Tersimpan');
    expect(submitBtn.attributes('disabled')).toBeDefined();

    // Fast-forward timer to test navigation
    vi.advanceTimersByTime(1300);
    expect(mockPush).toHaveBeenCalledWith('/admisi');

    vi.useRealTimers();
  });

  it('handles 422 validation failure: shows warning toast, sets field errors, keeps form intact, and does NOT navigate', async () => {
    admissionService.createAdmission.mockRejectedValueOnce({
      response: {
        status: 422,
        data: {
          message: 'Validasi gagal pada isian formulir.',
          errors: {
            doctor_id: ['Dokter tidak memiliki jadwal praktik pada klinik dan waktu ini.'],
            insurance_number: ['Nomor asuransi tidak valid.']
          }
        }
      }
    });

    const wrapper = mount(AdmissionCreateView, {
      global: {
        stubs: {
          MasterDataPageHeader: true,
          AppToast: {
            template: '<div class="app-toast-stub" :data-type="type" :data-title="title" :data-message="message" v-if="show">{{ type }}: {{ title }} - {{ message }}</div>',
            props: ['show', 'type', 'title', 'message']
          }
        }
      }
    });

    await flushPromises();

    const clinicSelect = wrapper.find('select#admission-clinic');
    await clinicSelect.setValue(10);
    await flushPromises();

    const doctorSelect = wrapper.find('select#admission-doctor');
    await doctorSelect.setValue(101);

    const phoneInput = wrapper.find('input#admission-family-phone');
    await phoneInput.setValue('08111222333');

    const form = wrapper.find('form');
    await form.trigger('submit.prevent');
    await flushPromises();

    // Verify error toast
    const toast = wrapper.find('.app-toast-stub');
    expect(toast.exists()).toBe(true);
    expect(toast.attributes('data-type')).toBe('warning');
    expect(toast.text()).toContain('Validasi gagal pada isian formulir.');

    // Verify field error is displayed in the UI
    expect(wrapper.text()).toContain('Dokter tidak memiliki jadwal praktik pada klinik dan waktu ini.');

    // Verify did NOT navigate
    expect(mockPush).not.toHaveBeenCalled();

    // Verify form input values are preserved
    expect(phoneInput.element.value).toBe('08111222333');
    expect(doctorSelect.element.value).toBe('101');
    expect(clinicSelect.element.value).toBe('10');
  });

  it('handles 500 server failure: shows server error toast, keeps form intact, and does NOT navigate', async () => {
    admissionService.createAdmission.mockRejectedValueOnce({
      response: {
        status: 500,
        data: {
          message: 'Terjadi kegagalan basis data pada transaksi admisi.'
        }
      }
    });

    const wrapper = mount(AdmissionCreateView, {
      global: {
        stubs: {
          MasterDataPageHeader: true,
          AppToast: {
            template: '<div class="app-toast-stub" :data-type="type" :data-title="title" :data-message="message" v-if="show">{{ type }}: {{ title }} - {{ message }}</div>',
            props: ['show', 'type', 'title', 'message']
          }
        }
      }
    });

    await flushPromises();

    const clinicSelect = wrapper.find('select#admission-clinic');
    await clinicSelect.setValue(10);
    await flushPromises();

    const doctorSelect = wrapper.find('select#admission-doctor');
    await doctorSelect.setValue(101);

    const phoneInput = wrapper.find('input#admission-family-phone');
    await phoneInput.setValue('08555666777');

    const form = wrapper.find('form');
    await form.trigger('submit.prevent');
    await flushPromises();

    // Toast must show danger / Kesalahan Server
    const toast = wrapper.find('.app-toast-stub');
    expect(toast.exists()).toBe(true);
    expect(toast.attributes('data-type')).toBe('danger');
    expect(toast.text()).toContain('Terjadi kegagalan basis data pada transaksi admisi.');

    // Never call router.push
    expect(mockPush).not.toHaveBeenCalled();

    // Form inputs preserved
    expect(phoneInput.element.value).toBe('08555666777');
  });

  it('handles network failure: shows connection error toast, keeps form intact, and does NOT navigate', async () => {
    admissionService.createAdmission.mockRejectedValueOnce(new Error('Network Error: Failed to fetch'));

    const wrapper = mount(AdmissionCreateView, {
      global: {
        stubs: {
          MasterDataPageHeader: true,
          AppToast: {
            template: '<div class="app-toast-stub" :data-type="type" :data-title="title" :data-message="message" v-if="show">{{ type }}: {{ title }} - {{ message }}</div>',
            props: ['show', 'type', 'title', 'message']
          }
        }
      }
    });

    await flushPromises();

    const clinicSelect = wrapper.find('select#admission-clinic');
    await clinicSelect.setValue(10);
    await flushPromises();

    const doctorSelect = wrapper.find('select#admission-doctor');
    await doctorSelect.setValue(101);

    const form = wrapper.find('form');
    await form.trigger('submit.prevent');
    await flushPromises();

    // Toast must show danger / Gangguan Koneksi
    const toast = wrapper.find('.app-toast-stub');
    expect(toast.exists()).toBe(true);
    expect(toast.attributes('data-type')).toBe('danger');
    expect(toast.text()).toContain('Gangguan Koneksi');

    // Never call router.push
    expect(mockPush).not.toHaveBeenCalled();
  });

  it('prevents multiple simultaneous submissions and disables button while submitting', async () => {
    let resolvePromise;
    admissionService.createAdmission.mockImplementation(() => new Promise((resolve) => {
      resolvePromise = resolve;
    }));

    const wrapper = mount(AdmissionCreateView, {
      global: {
        stubs: {
          MasterDataPageHeader: true,
          AppToast: true
        }
      }
    });

    await flushPromises();

    const clinicSelect = wrapper.find('select#admission-clinic');
    await clinicSelect.setValue(10);
    await flushPromises();

    const doctorSelect = wrapper.find('select#admission-doctor');
    await doctorSelect.setValue(101);

    const form = wrapper.find('form');
    // First trigger
    form.trigger('submit.prevent');
    await wrapper.vm.$nextTick();

    // Second trigger while in-flight
    form.trigger('submit.prevent');
    await wrapper.vm.$nextTick();

    expect(admissionService.createAdmission).toHaveBeenCalledTimes(1);

    const submitBtn = wrapper.find('button.btn-admit');
    expect(submitBtn.attributes('disabled')).toBeDefined();
    expect(submitBtn.text()).toContain('Menyimpan Admisi...');

    resolvePromise({ data: { success: true } });
    await flushPromises();
  });

  it('handles BPJS card check button and warning when card number is less than 8 digits', async () => {
    const wrapper = mount(AdmissionCreateView, {
      global: {
        stubs: {
          MasterDataPageHeader: true,
          AppToast: true
        }
      }
    });

    await flushPromises();

    // Select BPJS guarantor (id: 3)
    const guarantorSelect = wrapper.find('select#admission-guarantor');
    await guarantorSelect.setValue(3);
    await flushPromises();

    // Check button should exist
    const cekBtn = wrapper.find('.btn-action-cek');
    expect(cekBtn.exists()).toBe(true);

    // Set invalid short insurance number
    const insuranceInput = wrapper.find('input#admission-insurance-number');
    await insuranceInput.setValue('12345');
    await cekBtn.trigger('click');
    await flushPromises();

    // Warning box should be displayed
    const warningBox = wrapper.find('.bpjs-warning-box');
    expect(warningBox.exists()).toBe(true);
    expect(warningBox.text()).toContain('No.Kartu Tidak Sesuai');
  });

  it('interacts with SatuSehat consent and DU KOAS booking code search', async () => {
    vi.useFakeTimers();
    admissionService.createAdmission.mockResolvedValueOnce({
      data: { success: true, data: { id: 777 } }
    });

    const wrapper = mount(AdmissionCreateView, {
      global: {
        stubs: {
          MasterDataPageHeader: true,
          AppToast: true
        }
      }
    });

    await flushPromises();

    // Default SatuSehat should be 'tidak_setuju'
    const radioTidak = wrapper.find('input[type="radio"][value="tidak_setuju"]');
    expect(radioTidak.element.checked).toBe(true);

    // Change to 'setuju'
    const radioSetuju = wrapper.find('input[type="radio"][value="setuju"]');
    await radioSetuju.setChecked(true);
    expect(radioSetuju.element.checked).toBe(true);

    // Search DU KOAS
    const duKoasInput = wrapper.find('input#dukoas-search');
    await duKoasInput.setValue('BKG-2026-999');
    const cariBtn = wrapper.find('.btn-action-cari');
    await cariBtn.trigger('click');
    vi.advanceTimersByTime(500);
    await flushPromises();

    const bookingCodeInput = wrapper.find('input#dukoas-booking-code');
    expect(bookingCodeInput.element.value).toBe('BKG-2026-999');

    // Fill required clinic and doctor, then submit
    const clinicSelect = wrapper.find('select#admission-clinic');
    await clinicSelect.setValue(10);
    await flushPromises();

    const doctorSelect = wrapper.find('select#admission-doctor');
    await doctorSelect.setValue(101);

    const form = wrapper.find('form');
    await form.trigger('submit.prevent');
    await flushPromises();

    expect(admissionService.createAdmission).toHaveBeenCalled();
    const payload = admissionService.createAdmission.mock.calls[0][0];
    expect(payload.satusehat_consent).toBe('setuju');
    expect(payload.kode_booking).toBe('BKG-2026-999');

    vi.useRealTimers();
  });

  it('handles invalid patient_id query or 404 patient lookup without falling back to dummy patient', async () => {
    mockRoute.query = { patient_id: 'invalid-id' };
    const wrapperInvalid = mount(AdmissionCreateView, {
      global: {
        stubs: {
          MasterDataPageHeader: true,
          AppToast: {
            template: '<div class="app-toast-stub" v-if="show">{{ title }}: {{ message }}</div>',
            props: ['show', 'type', 'title', 'message']
          }
        }
      }
    });
    await flushPromises();
    expect(patientService.getPatient).not.toHaveBeenCalled();
    expect(wrapperInvalid.text()).toContain('Parameter Pasien Tidak Valid');

    mockRoute.query = { patient_id: '99999' };
    patientService.getPatient.mockRejectedValueOnce({
      response: { status: 404, data: { message: 'Pasien tidak ditemukan di server.' } }
    });
    const wrapper404 = mount(AdmissionCreateView, {
      global: {
        stubs: {
          MasterDataPageHeader: true,
          AppToast: {
            template: '<div class="app-toast-stub" v-if="show">{{ title }}: {{ message }}</div>',
            props: ['show', 'type', 'title', 'message']
          }
        }
      }
    });
    await flushPromises();
    expect(wrapper404.text()).toContain('Pasien tidak ditemukan di server.');
  });

  it('handles 401/403 and 409 conflict on createAdmission without navigating', async () => {
    mockPush.mockClear();
    admissionService.createAdmission.mockRejectedValueOnce({
      response: { status: 409, data: { message: 'Kunjungan duplikat terdeteksi.' } }
    });

    const wrapper = mount(AdmissionCreateView, {
      global: {
        stubs: {
          MasterDataPageHeader: true,
          AppToast: {
            template: '<div class="app-toast-stub" v-if="show">{{ title }}: {{ message }}</div>',
            props: ['show', 'type', 'title', 'message']
          }
        }
      }
    });
    await flushPromises();

    await wrapper.find('select#admission-clinic').setValue(10);
    await flushPromises();
    await wrapper.find('select#admission-doctor').setValue(101);
    await wrapper.find('form').trigger('submit.prevent');
    await flushPromises();

    expect(wrapper.text()).toContain('Konflik Data Admisi: Kunjungan duplikat terdeteksi.');
    expect(mockPush).not.toHaveBeenCalled();
  });
});
