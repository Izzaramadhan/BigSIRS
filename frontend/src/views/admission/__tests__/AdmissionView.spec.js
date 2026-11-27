import { describe, it, expect, vi, beforeEach } from 'vitest';
import { flushPromises, mount } from '@vue/test-utils';
import AdmissionView from '../AdmissionView.vue';
import AdmissionEditView from '../AdmissionEditView.vue';
import AdmissionOldPatientSearchCard from '../components/AdmissionOldPatientSearchCard.vue';
import AdmissionPatientListCard from '../components/AdmissionPatientListCard.vue';
import AdmissionTable from '../components/AdmissionTable.vue';
import AdmissionStatsCards from '../components/AdmissionStatsCards.vue';
import AdmissionQueueCard from '../components/AdmissionQueueCard.vue';
import { mockAdmissions, mockLoketInfo } from '@/mocks/admissions';
import { mockPatients } from '@/mocks/patients';
import admissionService from '@/services/admission.service';
import lookupService from '@/services/lookup.service';

const mockPush = vi.fn();
vi.mock('vue-router', async (importOriginal) => {
  const actual = await importOriginal();
  return {
    ...actual,
    useRoute: () => ({
      params: { id: '1' },
      query: {}
    }),
    useRouter: () => ({
      push: mockPush
    })
  };
});

vi.mock('@/services/patient.service', () => ({
  default: {
    searchPatients: vi.fn(async (params = {}) => {
      let filtered = [...mockPatients];
      if (params.q) {
        const q = String(params.q).toLowerCase();
        filtered = filtered.filter((p) =>
          (p.name && p.name.toLowerCase().includes(q)) ||
          (p.nik && p.nik.includes(q)) ||
          (p.medical_record_number && p.medical_record_number.toLowerCase().includes(q))
        );
      }
      return {
        data: {
          data: filtered,
          meta: {
            total: filtered.length,
            current_page: 1,
            last_page: 1,
            from: filtered.length > 0 ? 1 : 0,
            to: filtered.length
          }
        }
      };
    }),
    getPatient: vi.fn(async (id) => ({
      data: {
        data: mockPatients.find((p) => p.id === Number(id)) || mockPatients[0]
      }
    }))
  }
}));

vi.mock('@/services/admission.service', () => ({
  default: {
    getAdmissions: vi.fn(async (params = {}) => {
      let filtered = [...mockAdmissions];
      if (params.guarantor && params.guarantor !== 'all') {
        const g = String(params.guarantor).toUpperCase();
        filtered = filtered.filter((a) => {
          const ag = String(a.guarantor_type || '').toUpperCase();
          if (g === 'UMUM') return ag.includes('UMUM') || ag.includes('MANDIRI');
          return ag === g || ag.includes(g);
        });
      }
      return {
        data: {
          data: filtered.slice(0, params.per_page || 10),
          meta: {
            total: filtered.length,
            current_page: params.page || 1,
            last_page: Math.ceil(filtered.length / (params.per_page || 10)),
            from: 1,
            to: Math.min(filtered.length, params.per_page || 10)
          }
        }
      };
    }),
    getAdmission: vi.fn(async (id) => ({
      data: {
        data: {
          id: Number(id) || 1,
          visit_id: 10,
          admission_date: '2026-11-26',
          admission_time: '08:00 WIB',
          patient_id: 1,
          medical_record_number: '000001',
          patient_name: 'Bambang Setyadi',
          nik: '3471011205860001',
          gender: 'L',
          birth_place: 'Yogyakarta',
          birth_date: '1986-05-12',
          address: 'Jl. Malioboro No. 45',
          guarantor_id: 99,
          guarantor: 'Asuransi Arsip',
          guarantor_type: 'Asuransi Arsip',
          insurance_number: 'POL-ARSIP-01',
          referral_source_id: 3,
          referral_source: 'Datang Sendiri',
          referral_name: '',
          visit_type: 'Rawat Jalan',
          polyclinic_id: 1,
          polyclinic: 'Poli Umum',
          doctor_id: 1,
          doctor: 'dr. Budi Santoso, Sp.PD',
          case_type: 'Pemeriksaan Kasus Baru',
          service_status: 'Tidak',
          can_change_clinic: true,
          emergency_contact: {
            name: 'Ratna Dewi',
            relationship: 'Istri',
            phone: '081234567899',
            address: 'Yogyakarta'
          },
          notes: ''
        }
      }
    })),
    createAdmission: vi.fn(async (payload) => ({
      data: {
        data: {
          id: 999,
          visit_id: 1999,
          ...payload
        }
      }
    })),
    updateAdmission: vi.fn(async (id, payload) => ({
      data: {
        success: true,
        data: {
          id: Number(id) || 1,
          ...payload
        }
      }
    }))
  }
}));

