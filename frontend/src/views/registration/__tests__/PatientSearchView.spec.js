import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import PatientSearchTable from '../components/PatientSearchTable.vue';
import PatientSearchFilters from '../components/PatientSearchFilters.vue';
import { mockPatients } from '@/mocks/patients';

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
