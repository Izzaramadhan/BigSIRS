import { describe, it, expect, vi } from 'vitest';
import { mount, flushPromises } from '@vue/test-utils';
import PatientSearchView from '../PatientSearchView.vue';
import RegistrationPatientSearchCard from '../components/RegistrationPatientSearchCard.vue';
import RegistrationQueueCard from '../components/RegistrationQueueCard.vue';
import RegistrationPatientListCard from '../components/RegistrationPatientListCard.vue';
import PatientSearchTable from '../components/PatientSearchTable.vue';
import PatientSearchFilters from '../components/PatientSearchFilters.vue';
import { mockPatients } from '@/mocks/patients';
import patientService from '@/services/patient.service';

const mockPush = vi.fn();
vi.mock('vue-router', async (importOriginal) => {
  const actual = await importOriginal();
  return {
    ...actual,
    useRouter: () => ({
      push: mockPush
    }),
    useRoute: () => ({
      query: {},
      params: {}
    })
  };
});

vi.mock('@/services/master-data/guarantors.service', () => ({
  guarantorsService: {
    getGuarantors: vi.fn(async () => ({
      data: [
        { id: 2, name: 'UMUM', type: 'self_pay', is_active: true },
        { id: 3, name: 'JKN', type: 'bpjs', is_active: true },
        { id: 4, name: 'BPJS MANDIRI', type: 'bpjs', is_active: true }
      ]
    }))
  }
}));

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
      if (params.guarantor && params.guarantor !== 'all') {
        const g = String(params.guarantor).toLowerCase();
        filtered = filtered.filter((p) => {
          const pg = String(p.guarantor || '').toLowerCase();
          if (g === '2' || g === 'umum') return pg.includes('umum');
          if (g === '3' || g.includes('jkn') || g.includes('bpjs')) return pg.includes('bpjs') || pg.includes('jkn');
          return pg.includes(g);
        });
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

describe('PatientSearchView (Halaman Awal Pendaftaran Terbaru)', () => {
  it('renders FORM PENCARIAN PASIEN, NO. ANTRIAN, and DAFTAR PASIEN with 10 columns', async () => {
    const wrapper = mount(PatientSearchView, {
      global: {
        stubs: {
          AppToast: true,
          Teleport: true
        }
      }
    });

    await flushPromises();

    expect(wrapper.text()).toContain('Pendaftaran Pasien');
    expect(wrapper.text()).toContain('Home');
    expect(wrapper.text()).toContain('Data Pasien');
    expect(wrapper.text()).toContain('FORM PENCARIAN PASIEN');
    expect(wrapper.text()).toContain('NO. ANTRIAN');
    expect(wrapper.text()).toContain('DAFTAR PASIEN');

    expect(wrapper.find('input#reg-patient-query').exists()).toBe(true);
    expect(wrapper.find('input#reg-patient-birthdate').exists()).toBe(true);
    expect(wrapper.find('button.btn-cari-pasien').text()).toContain('Cari');
    expect(wrapper.find('button.btn-register-patient').text()).toContain('Registrasi Pasien Baru');
    expect(wrapper.find('button.btn-satusehat-kyc').text()).toContain('SatuSehat KYC');

    expect(wrapper.find('.queue-number-display').text()).toBe('P001');
    expect(wrapper.find('.btn-call-queue').text()).toContain('Panggil');
    expect(wrapper.find('.btn-prev-queue').text()).toContain('Prev');
    expect(wrapper.find('.btn-next-queue').text()).toContain('Next');

    expect(wrapper.find('select#reg-guarantor-filter').exists()).toBe(true);
    expect(wrapper.find('select#reg-records-perpage').exists()).toBe(true);

    const headers = wrapper
      .find('.registration-list-card')
      .findAll('thead th')
      .map((th) => th.text().trim());

    expect(headers).toEqual([
      'No.',
      'No. Antrian',
      'Waktu Admisi',
      'Nama',
      'No. HP',
      'Alamat',
      'Admisi ke',
      'Jenis Asuransi',
      'General Consent',
      'Aksi'
    ]);

    expect(wrapper.find('.btn-tools').text()).toContain('Tools');
  });

  it('controls NO. ANTRIAN Prev/Next queue numbers cleanly', async () => {
    const wrapper = mount(PatientSearchView, {
      global: {
        stubs: {
          AppToast: true,
          Teleport: true
        }
      }
    });

    expect(wrapper.find('.queue-number-display').text()).toBe('P001');
    await wrapper.find('.btn-next-queue').trigger('click');
    expect(wrapper.find('.queue-number-display').text()).toBe('P002');
    await wrapper.find('.btn-prev-queue').trigger('click');
    expect(wrapper.find('.queue-number-display').text()).toBe('P001');
  });

  it('searches registered patients, navigates to /pendaftaran/baru, and opens SatuSehat KYC modal', async () => {
    const wrapper = mount(PatientSearchView, {
      global: {
        stubs: {
          AppToast: true,
          Teleport: true
        }
      }
    });

    await wrapper.find('input#reg-patient-query').setValue('Bambang');
    await wrapper.find('form.patient-search-form').trigger('submit.prevent');

    expect(wrapper.find('.search-results-panel').exists()).toBe(true);
    expect(wrapper.find('.search-results-panel').text()).toContain('Bambang Setyadi');

    await wrapper.find('button.btn-register-patient').trigger('click');
    expect(mockPush).toHaveBeenCalledWith('/pendaftaran/baru');

    await wrapper.find('button.btn-satusehat-kyc').trigger('click');
    expect(wrapper.text()).toContain('Verifikasi SatuSehat KYC');
  });

  it('maintains top-cards-grid layout with top alignment and independent queue card height', async () => {
    const wrapper = mount(PatientSearchView, {
      global: {
        stubs: {
          AppToast: true,
          Teleport: true
        }
      }
    });

    const topGrid = wrapper.find('.top-cards-grid');
    expect(topGrid.exists()).toBe(true);

    const queueCard = wrapper.findComponent(RegistrationQueueCard);
    expect(queueCard.exists()).toBe(true);
    expect(queueCard.classes()).toContain('registration-queue-card');

    const searchCard = wrapper.findComponent(RegistrationPatientSearchCard);
    expect(searchCard.exists()).toBe(true);
    expect(searchCard.classes()).toContain('registration-search-card');

    // Perform search so search results appear
    await wrapper.find('input#reg-patient-query').setValue('Bambang');
    await wrapper.find('form.patient-search-form').trigger('submit.prevent');
    await flushPromises();

    // Verify search results are displayed inside search card
    expect(wrapper.find('.search-results-panel').exists()).toBe(true);

    // Queue card still renders cleanly with its buttons and queue display
    expect(queueCard.find('.queue-number-display').text()).toBe('P001');
    expect(queueCard.find('.btn-call-queue').exists()).toBe(true);
    expect(queueCard.find('.btn-prev-queue').exists()).toBe(true);
    expect(queueCard.find('.btn-next-queue').exists()).toBe(true);
  });

  it('filters DAFTAR PASIEN by Jenis Asuransi dropdown and table Search input', async () => {
    const wrapper = mount(PatientSearchView, {
      global: {
        stubs: {
          AppToast: true,
          Teleport: true
        }
      }
    });

    await flushPromises();

    // Options loaded dynamically from master data
    const options = wrapper.findAll('select#reg-guarantor-filter option');
    expect(options.map((o) => o.text().trim())).toEqual([
      'Semua Jenis Asuransi',
      'UMUM',
      'JKN',
      'BPJS MANDIRI'
    ]);
    expect(options.map((o) => o.attributes('value'))).toEqual([
      'all',
      '2',
      '3',
      '4'
    ]);

    await wrapper.find('select#reg-guarantor-filter').setValue('2');
    await flushPromises();
    const badges = wrapper.findAll('.insurance-badge').map((b) => b.text().trim());
    expect(badges.length).toBeGreaterThan(0);
    expect(badges.every((b) => b === 'UMUM')).toBe(true);

    await wrapper.find('select#reg-guarantor-filter').setValue('all');
    await flushPromises();
    expect(wrapper.find('.registration-list-card tbody').exists()).toBe(true);
  });

  it('renders single Tools dropdown button in search results with Detail, Edit, Delete, Admisi actions', async () => {
    const wrapper = mount(PatientSearchView, {
      global: {
        stubs: {
          AppToast: true,
          Teleport: false
        }
      }
    });

    await wrapper.find('input#reg-patient-query').setValue('Bambang');
    await wrapper.find('form.patient-search-form').trigger('submit.prevent');
    await flushPromises();

    const searchCard = wrapper.findComponent(RegistrationPatientSearchCard);
    expect(searchCard.exists()).toBe(true);

    // Verify only Tools button exists in Aksi column (no separate detail or admisi buttons beside it)
    const toolsBtn = searchCard.find('.btn-tools');
    expect(toolsBtn.exists()).toBe(true);
    expect(toolsBtn.text()).toContain('Tools');
    expect(searchCard.find('.btn-detail-sm').exists()).toBe(false);
    expect(searchCard.find('.btn-admit-sm').exists()).toBe(false);

    // Click Tools button
    await toolsBtn.trigger('click');

    // Dropdown portal should appear with 4 actions
    const dropdown = document.querySelector('.dropdown-portal');
    expect(dropdown).toBeTruthy();

    const actionButtons = Array.from(dropdown.querySelectorAll('.dropdown-action-btn')).map((btn) =>
      btn.textContent.trim()
    );
    expect(actionButtons).toEqual(['Detail', 'Edit', 'Delete', 'Admisi']);

    // Click Detail
    const detailBtn = dropdown.querySelectorAll('.dropdown-action-btn')[0];
    detailBtn.click();
    await flushPromises();
    expect(wrapper.find('.modal-header h3').exists()).toBe(true);

    // Search again and open Tools for Admisi
    await toolsBtn.trigger('click');
    const admisiBtn = document.querySelectorAll('.dropdown-action-btn')[3];
    admisiBtn.click();
    await flushPromises();
    expect(mockPush).toHaveBeenCalledWith({ path: '/admisi/baru', query: { patient_id: 1 } });
  });

  it('blocks navigation and displays notification on Pendaftaran if patient has unpaid billing', async () => {
    mockPush.mockClear();
    const originalHasUnpaid = mockPatients[0].has_unpaid_bills;
    mockPatients[0].has_unpaid_bills = true;
    mockPatients[0].unpaid_billing_message = 'Pasien ini belum lunas pembayaran. Silahkan lakukan pembayaran di Billing. Atau silahkan hubungi petugas jika mengalami kesulitan.';

    const wrapper = mount(PatientSearchView, {
      global: {
        stubs: {
          MasterDataPageHeader: true,
          AppToast: {
            template: '<div class="toast-stub" :data-type="type">{{ title }}: {{ message }}</div>',
            props: ['show', 'type', 'title', 'message']
          }
        }
      }
    });

    await flushPromises();

    await wrapper.find('input#reg-patient-query').setValue('Bambang');
    await wrapper.find('form.patient-search-form').trigger('submit.prevent');
    await flushPromises();

    const searchCard = wrapper.findComponent(RegistrationPatientSearchCard);
    const toolsBtn = searchCard.find('.btn-tools');
    expect(toolsBtn.exists()).toBe(true);
    await toolsBtn.trigger('click');

    const dropdown = document.querySelector('.dropdown-portal');
    expect(dropdown).toBeTruthy();
    const admisiBtn = dropdown.querySelectorAll('.dropdown-action-btn')[3];
    admisiBtn.click();
    await flushPromises();

    // Must NOT navigate
    expect(mockPush).not.toHaveBeenCalled();

    // Must show unpaid billing message on toast
    expect(wrapper.text()).toContain('Pasien ini belum lunas pembayaran. Silahkan lakukan pembayaran di Billing.');

    // Cleanup mockPatients
    mockPatients[0].has_unpaid_bills = originalHasUnpaid;
  });

  it('keeps component emit contracts on RegistrationPatientSearchCard, RegistrationQueueCard & RegistrationPatientListCard', async () => {
    const qWrapper = mount(RegistrationQueueCard, {
      props: { queueNumber: 'P005', canPrev: true }
    });
    await qWrapper.find('.btn-call-queue').trigger('click');
    expect(qWrapper.emitted('call')).toBeTruthy();

    const sWrapper = mount(RegistrationPatientSearchCard, {
      props: { keyword: 'Siti', birthDate: '', results: [], hasSearched: false }
    });
    await sWrapper.find('button.btn-satusehat-kyc').trigger('click');
    expect(sWrapper.emitted('open-kyc')).toBeTruthy();

    const lWrapper = mount(RegistrationPatientListCard, {
      props: {
        admissions: [],
        totalCount: 0,
        currentPage: 1,
        lastPage: 1,
        perPage: 10,
        from: 0,
        to: 0
      },
      global: {
        stubs: {
          Teleport: true
        }
      }
    });
    await lWrapper.find('th.col-queue').trigger('click');
    expect(lWrapper.emitted('sort')[0]).toEqual(['antrian']);
  });

  it('navigates to registration.admission-edit when Tools -> Edit Admisi is clicked from Daftar Pasien', async () => {
    mockPush.mockClear();
    const wrapper = mount(PatientSearchView, {
      global: {
        stubs: {
          AppToast: true,
          Teleport: true
        }
      }
    });

    await flushPromises();

    const listCard = wrapper.findComponent(RegistrationPatientListCard);
    expect(listCard.exists()).toBe(true);

    // Trigger edit-admission with an admission-bearing patient row
    await listCard.vm.$emit('edit-admission', {
      id: 1,
      admission_id: 10,
      visit_id: 20,
      patient_name: 'Bambang Setyadi'
    });

    expect(mockPush).toHaveBeenCalledWith({
      name: 'registration.admission-edit',
      params: { id: 10 },
      query: {
        visit_id: 20,
        from: 'pendaftaran'
      }
    });
  });

  it('displays warning toast when Edit Admisi is clicked on patient with no active admission', async () => {
    mockPush.mockClear();
    const wrapper = mount(PatientSearchView, {
      global: {
        stubs: {
          AppToast: true,
          Teleport: true
        }
      }
    });

    await flushPromises();

    const listCard = wrapper.findComponent(RegistrationPatientListCard);
    await listCard.vm.$emit('edit-admission', {
      id: 2,
      admission_id: null,
      patient_name: 'Pasien Baru Tanpa Admisi'
    });

    expect(mockPush).not.toHaveBeenCalled();
    expect(wrapper.vm.toast.show).toBe(true);
    expect(wrapper.vm.toast.type).toBe('warning');
    expect(wrapper.vm.toast.message).toContain('belum memiliki riwayat admisi untuk diedit');
  });
});

describe('Registration Patient Components', () => {
  describe('PatientSearchTable', () => {
    it('renders the 10 columns in EXACT required order', () => {
      const wrapper = mount(PatientSearchTable, {
        props: {
          patients: mockPatients.slice(0, 5),
          totalCount: 5,
          currentPage: 1,
          perPage: 10,
          loading: false,
          selectedPatientId: null
        }
      });

      const thList = wrapper.findAll('thead th').map(th => th.text().trim());
      
      // Expected strict columns:
      // 1: No
      // 2: No. RM
      // 3: NIK
      // 4: Nama Pasien
      // 5: Tgl Lahir (Usia)
      // 6: Gender
      // 7: No. Telepon
      // 8: Alamat Domisili
      // 9: Status
      // 10: Aksi
      expect(thList).toEqual([
        'No',
        'No. RM',
        'NIK',
        'Nama Pasien',
        'Tgl Lahir (Usia)',
        'Gender',
        'No. Telepon',
        'Alamat Domisili',
        'Status',
        'Aksi'
      ]);
    });

    it('displays the found count badge', () => {
      const wrapper = mount(PatientSearchTable, {
        props: {
          patients: mockPatients.slice(0, 8),
          totalCount: 8,
          currentPage: 1,
          perPage: 10,
          loading: false
        }
      });

      const countBadge = wrapper.find('.count-badge');
      expect(countBadge.text()).toContain('Ditemukan 8 pasien');
    });

    it('emits view-detail when eye icon is clicked', async () => {
      const wrapper = mount(PatientSearchTable, {
        props: {
          patients: [mockPatients[0]],
          totalCount: 1,
          currentPage: 1,
          perPage: 10,
          loading: false
        }
      });

      const eyeBtn = wrapper.find('.btn-action-icon');
      await eyeBtn.trigger('click');
      expect(wrapper.emitted('view-detail')).toBeTruthy();
      expect(wrapper.emitted('view-detail')[0][0]).toEqual(mockPatients[0]);
    });

    it('emits select-patient when Pilih Pasien button is clicked', async () => {
      const wrapper = mount(PatientSearchTable, {
        props: {
          patients: [mockPatients[0]],
          totalCount: 1,
          currentPage: 1,
          perPage: 10,
          loading: false
        }
      });

      const selectBtn = wrapper.find('.btn-select-patient');
      await selectBtn.trigger('click');
      expect(wrapper.emitted('select-patient')).toBeTruthy();
      expect(wrapper.emitted('select-patient')[0][0]).toEqual(mockPatients[0]);
    });

    it('displays empty state when patient list is empty', () => {
      const wrapper = mount(PatientSearchTable, {
        props: {
          patients: [],
          totalCount: 0,
          currentPage: 1,
          perPage: 10,
          loading: false
        }
      });

      expect(wrapper.text()).toContain('Data pasien tidak ditemukan.');
      expect(wrapper.text()).toContain('Periksa kembali informasi pencarian atau lakukan registrasi pasien baru.');
    });
  });

  describe('PatientSearchFilters', () => {
    it('renders the search input with proper placeholder', () => {
      const wrapper = mount(PatientSearchFilters, {
        props: {
          searchQuery: '',
          filters: {
            gender: 'all',
            status: 'all',
            birthDate: '',
            medicalRecordNumber: ''
          },
          isOpen: false,
          validationError: '',
          loading: false
        }
      });

      const input = wrapper.find('.search-input');
      expect(input.attributes('placeholder')).toBe('Cari berdasarkan Nama Pasien, No. RM, atau NIK...');
    });

    it('displays validation error when passed as prop', () => {
      const wrapper = mount(PatientSearchFilters, {
        props: {
          searchQuery: 'ab',
          filters: {
            gender: 'all',
            status: 'all',
            birthDate: '',
            medicalRecordNumber: ''
          },
          isOpen: false,
          validationError: 'Pencarian berdasarkan nama pasien minimal 3 karakter.',
          loading: false
        }
      });

      expect(wrapper.find('.validation-error-row').text()).toContain('minimal 3 karakter');
    });

    it('emits search event when Cari Pasien button is clicked', async () => {
      const wrapper = mount(PatientSearchFilters, {
        props: {
          searchQuery: 'Bambang',
          filters: {
            gender: 'all',
            status: 'all',
            birthDate: '',
            medicalRecordNumber: ''
          },
          isOpen: false,
          validationError: '',
          loading: false
        }
      });

      const searchBtn = wrapper.find('.btn-search');
      await searchBtn.trigger('click');
      expect(wrapper.emitted('search')).toBeTruthy();
    });
  });
});