vi.mock('@/services/polyclinic', () => ({
  polyclinicService: {
    getPolyclinics: vi.fn(async () => ({
      data: {
        data: [
          { id: 1, name: 'Poli Umum' },
          { id: 2, name: 'Poli Anak' }
        ]
      }
    }))
  }
}));

vi.mock('@/services/lookup.service', () => ({
  default: {
    getGuarantors: vi.fn(),
    getDoctors: vi.fn(async () => [{ id: 1, name: 'dr. Budi Santoso, Sp.PD' }]),
    getDoctorsByPolyclinic: vi.fn(async () => [{ id: 1, name: 'dr. Budi Santoso, Sp.PD' }]),
    getReferralSources: vi.fn(async () => [
      { id: 1, name: 'FKT Primer' },
      { id: 3, name: 'Datang Sendiri' }
    ])
  }
}));

const mockMasterGuarantors = [
  { id: 2, code: 'UMM', name: 'UMUM', type: 'self_pay', is_active: true },
  { id: 1, code: 'BPJS', name: 'BPJS Kesehatan', type: 'bpjs', is_active: true },
  { id: 3, code: 'PRV', name: 'Asuransi Swasta', type: 'private_insurance', is_active: true },
  { id: 8, code: 'NEW-CORP', name: 'Asuransi Perusahaan Baru', type: 'private_insurance', is_active: true }
];

