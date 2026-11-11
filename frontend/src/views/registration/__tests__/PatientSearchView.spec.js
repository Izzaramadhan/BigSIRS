import { describe, it, expect, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import PatientSearchView from '../PatientSearchView.vue';
import RegistrationPatientSearchCard from '../components/RegistrationPatientSearchCard.vue';
import RegistrationQueueCard from '../components/RegistrationQueueCard.vue';
import RegistrationPatientListCard from '../components/RegistrationPatientListCard.vue';
import PatientSearchTable from '../components/PatientSearchTable.vue';
import PatientSearchFilters from '../components/PatientSearchFilters.vue';
import { mockPatients } from '@/mocks/patients';

const mockPush = vi.fn();
vi.mock('vue-router', () => ({
  useRouter: () => ({
    push: mockPush
  })
}));

describe('PatientSearchView (Halaman Awal Pendaftaran Terbaru)', () => {
  it('renders FORM PENCARIAN PASIEN, NO. ANTRIAN, and DAFTAR PASIEN with 10 columns', () => {
    const wrapper = mount(PatientSearchView, {
      global: {
        stubs: {
          AppToast: true,
          Teleport: true
        }
      }
    });

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
    expect(wrapper.find('input#reg-table-search').exists()).toBe(true);

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

  it('filters DAFTAR PASIEN by Jenis Asuransi dropdown and table Search input', async () => {
    const wrapper = mount(PatientSearchView, {
      global: {
        stubs: {
          AppToast: true,
          Teleport: true
        }
      }
    });

    await wrapper.find('select#reg-guarantor-filter').setValue('UMUM');
    const badges = wrapper.findAll('.insurance-badge').map((b) => b.text().trim());
    expect(badges.length).toBeGreaterThan(0);
    expect(badges.every((b) => b === 'UMUM')).toBe(true);

    await wrapper.find('select#reg-guarantor-filter').setValue('all');
    await wrapper.find('input#reg-table-search').setValue('RM-000245');
    expect(wrapper.find('.registration-list-card tbody').text()).toContain('RM-000245');
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
