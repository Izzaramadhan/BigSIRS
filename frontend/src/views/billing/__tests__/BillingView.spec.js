import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import BillingTable from '../components/BillingTable.vue';
import BillingStatsCards from '../components/BillingStatsCards.vue';
import BillingQuickFilters from '../components/BillingQuickFilters.vue';
import BillingClosingShiftPanel from '../components/BillingClosingShiftPanel.vue';
import BillingPagination from '../components/BillingPagination.vue';
import { generateMockBillingRecords, mockCashierShift } from '@/mocks/billing';

describe('Billing Rawat Jalan Components', () => {
  const mockRecords = generateMockBillingRecords();

  describe('BillingTable', () => {
    it('renders the 10 columns in EXACT required order', () => {
      const wrapper = mount(BillingTable, {
        props: {
          records: mockRecords.slice(0, 5),
          totalCount: 5,
          pageSubtotal: 3500000,
          currentPage: 1,
          perPage: 10,
          loading: false,
          selectedIds: new Set(),
          areAllVisibleSelected: false
        }
      });

      const thList = wrapper.findAll('thead th').map((th) => th.text().trim());

      // Expected strict columns from Section 8:
      // 1. Checkbox Pilih Data
      // 2. Nomor Tagihan
      // 3. Waktu Pelayanan
      // 4. No. RM & Pasien
      // 5. Poliklinik & DPJP
      // 6. Penjamin
      // 7. Total Tagihan
      // 8. Tanggungan Pasien
      // 9. Status
      // 10. Aksi Kasir
      expect(thList).toEqual([
        '', // Checkbox
        '1. Nomor Tagihan',
        '2. Waktu Pelayanan',
        '3. No. RM & Pasien',
        '4. Poliklinik & DPJP',
        '5. Penjamin',
        '6. Total Tagihan',
        '7. Tanggungan Pasien',
        '8. Status',
        '9. Aksi Kasir'
      ]);
    });

    it('displays the found count and formatted page subtotal', () => {
      const wrapper = mount(BillingTable, {
        props: {
          records: mockRecords.slice(0, 10),
          totalCount: 248,
          pageSubtotal: 5485000,
          currentPage: 1,
          perPage: 10,
          loading: false,
          selectedIds: new Set(),
          areAllVisibleSelected: false
        }
      });

      expect(wrapper.find('.count-badge').text()).toContain('Ditemukan 248 data tagihan');
      expect(wrapper.find('.subtotal-badge').text()).toContain('Rp 5.485.000');
    });

    it('renders contextual action buttons based on status', () => {
      const sample = [
        { ...mockRecords[0], status: 'Siap Dibayar' },
        { ...mockRecords[1], status: 'Lunas' },
        { ...mockRecords[2], status: 'Menunggu Verifikasi' },
        { ...mockRecords[3], status: 'Dibayar Sebagian' },
        { ...mockRecords[4], status: 'Belum Lengkap' },
        { ...mockRecords[5], status: 'Dibatalkan' }
      ];

      const wrapper = mount(BillingTable, {
        props: {
          records: sample,
          totalCount: 6,
          pageSubtotal: 3000000,
          currentPage: 1,
          perPage: 10,
          loading: false,
          selectedIds: new Set(),
          areAllVisibleSelected: false
        }
      });

      expect(wrapper.find('.btn-pay').exists()).toBe(true);
      expect(wrapper.find('.btn-receipt').exists()).toBe(true);
      expect(wrapper.find('.btn-verify').exists()).toBe(true);
      expect(wrapper.find('.btn-settle').exists()).toBe(true);
      expect(wrapper.find('.btn-notif-poli').exists()).toBe(true);
      expect(wrapper.find('.btn-cancel-note').exists()).toBe(true);
    });
  });

  describe('BillingStatsCards', () => {
    it('renders 5 statistic cards with labels and numbers', () => {
      const stats = {
        waiting: 18,
        inhealthClaims: 4,
        bpjsClaims: 14,
        ready: 24,
        elderlyReady: 6,
        cashEdcReady: 18,
        paid: 193,
        completionRatio: '91,2%',
        pending: 9,
        totalExcess: 3420000,
        todayIncome: 48250000,
        yesterdayIncome: 42180000,
        growth: '+14,2%'
      };

      const wrapper = mount(BillingStatsCards, {
        props: { stats }
      });

      expect(wrapper.text()).toContain('Menunggu Verifikasi');
      expect(wrapper.text()).toContain('18');
      expect(wrapper.text()).toContain('Siap Dibayar');
      expect(wrapper.text()).toContain('24');
      expect(wrapper.text()).toContain('Lunas Hari Ini');
      expect(wrapper.text()).toContain('193');
      expect(wrapper.text()).toContain('Belum Lunas');
      expect(wrapper.text()).toContain('9');
      expect(wrapper.text()).toContain('Pendapatan Hari Ini');
      expect(wrapper.text()).toContain('Rp 48.250.000');
    });
  });

  describe('BillingQuickFilters', () => {
    it('renders all 5 quick filter segmented chips with counts and emits on click', async () => {
      const counts = {
        all: 248,
        ready_cashier: 24,
        verify_bpjs: 18,
        general_today: 57,
        tariff_adjustment: 3
      };

      const wrapper = mount(BillingQuickFilters, {
        props: {
          activeFilter: 'all',
          counts
        }
      });

      expect(wrapper.text()).toContain('Semua Tagihan (248)');
      expect(wrapper.text()).toContain('Siap Bayar Kasir (24)');
      expect(wrapper.text()).toContain('Verifikasi BPJS (18)');
      expect(wrapper.text()).toContain('Pasien Umum Hari Ini (57)');
      expect(wrapper.text()).toContain('Perlu Penyesuaian Tarif (3)');

      const chips = wrapper.findAll('.quick-chip');
      await chips[1].trigger('click');
      expect(wrapper.emitted('select')[0]).toEqual(['ready_cashier']);
    });
  });

  describe('BillingClosingShiftPanel', () => {
    it('renders shift details and action buttons', async () => {
      const wrapper = mount(BillingClosingShiftPanel, {
        props: {
          shift: mockCashierShift
        }
      });

      expect(wrapper.text()).toContain('Validasi Closing Shift Kasir #1');
      expect(wrapper.text()).toContain('07:00 – 14:00 WIB');
      expect(wrapper.text()).toContain('Kasir_Poli_04');

      const buttons = wrapper.findAll('button');
      await buttons[0].trigger('click');
      expect(wrapper.emitted('reconcile')).toBeTruthy();

      await buttons[1].trigger('click');
      expect(wrapper.emitted('handover')).toBeTruthy();
    });
  });

  describe('BillingPagination', () => {
    it('renders pagination info and handles page change', async () => {
      const wrapper = mount(BillingPagination, {
        props: {
          currentPage: 1,
          lastPage: 25,
          perPage: 10,
          total: 248,
          from: 1,
          to: 10,
          loading: false
        }
      });

      expect(wrapper.text()).toContain('Menampilkan 1–10 dari 248 data tagihan');
      const nextBtn = wrapper.find('.btn-next');
      await nextBtn.trigger('click');
      expect(wrapper.emitted('page-change')[0]).toEqual([2]);
    });
  });
});