describe('AdmissionView (Halaman Awal Admisi Terbaru)', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    lookupService.getGuarantors.mockResolvedValue(mockMasterGuarantors);
  });

  it('renders FORM PENCARIAN PASIEN LAMA and DAFTAR PASIEN with 6 standardized columns', async () => {
    const wrapper = mount(AdmissionView, {
      global: {
        stubs: {
          MasterDataPageHeader: false,
          AppToast: true,
          Teleport: true
        }
      }
    });
    await flushPromises();

    expect(wrapper.text()).toContain('Admisi Pasien');
    expect(wrapper.text()).toContain('Home');
    expect(wrapper.text()).toContain('Data Admisi Pasien');
    expect(wrapper.text()).toContain('FORM PENCARIAN PASIEN LAMA');
    expect(wrapper.text()).toContain('DAFTAR PASIEN');

    expect(wrapper.find('input#old-patient-query').exists()).toBe(true);
    expect(wrapper.find('input#old-patient-birthdate').exists()).toBe(true);
    expect(wrapper.find('button.btn-search-old').text()).toContain('Cari');

    expect(wrapper.find('select#admission-guarantor-filter').exists()).toBe(true);
    expect(wrapper.find('select#admission-records-perpage').exists()).toBe(true);

    const headers = wrapper
      .find('.admission-list-card')
      .findAll('thead th')
      .map((th) => th.text().trim());

    expect(headers).toEqual([
      'No.',
      'Waktu Admisi',
      'Nama',
      'Admisi ke',
      'Jenis Asuransi',
      'Aksi'
    ]);

    expect(wrapper.find('.btn-tools').exists()).toBe(true);
    expect(wrapper.find('.btn-tools').text()).toContain('Tools');
  });

  it('searches old patients in FORM PENCARIAN PASIEN LAMA and allows opening AdmissionNewModal', async () => {
    const wrapper = mount(AdmissionView, {
      global: {
        stubs: {
          AppToast: true,
          Teleport: true
        }
      }
    });

    await wrapper.find('input#old-patient-query').setValue('Bambang');
    await wrapper.find('form.old-patient-search-form').trigger('submit.prevent');
    await flushPromises();

    expect(wrapper.find('.search-results-panel').exists()).toBe(true);
    expect(wrapper.find('.search-results-panel').text()).toContain('Bambang Setyadi');

    await wrapper.find('.btn-admit-patient').trigger('click');
    expect(wrapper.find('.modal-overlay').exists()).toBe(true);
    expect(wrapper.find('input#add-patient-name').element.value).toContain('Bambang Setyadi');
  });

  it('blocks AdmissionNewModal and triggers unpaid billing notification when patient has unpaid bills', async () => {
    const patientService = (await import('@/services/patient.service')).default;
    patientService.searchPatients.mockResolvedValueOnce({
      data: {
        data: [
          {
            id: 99,
            name: 'Hanjaya Hartono',
            medical_record_number: '000176',
            nik: '6585877985746978',
            has_unpaid_bills: true,
            unpaid_billing_message: 'Pasien ini belum lunas pembayaran. Silahkan lakukan pembayaran di Billing. Atau silahkan hubungi petugas jika mengalami kesulitan.'
          }
        ],
        meta: { total: 1 }
      }
    });

    const wrapper = mount(AdmissionView, {
      global: {
        stubs: {
          AppToast: true,
          Teleport: true
        }
      }
    });

    await wrapper.find('input#old-patient-query').setValue('Hanjaya');
    await wrapper.find('form.old-patient-search-form').trigger('submit.prevent');
    await flushPromises();

    expect(wrapper.find('.search-results-panel').exists()).toBe(true);
    expect(wrapper.find('.search-results-panel').text()).toContain('Hanjaya Hartono');

    // Click Admit button
    await wrapper.find('.btn-admit-patient').trigger('click');
    await flushPromises();

    // Modal AdmissionNewModal MUST NOT be open!
    expect(wrapper.find('.modal-overlay').exists()).toBe(false);
    expect(wrapper.find('input#add-patient-name').exists()).toBe(false);
  });

  it('filters DAFTAR PASIEN by Jenis Asuransi dropdown loaded from Master Data (UMUM / dynamic guarantors)', async () => {
    const wrapper = mount(AdmissionView, {
      global: {
        stubs: {
          AppToast: true,
          Teleport: true
        }
      }
    });
    await flushPromises();

    await flushPromises();
    const guarantorSelect = wrapper.find('select#admission-guarantor-filter');
    const optionTexts = guarantorSelect.findAll('option').map((opt) => opt.text().trim());
    expect(optionTexts).toContain('Asuransi Perusahaan Baru');

    await guarantorSelect.setValue('UMUM');
    await flushPromises();

    const badges = wrapper.findAll('.insurance-badge').map((el) => el.text().trim());
    expect(badges.length).toBeGreaterThan(0);
    expect(badges.every((b) => b === 'UMUM')).toBe(true);
  });

  it('emits expected events from AdmissionOldPatientSearchCard & AdmissionPatientListCard', async () => {
    const searchWrapper = mount(AdmissionOldPatientSearchCard, {
      props: {
        keyword: 'RM-000245',
        birthDate: '',
        results: [],
        hasSearched: false
      }
    });
    await searchWrapper.find('form').trigger('submit.prevent');
    expect(searchWrapper.emitted('search')).toBeTruthy();

    const listWrapper = mount(AdmissionPatientListCard, {
      props: {
        admissions: mockAdmissions.slice(0, 3),
        totalCount: 3,
        currentPage: 1,
        lastPage: 1,
        perPage: 10,
        from: 1,
        to: 3,
        guarantorFilter: 'all'
      },
      global: {
        stubs: {
          Teleport: true
        }
      }
    });
    await listWrapper.find('th.col-name').trigger('click');
    expect(listWrapper.emitted('sort')[0]).toEqual(['nama']);
  });

  it('renders updated Tools dropdown menu items (Edit, Hapus, Cetak Tracer, Cetak Antrian, Cetak Label)', async () => {
    const listWrapper = mount(AdmissionPatientListCard, {
      props: {
        admissions: mockAdmissions.slice(0, 1),
        totalCount: 1,
        currentPage: 1,
        lastPage: 1,
        perPage: 10,
        from: 1,
        to: 1,
        guarantorFilter: 'all'
      },
      global: {
        stubs: {
          Teleport: true
        }
      }
    });

    await listWrapper.find('.btn-tools').trigger('click');
    const menuLabels = listWrapper
      .findAll('.dropdown-action-btn span')
      .map((el) => el.text().trim());

    expect(menuLabels).toEqual([
      'Edit',
      'Hapus',
      'Cetak Tracer',
      'Cetak Antrian',
      'Cetak Label'
    ]);

    await listWrapper.findAll('.dropdown-action-btn')[1].trigger('click');
    expect(listWrapper.emitted('delete-admission')).toBeTruthy();
  });

  it('navigates to Edit Data Admisi page when clicking Tools -> Edit in AdmissionView', async () => {
    mockPush.mockClear();
    const wrapper = mount(AdmissionView, {
      global: {
        stubs: {
          Teleport: true
        }
      }
    });
    await flushPromises();

    await wrapper.find('.btn-tools').trigger('click');
    const editBtn = wrapper.findAll('.dropdown-action-btn')[0];
    expect(editBtn.text()).toContain('Edit');

    await editBtn.trigger('click');
    expect(mockPush).toHaveBeenCalledWith({
      name: 'registration.admission-edit',
      params: { id: expect.anything() }
    });
  });

  it('AdmissionOldPatientSearchCard blocks select-patient event and displays unpaid toast when patient has unpaid bills', async () => {
    const wrapper = mount(AdmissionOldPatientSearchCard, {
      props: {
        keyword: 'Hanjaya',
        birthDate: '',
        hasSearched: true,
        results: [
          {
            id: 99,
            name: 'Hanjaya Hartono',
            no_rm: '000176',
            no_ktp: '6585877985746978',
            has_unpaid_bills: true,
            unpaid_billing_message: 'Pasien ini belum lunas pembayaran. Silahkan lakukan pembayaran di Billing. Atau silahkan hubungi petugas jika mengalami kesulitan.'
          }
        ]
      },
      global: {
        stubs: {
          Teleport: true
        }
      }
    });

    await wrapper.find('.btn-admit-patient').trigger('click');

    expect(wrapper.emitted('select-patient')).toBeFalsy();
    expect(wrapper.find('.unpaid-billing-toast').exists()).toBe(true);
    expect(wrapper.find('.unpaid-billing-toast').text()).toContain('Pasien ini belum lunas pembayaran');
  });
});

