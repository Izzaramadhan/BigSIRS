import { describe, it, expect, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import AdmissionView from '../AdmissionView.vue';
import AdmissionEditView from '../AdmissionEditView.vue';
import AdmissionOldPatientSearchCard from '../components/AdmissionOldPatientSearchCard.vue';
import AdmissionPatientListCard from '../components/AdmissionPatientListCard.vue';
import AdmissionTable from '../components/AdmissionTable.vue';
import AdmissionStatsCards from '../components/AdmissionStatsCards.vue';
import AdmissionQueueCard from '../components/AdmissionQueueCard.vue';
import { mockAdmissions, mockLoketInfo } from '@/mocks/admissions';

const mockPush = vi.fn();
vi.mock('vue-router', () => ({
  useRoute: () => ({
    params: { id: '1' }
  }),
  useRouter: () => ({
    push: mockPush
  })
}));

describe('AdmissionView (Halaman Awal Admisi Terbaru)', () => {
  it('renders FORM PENCARIAN PASIEN LAMA and DAFTAR PASIEN with 6 standardized columns', () => {
    const wrapper = mount(AdmissionView, {
      global: {
        stubs: {
          MasterDataPageHeader: false,
          AppToast: true,
          Teleport: true
        }
      }
    });

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

    expect(wrapper.find('.search-results-panel').exists()).toBe(true);
    expect(wrapper.find('.search-results-panel').text()).toContain('Bambang Setyadi');

    await wrapper.find('.btn-admit-patient').trigger('click');
    expect(wrapper.find('.modal-overlay').exists()).toBe(true);
    expect(wrapper.find('input#add-patient-name').element.value).toContain('Bambang Setyadi');
  });

  it('filters DAFTAR PASIEN by Jenis Asuransi dropdown (UMUM / BPJS Kesehatan)', async () => {
    const wrapper = mount(AdmissionView, {
      global: {
        stubs: {
          AppToast: true,
          Teleport: true
        }
      }
    });

    const guarantorSelect = wrapper.find('select#admission-guarantor-filter');
    await guarantorSelect.setValue('UMUM');

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

    await wrapper.find('.btn-tools').trigger('click');
    const editBtn = wrapper.findAll('.dropdown-action-btn')[0];
    expect(editBtn.text()).toContain('Edit');

    await editBtn.trigger('click');
    expect(mockPush).toHaveBeenCalledWith({
      name: 'registration.admission-edit',
      params: { id: expect.anything() }
    });
  });
});

describe('AdmissionEditView (Halaman Edit Data Admisi)', () => {
  it('renders DATA PASIEN card and FORM EDIT with 3 sub-panels', () => {
    const wrapper = mount(AdmissionEditView, {
      global: {
        stubs: {
          Teleport: true
        }
      }
    });

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

  it('disables No. Asuransi for UMUM, enables for BPJS, and validates required fields', async () => {
    const wrapper = mount(AdmissionEditView, {
      global: {
        stubs: {
          Teleport: true
        }
      }
    });

    const guarantorSelect = wrapper.find('#edit-guarantor');
    const insuranceInput = wrapper.find('#edit-insurance-number');

    await guarantorSelect.setValue('UMUM');
    expect(insuranceInput.element.disabled).toBe(true);

    await guarantorSelect.setValue('BPJS Kesehatan');
    expect(insuranceInput.element.disabled).toBe(false);

    await wrapper.find('#edit-referral-source').setValue('FKT Primer');
    await wrapper.find('#edit-referral-name').setValue('');
    await wrapper.find('form').trigger('submit');

    expect(wrapper.find('.error-message').text()).toContain('Nama Asal Rujukan wajib diisi.');
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

      await wrapper.find('#edit-referral-name').setValue('Puskesmas Banguntapan I');
      const doctorOptions = wrapper.findAll('#edit-doctor option').filter((opt) => opt.element.value);
      if (doctorOptions.length > 0) {
        await wrapper.find('#edit-doctor').setValue(doctorOptions[0].element.value);
      }
      await wrapper.find('#edit-family-name').setValue('Sabirin');
      await wrapper.find('form').trigger('submit');

      expect(wrapper.text()).toContain('Data Admisi Diperbarui');
      vi.advanceTimersByTime(600);
      expect(mockPush).toHaveBeenCalledWith('/admisi');
    } finally {
      vi.useRealTimers();
    }
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
