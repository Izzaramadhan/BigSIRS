import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import AdmissionTable from '../components/AdmissionTable.vue';
import AdmissionStatsCards from '../components/AdmissionStatsCards.vue';
import AdmissionQueueCard from '../components/AdmissionQueueCard.vue';
import { mockAdmissions, mockLoketInfo } from '@/mocks/admissions';

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