describe('AdmissionEditView (Halaman Edit Data Admisi)', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    lookupService.getGuarantors.mockResolvedValue(mockMasterGuarantors);
  });

  it('renders DATA PASIEN card and FORM EDIT with 3 sub-panels', async () => {
    const wrapper = mount(AdmissionEditView, {
      global: {
        stubs: {
          Teleport: true
        }
      }
    });
    await flushPromises();

    expect(wrapper.text()).toContain('DATA PASIEN');
    expect(wrapper.text()).toContain('No. RM');
    expect(wrapper.text()).toContain('Nama');
    expect(wrapper.text()).toContain('TTL');
    expect(wrapper.text()).toContain('Alamat');
    expect(wrapper.text()).toContain('FORM EDIT');
    expect(wrapper.text()).toContain('Data Admisi Pasien');
    expect(wrapper.text()).toContain('Data Visit Pasien');
    expect(wrapper.text()).toContain('Data Keluarga Terdekat Pasien');
    expect(wrapper.find('.btn-header-back').exists()).toBe(true);
    expect(wrapper.find('.btn-batal').text()).toContain('Batal');
    expect(wrapper.find('.btn-update').text()).toContain('Update');
  });

  it('disables No. Asuransi for self_pay guarantor, enables for BPJS/private, and preserves historical guarantor', async () => {
    // Include scenario where historical guarantor on admission record is NOT returned by active lookup
    lookupService.getGuarantors.mockResolvedValue([
      { id: 2, code: 'UMM', name: 'UMUM', type: 'self_pay', is_active: true },
      { id: 1, code: 'BPJS', name: 'BPJS Kesehatan', type: 'bpjs', is_active: true },
      { id: 99, code: 'OLD', name: 'Asuransi Arsip', type: 'private_insurance', is_active: false }
    ]);

    const wrapper = mount(AdmissionEditView, {
      global: {
        stubs: {
          Teleport: true
        }
      }
    });
    await flushPromises();

    const guarantorSelect = wrapper.find('#edit-guarantor');
    const insuranceInput = wrapper.find('#edit-insurance-number');
    const optionTexts = guarantorSelect.findAll('option').map((opt) => opt.text().trim());
    expect(optionTexts).toContain('Asuransi Arsip (Nonaktif / Historis)');

    await guarantorSelect.setValue('2');
    expect(insuranceInput.element.disabled).toBe(true);

    await guarantorSelect.setValue('1');
    expect(insuranceInput.element.disabled).toBe(false);

    await wrapper.find('#edit-referral-source').setValue('FKT Primer');
    await wrapper.find('#edit-referral-name').setValue('');
    await wrapper.find('form').trigger('submit');

    expect(wrapper.find('.error-message').text()).toContain('Nama Asal Rujukan wajib diisi.');
  });

  it('shows API error and retry button when guarantor lookup fails in AdmissionEditView', async () => {
    lookupService.getGuarantors
      .mockRejectedValueOnce(new Error('Server 500'))
      .mockResolvedValueOnce(mockMasterGuarantors);

    const wrapper = mount(AdmissionEditView, {
      global: {
        stubs: {
          Teleport: true
        }
      }
    });
    await flushPromises();

    expect(wrapper.text()).toContain('Gagal memuat data penjamin');
    await wrapper.find('.btn-retry-guarantor').trigger('click');
    await flushPromises();

    expect(wrapper.text()).not.toContain('Gagal memuat data penjamin');
    expect(wrapper.findAll('#edit-guarantor option').map((o) => o.text().trim())).toContain('Asuransi Perusahaan Baru');
  });

  it('saves edit changes on Update and navigates back to /admisi', async () => {
    mockPush.mockClear();
    vi.useFakeTimers();
    try {
      const wrapper = mount(AdmissionEditView, {
        global: {
          stubs: {
            Teleport: true
          }
        }
      });
      await flushPromises();

      await wrapper.find('#edit-referral-name').setValue('Puskesmas Banguntapan I');
      const doctorOptions = wrapper.findAll('#edit-doctor option').filter((opt) => opt.element.value);
      if (doctorOptions.length > 0) {
        await wrapper.find('#edit-doctor').setValue(doctorOptions[0].element.value);
      }
      await wrapper.find('#edit-family-name').setValue('Sabirin');
      await wrapper.find('form').trigger('submit');
      await flushPromises();

      expect(admissionService.updateAdmission).toHaveBeenCalled();
      expect(wrapper.text()).toContain('Data Admisi Diperbarui');
      vi.advanceTimersByTime(600);
      expect(mockPush).toHaveBeenCalledWith('/admisi');
    } finally {
      vi.useRealTimers();
    }
  });

  it('aborts update without fake success or navigation when updateAdmission fails with server/auth/network error', async () => {
    mockPush.mockClear();
    admissionService.updateAdmission.mockRejectedValueOnce({
      response: { status: 403, data: { message: 'Akses edit admisi ditolak.' } }
    });

    const wrapper = mount(AdmissionEditView, {
      global: {
        stubs: {
          Teleport: true
        }
      }
    });
    await flushPromises();

    await wrapper.find('#edit-referral-name').setValue('Puskesmas Uji');
    await wrapper.find('form').trigger('submit');
    await flushPromises();

    expect(wrapper.text()).toContain('Akses edit admisi ditolak.');
    expect(wrapper.text()).not.toContain('Data Admisi Diperbarui');
    expect(mockPush).not.toHaveBeenCalled();
  });

  it('shows API error alert with retry and never falls back to mockAdmissions when getAdmissions fails in AdmissionView', async () => {
    admissionService.getAdmissions.mockRejectedValueOnce(new Error('Network Error'));

    const wrapper = mount(AdmissionView, {
      global: {
        stubs: {
          AppToast: true,
          Teleport: true
        }
      }
    });
    await flushPromises();

    const listCard = wrapper.findComponent(AdmissionPatientListCard);
    expect(listCard.text()).toContain('Gagal memuat data admisi dari server.');
    expect(listCard.text()).toContain('Tidak dapat terhubung ke server.');
    expect(listCard.findAll('tbody tr td.col-name').length).toBe(0);

    await listCard.find('button.btn-reset-empty').trigger('click');
    await flushPromises();
    expect(listCard.findAll('tbody tr td.col-name').length).toBeGreaterThan(0);
  });

  it('verifies unpaid billing from API before opening AdmissionNewModal and blocks when check fails', async () => {
    const patientService = (await import('@/services/patient.service')).default;
    patientService.searchPatients.mockResolvedValueOnce({
      data: {
        data: [
          {
            id: 505,
            name: 'Pasien Tanpa Flag Tagihan',
            medical_record_number: '000505',
            nik: '3404011111110505'
          }
        ],
        meta: { total: 1 }
      }
    });

    const wrapper = mount(AdmissionView, {
      global: {
        stubs: {
          AppToast: true,
          Teleport: true
        }
      }
    });
    await flushPromises();

    await wrapper.find('input#old-patient-query').setValue('Pasien');
    await wrapper.find('form.old-patient-search-form').trigger('submit.prevent');
    await flushPromises();

    patientService.getPatient.mockRejectedValueOnce(new Error('Network Error'));
    await wrapper.find('.btn-admit-patient').trigger('click');
    await flushPromises();

    expect(wrapper.find('.modal-overlay').exists()).toBe(false);
  });

  it('navigates back to /admisi when Batal is clicked', async () => {
    mockPush.mockClear();
    const wrapper = mount(AdmissionEditView, {
      global: {
        stubs: {
          Teleport: true
        }
      }
    });

    await wrapper.find('.btn-batal').trigger('click');
    expect(mockPush).toHaveBeenCalledWith('/admisi');
  });

  it('renders restriction warning banner and disables polyclinic select when clinic change is restricted', async () => {
    const wrapper = mount(AdmissionEditView, {
      global: {
        stubs: {
          Teleport: true
        }
      }
    });

    // Simulate restricted examination state
    wrapper.vm.canChangeClinic = false;
    wrapper.vm.cannotChangeReason = 'Pasien sedang dalam proses pemeriksaan klinis di poliklinik. Perubahan poliklinik dibatasi.';
    await wrapper.vm.$nextTick();

    const banner = wrapper.find('.clinic-restriction-banner');
    expect(banner.exists()).toBe(true);
    expect(banner.text()).toContain('Pembatasan Perubahan Pelayanan');
    expect(banner.text()).toContain('sedang dalam proses pemeriksaan klinis');

    const clinicSelect = wrapper.find('#edit-clinic');
    expect(clinicSelect.element.disabled).toBe(true);
  });
});

