import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import ExaminationTable from '../components/ExaminationTable.vue';
import ExaminationStatsCards from '../components/ExaminationStatsCards.vue';
import ExaminationAudioDisplay from '../components/ExaminationAudioDisplay.vue';
import ExaminationPagination from '../components/ExaminationPagination.vue';
import { generateMockExaminations } from '@/mocks/examinations';

describe('Examination Rawat Jalan Components', () => {
  const mockList = generateMockExaminations();

  describe('ExaminationTable', () => {
    it('renders the 11 columns in EXACT required order', () => {
      const wrapper = mount(ExaminationTable, {
        props: {
          examinations: mockList.slice(0, 5),
          totalCount: 5,
          activeQueueCount: 5,
          currentPage: 1,
          perPage: 10,
          loading: false
        }
      });

      const thList = wrapper.findAll('thead th').map((th) => th.text().trim());

      // Expected strict 11 columns from Prompt Section 8:
      // 1. No. Antrean
      // 2. Waktu
      // 3. No. RM
      // 4. Pasien & Prioritas
      // 5. Usia / Gender
      // 6. Poliklinik Tujuan
      // 7. Dokter Pemeriksa
      // 8. Penjamin
      // 9. Waktu Tunggu
      // 10. Status
      // 11. Aksi Klinis
      expect(thList).toEqual([
        '1. No. Antrean',
        '2. Waktu',
        '3. No. RM',
        '4. Pasien & Prioritas',
        '5. Usia / Gender',
        '6. Poliklinik Tujuan',
        '7. Dokter Pemeriksa',
        '8. Penjamin',
        '9. Waktu Tunggu',
        '10. Status',
        '11. Aksi Klinis'
      ]);
    });

    it('displays summary count text accurately matching mock dataset', () => {
      const wrapper = mount(ExaminationTable, {
        props: {
          examinations: mockList.slice(0, 10),
          totalCount: 55,
          activeQueueCount: 55,
          currentPage: 1,
          perPage: 10,
          loading: false
        }
      });

      const badge = wrapper.find('.count-badge');
      expect(badge.text()).toContain('Menampilkan 10 dari 55 pasien dalam antrean aktif poliklinik');
    });

    it('renders priority badges when priority is not Normal', () => {
      const wrapper = mount(ExaminationTable, {
        props: {
          examinations: [
            mockList[0], // Lansia
            mockList[1]  // Cito
          ],
          totalCount: 2,
          currentPage: 1,
          perPage: 10,
          loading: false
        }
      });

      expect(wrapper.find('.priority-elderly').exists()).toBe(true);
      expect(wrapper.find('.priority-cito').exists()).toBe(true);
    });

    it('renders contextual action buttons based on status', () => {
      const sampleRows = [
        { ...mockList[0], status: 'Menunggu' },
        { ...mockList[1], status: 'Dipanggil' },
        { ...mockList[2], status: 'Sedang Diperiksa' },
        { ...mockList[3], status: 'Selesai' },
        { ...mockList[4], status: 'Dibatalkan' }
      ];

      const wrapper = mount(ExaminationTable, {
        props: {
          examinations: sampleRows,
          totalCount: 5,
          currentPage: 1,
          perPage: 10,
          loading: false
        }
      });

      expect(wrapper.find('.btn-start-exam').exists()).toBe(true);
      expect(wrapper.find('.btn-enter-clinic').exists()).toBe(true);
      expect(wrapper.find('.btn-open-rme').exists()).toBe(true);
      expect(wrapper.find('.btn-resume').exists()).toBe(true);
      expect(wrapper.find('.btn-cancel-detail').exists()).toBe(true);
    });
  });

  describe('ExaminationStatsCards', () => {
    it('renders 4 statistic cards with expected values and labels', () => {
      const stats = {
        waiting: 37,
        avgWaitTime: '~24 Menit',
        inService: 18,
        activePolyCount: 8,
        activeDoctorsCount: 14,
        completed: 193,
        completedPercentage: '77,8%',
        cancelled: 4
      };

      const wrapper = mount(ExaminationStatsCards, {
        props: { stats }
      });

      expect(wrapper.text()).toContain('MENUNGGU PEMERIKSAAN');
      expect(wrapper.text()).toContain('37');
      expect(wrapper.text()).toContain('SEDANG DIPERIKSA');
      expect(wrapper.text()).toContain('18');
      expect(wrapper.text()).toContain('PEMERIKSAAN SELESAI');
      expect(wrapper.text()).toContain('193');
      expect(wrapper.text()).toContain('DIBATALKAN / NO SHOW');
      expect(wrapper.text()).toContain('4');
    });
  });

  describe('ExaminationAudioDisplay', () => {
    it('renders the bottom audio display panel and emits replay-audio event on click', async () => {
      const wrapper = mount(ExaminationAudioDisplay, {
        props: {
          lastCalledPatient: mockList[0]
        }
      });

      expect(wrapper.text()).toContain('DISPLAY PEMANGGIL SUARA POLIKLINIK');
      expect(wrapper.text()).toContain('Speaker Ruang Antrean Poliklinik Aktif');
      expect(wrapper.text()).toContain('Simulasi display antrean — belum terhubung ke perangkat audio maupun SMS Gateway.');

      const replayBtn = wrapper.find('.btn-replay');
      expect(replayBtn.exists()).toBe(true);
      await replayBtn.trigger('click');
      expect(wrapper.emitted('replay-audio')).toBeTruthy();
    });
  });

  describe('ExaminationPagination', () => {
    it('renders pagination options and handles page navigation', async () => {
      const wrapper = mount(ExaminationPagination, {
        props: {
          currentPage: 1,
          lastPage: 6,
          perPage: 10,
          total: 55,
          from: 1,
          to: 10,
          loading: false
        }
      });

      expect(wrapper.text()).toContain('Menampilkan 1–10 dari 55 total antrean terdaftar');
      const nextBtn = wrapper.find('.btn-next');
      await nextBtn.trigger('click');
      expect(wrapper.emitted('page-change')[0]).toEqual([2]);
    });
  });
});