describe('Admission Rawat Jalan Components', () => {
  describe('AdmissionTable', () => {
    it('renders the 11 columns in EXACT required order', () => {
      const wrapper = mount(AdmissionTable, {
        props: {
          admissions: mockAdmissions.slice(0, 5),
          totalCount: 5,
          currentPage: 1,
          perPage: 10,
          loading: false
        }
      });

      const thList = wrapper.findAll('thead th').map(th => th.text().trim());

      // Expected strict columns from Section 8:
      // 1. No.
      // 2. No. Antrean
      // 3. Waktu Admisi
      // 4. No. RM
      // 5. Nama Pasien
      // 6. Poliklinik
      // 7. Dokter
      // 8. Jenis Penjamin
      // 9. General Consent
      // 10. Status Pelayanan
      // 11. Aksi
      expect(thList).toEqual([
        'No.',
        'No. Antrean',
        'Waktu Admisi',
        'No. RM',
        'Nama Pasien',
        'Poliklinik',
        'Dokter',
        'Jenis Penjamin',
        'General Consent',
        'Status Pelayanan',
        'Aksi'
      ]);
    });

    it('displays the found admission count badge', () => {
      const wrapper = mount(AdmissionTable, {
        props: {
          admissions: mockAdmissions.slice(0, 10),
          totalCount: 248,
          currentPage: 1,
          perPage: 10,
          loading: false
        }
      });

      const countBadge = wrapper.find('.count-badge');
      expect(countBadge.text()).toContain('Ditemukan 248 data admisi rawat jalan');
    });

    it('renders Auto-sync notice correctly', () => {
      const wrapper = mount(AdmissionTable, {
        props: {
          admissions: mockAdmissions.slice(0, 5),
          totalCount: 5,
          currentPage: 1,
          perPage: 10,
          loading: false
        }
      });

      expect(wrapper.find('.sync-status-badge').text()).toContain('Auto-sync: Tidak Aktif (Mode Demo)');
    });

    it('renders empty state when admissions list is empty', () => {
      const wrapper = mount(AdmissionTable, {
        props: {
          admissions: [],
          totalCount: 0,
          currentPage: 1,
          perPage: 10,
          loading: false
        }
      });

      expect(wrapper.text()).toContain('Tidak ada data admisi yang ditemukan.');
      expect(wrapper.text()).toContain('Coba ubah kata kunci pencarian atau reset filter untuk menampilkan data lainnya.');
    });
  });

  describe('AdmissionStatsCards', () => {
    it('renders 4 statistic cards with expected values and labels', () => {
      const wrapper = mount(AdmissionStatsCards, {
        props: {
          stats: {
            total: 248,
            waiting: 37,
            inService: 18,
            completed: 193,
            percentage: '77,8%'
          }
        }
      });

      expect(wrapper.text()).toContain('TOTAL ADMISI HARI INI');
      expect(wrapper.text()).toContain('248');
      expect(wrapper.text()).toContain('Termasuk BPJS & Pasien Umum');

      expect(wrapper.text()).toContain('MENUNGGU PELAYANAN');
      expect(wrapper.text()).toContain('37');
      expect(wrapper.text()).toContain('Di ruang tunggu 14 poliklinik');

      expect(wrapper.text()).toContain('SEDANG DILAYANI');
      expect(wrapper.text()).toContain('18');
      expect(wrapper.text()).toContain('Aktif di 8 poliklinik spesialis');

      expect(wrapper.text()).toContain('PELAYANAN SELESAI');
      expect(wrapper.text()).toContain('193');
      expect(wrapper.text()).toContain('77,8% dari target kuota harian');
    });
  });

  describe('AdmissionQueueCard', () => {
    it('renders Loket 02 and current queue A-024 with next queue A-025', () => {
      const wrapper = mount(AdmissionQueueCard, {
        props: {
          loket: mockLoketInfo,
          currentQueue: 'A-024',
          nextQueue: 'A-025'
        }
      });

      expect(wrapper.text()).toContain('Loket Admisi 02');
      expect(wrapper.text()).toContain('Loket Utama Rawat Jalan');
      expect(wrapper.text()).toContain('Admin SISFOMEDIKA');
      expect(wrapper.text()).toContain('A-024');
      expect(wrapper.text()).toContain('A-025');
      expect(wrapper.text()).toContain('Audio Online');
    });

    it('emits call-current when Panggil Antrean button is clicked', async () => {
      const wrapper = mount(AdmissionQueueCard, {
        props: {
          loket: mockLoketInfo,
          currentQueue: 'A-024',
          nextQueue: 'A-025'
        }
      });

      const callBtn = wrapper.find('.btn-call-hero');
      await callBtn.trigger('click');
      expect(wrapper.emitted('call-current')).toBeTruthy();
    });
  });
});
