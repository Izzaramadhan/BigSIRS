import { describe, it, expect, beforeEach } from 'vitest';
import { mount } from '@vue/test-utils';
import StockOpnameView from '../StockOpnameView.vue';
import StockOpnameSummaryCards from '../components/StockOpnameSummaryCards.vue';
import StockOpnameFilters from '../components/StockOpnameFilters.vue';
import StockOpnameStatusBadge from '../components/StockOpnameStatusBadge.vue';
import StockOpnameTable from '../components/StockOpnameTable.vue';
import StockOpnameFormModal from '../components/StockOpnameFormModal.vue';
import StockOpnameDetailModal from '../components/StockOpnameDetailModal.vue';
import { useStockOpnames } from '@/composables/useStockOpnames';

describe('Stock Opname Module Tests', () => {
  describe('useStockOpnames Composable', () => {
    let composable;

    beforeEach(() => {
      composable = useStockOpnames();
    });

    it('initializes with default mock sessions and summary stats', () => {
      expect(composable.opnames.value.length).toBeGreaterThanOrEqual(6);
      expect(composable.summary.value.inProgressSessions).toBe('3 Sesi');
      expect(composable.summary.value.pendingReviewWarehouses).toBe('2 Gudang');
      expect(composable.summary.value.completedThisMonthSessions).toBe('18 Sesi');
      expect(composable.summary.value.discrepancyItems).toBe('14 Item');
      expect(composable.summary.value.discrepancyValue).toBe('Rp1.840.000');
    });

    it('filters sessions by opname number, warehouse, and status', () => {
      // 1. Filter by opname number
      composable.filters.opnameNumber = '0045';
      composable.applyFilters();
      expect(composable.filteredOpnames.value.length).toBe(1);
      expect(composable.filteredOpnames.value[0].opnameNumber).toBe('SO-202609-0045');

      // 2. Filter by warehouse
      composable.resetFilters();
      composable.filters.warehouse = 'Gudang Utama Farmasi';
      composable.applyFilters();
      expect(
        composable.filteredOpnames.value.every((o) => o.warehouse === 'Gudang Utama Farmasi')
      ).toBe(true);

      // 3. Filter by status
      composable.resetFilters();
      composable.filters.status = 'Menunggu Peninjauan';
      composable.applyFilters();
      expect(
        composable.filteredOpnames.value.every((o) => o.status === 'Menunggu Peninjauan')
      ).toBe(true);

      // 4. Reset filters
      composable.resetFilters();
      expect(composable.filteredOpnames.value.length).toBe(composable.opnames.value.length);
    });

    it('creates a new stock opname session (active and draft)', async () => {
      const initialCount = composable.opnames.value.length;

      // Create active opname
      const resActive = await composable.createStockOpname({
        title: 'Audit Darurat IGD',
        warehouse: 'Apotek IGD 24 Jam',
        officerName: 'Apt. Dimas',
        type: 'Audit Khusus'
      }, false);

      expect(resActive.success).toBe(true);
      expect(composable.opnames.value.length).toBe(initialCount + 1);
      expect(composable.opnames.value[0].status).toBe('Sedang Dihitung');

      // Create draft opname
      const resDraft = await composable.createStockOpname({
        title: 'Draft Audit Akhir Bulan',
        warehouse: 'Gudang Utama Farmasi',
        officerName: 'Apt. Budi',
        type: 'Audit Akhir Bulan'
      }, true);

      expect(resDraft.success).toBe(true);
      expect(composable.opnames.value[0].status).toBe('Draft Sesi');
    });

    it('cancels and approves stock opnames correctly', async () => {
      // Cancel
      const resCancel = await composable.cancelStockOpname(1, 'Dibatalkan oleh penguji.');
      expect(resCancel.success).toBe(true);
      const item1 = composable.opnames.value.find((o) => o.id === 1);
      expect(item1.status).toBe('Dibatalkan');
      expect(item1.cancelReason).toBe('Dibatalkan oleh penguji.');

      // Approve
      const resApprove = await composable.approveStockOpname(2);
      expect(resApprove.success).toBe(true);
      const item2 = composable.opnames.value.find((o) => o.id === 2);
      expect(item2.status).toBe('Selesai');
      expect(item2.differenceStatus).toContain('Adjusted');
    });
  });

  describe('StockOpnameSummaryCards Component', () => {
    it('renders 4 summary cards with correct metrics and notes', () => {
      const summaryMock = {
        inProgressSessions: '3 Sesi',
        inProgressNote: '1.420 item dalam proses hitung fisik',
        pendingReviewWarehouses: '2 Gudang',
        pendingReviewNote: 'Perlu persetujuan Apoteker & PJ Logistik',
        completedThisMonthSessions: '18 Sesi',
        completedThisMonthNote: 'Akurasi stok rata-rata 99,4%',
        discrepancyItems: '14 Item',
        discrepancyValue: 'Rp1.840.000',
        discrepancyRatio: '8+ / 6-'
      };

      const wrapper = mount(StockOpnameSummaryCards, {
        props: { summary: summaryMock }
      });

      expect(wrapper.text()).toContain('SEDANG BERLANGSUNG');
      expect(wrapper.text()).toContain('3 Sesi');
      expect(wrapper.text()).toContain('MENUNGGU PENINJAUAN');
      expect(wrapper.text()).toContain('2 Gudang');
      expect(wrapper.text()).toContain('SELESAI BULAN INI');
      expect(wrapper.text()).toContain('18 Sesi');
      expect(wrapper.text()).toContain('TOTAL SELISIH DITEMUKAN');
      expect(wrapper.text()).toContain('14 Item');
      expect(wrapper.text()).toContain('Rp1.840.000');
    });
  });

  describe('StockOpnameFilters Component', () => {
    it('emits apply and reset events properly without mutating props', async () => {
      const filtersMock = {
        opnameNumber: '',
        startDate: '',
        endDate: '',
        warehouse: 'Semua Gudang',
        status: 'Semua Status'
      };

      const wrapper = mount(StockOpnameFilters, {
        props: {
          filters: filtersMock,
          warehouses: ['Gudang Utama Farmasi', 'Apotek IGD 24 Jam'],
          statuses: ['Semua Status', 'Draft Sesi', 'Sedang Dihitung', 'Selesai'],
          loading: false
        }
      });

      expect(wrapper.text()).toContain('Pencarian & Filter Data Stok Opname');

      await wrapper.find('form').trigger('submit.prevent');
      expect(wrapper.emitted('apply')).toBeTruthy();

      await wrapper.find('button.btn-cancel').trigger('click');
      expect(wrapper.emitted('reset')).toBeTruthy();
    });
  });

  describe('StockOpnameStatusBadge Component', () => {
    it('renders session badge styles correctly', () => {
      const wrapperCounting = mount(StockOpnameStatusBadge, {
        props: { type: 'session', status: 'Sedang Dihitung' }
      });
      expect(wrapperCounting.classes()).toContain('badge-counting');
      expect(wrapperCounting.text()).toBe('Sedang Dihitung');

      const wrapperReview = mount(StockOpnameStatusBadge, {
        props: { type: 'session', status: 'Menunggu Peninjauan' }
      });
      expect(wrapperReview.classes()).toContain('badge-review');

      const wrapperCompleted = mount(StockOpnameStatusBadge, {
        props: { type: 'session', status: 'Selesai' }
      });
      expect(wrapperCompleted.classes()).toContain('badge-completed');

      const wrapperCancelled = mount(StockOpnameStatusBadge, {
        props: { type: 'session', status: 'Dibatalkan' }
      });
      expect(wrapperCancelled.classes()).toContain('badge-cancelled');
    });

    it('renders difference status badges correctly', () => {
      const wrapperDiff = mount(StockOpnameStatusBadge, {
        props: { type: 'difference', status: '4 Item (+/-)', variant: 'danger' }
      });
      expect(wrapperDiff.classes()).toContain('diff-danger');
      expect(wrapperDiff.text()).toContain('4 Item (+/-)');
    });
  });

  describe('StockOpnameTable Component', () => {
    const itemsMock = [
      {
        id: 1,
        opnameNumber: 'SO-202609-0045',
        title: 'Batch Audit Rutin Pagi',
        dateDisplay: '15 Sep 2026',
        timeDisplay: '08:30 WIB',
        warehouse: 'Gudang Utama Farmasi',
        locationDetail: 'Rak A1 - Rak C4',
        totalItems: 350,
        countedItems: 245,
        differenceStatus: '4 Item (+/-)',
        differenceType: 'danger',
        progressPercent: 70,
        progressText: '70% – 105 sisa',
        officerName: 'Apt. Budi Santoso, S.Farm',
        officerRole: 'SIP: 19840212/2021',
        status: 'Sedang Dihitung'
      },
      {
        id: 2,
        opnameNumber: 'SO-202609-0040',
        title: 'Salah Pemilihan Depo',
        dateDisplay: '10 Sep 2026',
        timeDisplay: '11:20 WIB',
        warehouse: 'Apotek IGD 24 Jam',
        locationDetail: 'Gedung Emergency',
        totalItems: 95,
        countedItems: null,
        differenceStatus: '-',
        differenceType: 'muted',
        progressPercent: 0,
        progressText: 'Dibatalkan',
        officerName: 'Apt. Dimas Prasetya',
        officerRole: 'Admin Logistik',
        status: 'Dibatalkan'
      }
    ];

    const paginationInfoMock = {
      current_page: 1,
      per_page: 10,
      last_page: 1,
      total: 2,
      from: 1,
      to: 2
    };

    it('renders 11 columns and handles row actions', async () => {
      const wrapper = mount(StockOpnameTable, {
        props: {
          items: itemsMock,
          paginationInfo: paginationInfoMock,
          loading: false
        }
      });

      expect(wrapper.text()).toContain('DAFTAR SESI AUDIT STOK');
      expect(wrapper.text()).toContain('2 Dokumen');
      expect(wrapper.text()).toContain('SO-202609-0045');
      expect(wrapper.text()).toContain('Gudang Utama Farmasi');
      expect(wrapper.text()).toContain('245');
      expect(wrapper.text()).toContain('350');
      expect(wrapper.text()).toContain('Apt. Budi Santoso, S.Farm');

      // Click Lanjutkan button
      const continueBtn = wrapper.find('.btn-continue');
      expect(continueBtn.exists()).toBe(true);
      await continueBtn.trigger('click');
      expect(wrapper.emitted('continue')).toBeTruthy();

      // Cancelled row has dimmed class
      const cancelledRow = wrapper.findAll('tbody tr')[1];
      expect(cancelledRow.classes()).toContain('row-cancelled');
    });

    it('renders empty state when items array is empty', () => {
      const wrapper = mount(StockOpnameTable, {
        props: {
          items: [],
          paginationInfo: { current_page: 1, per_page: 10, last_page: 1, total: 0, from: 0, to: 0 },
          loading: false
        }
      });

      expect(wrapper.text()).toContain('Belum ada sesi stok opname.');
    });

    it('renders skeleton rows when loading is true', () => {
      const wrapper = mount(StockOpnameTable, {
        props: {
          items: [],
          paginationInfo: { current_page: 1, per_page: 10, last_page: 1, total: 0, from: 0, to: 0 },
          loading: true
        }
      });

      expect(wrapper.findAll('.skeleton-row').length).toBe(6);
    });
  });

  describe('StockOpnameFormModal Component', () => {
    it('validates required fields and emits submit with isDraft flag', async () => {
      const wrapper = mount(StockOpnameFormModal, {
        props: {
          isOpen: true,
          warehouses: ['Gudang Utama Farmasi', 'Apotek IGD 24 Jam'],
          isSubmitting: false
        },
        global: {
          stubs: {
            teleport: true
          }
        }
      });

      expect(wrapper.text()).toContain('Mulai Sesi Stok Opname');

      // Click submit (Mulai)
      const startBtn = wrapper.find('button.btn-primary');
      await startBtn.trigger('click');
      expect(wrapper.emitted('submit')).toBeTruthy();
      expect(wrapper.emitted('submit')[0][0].isDraft).toBe(false);

      // Click Simpan Draft
      const draftBtn = wrapper.find('button.btn-outline');
      await draftBtn.trigger('click');
      expect(wrapper.emitted('submit')[1][0].isDraft).toBe(true);
    });
  });

  describe('StockOpnameDetailModal Component', () => {
    it('renders session details and item list', () => {
      const opnameMock = {
        id: 1,
        opnameNumber: 'SO-202609-0045',
        title: 'Batch Audit Rutin Pagi',
        dateDisplay: '15 Sep 2026',
        timeDisplay: '08:30 WIB',
        warehouse: 'Gudang Utama Farmasi',
        locationDetail: 'Rak A1 - Rak C4',
        type: 'Audit Rutin',
        officerName: 'Apt. Budi Santoso, S.Farm',
        officerRole: 'SIP: 19840212/2021',
        totalItems: 350,
        countedItems: 245,
        differenceStatus: '4 Item (+/-)',
        differenceType: 'danger',
        progressText: '70% – 105 sisa',
        status: 'Sedang Dihitung',
        notes: 'Audit rutin persediaan oral.',
        items: [
          { id: 1, name: 'Paracetamol 500 mg', systemStock: 100, physicalStock: 98, difference: -2, unit: 'Tablet' }
        ]
      };

      const wrapper = mount(StockOpnameDetailModal, {
        props: {
          isOpen: true,
          opname: opnameMock
        },
        global: {
          stubs: {
            teleport: true
          }
        }
      });

      expect(wrapper.text()).toContain('SO-202609-0045');
      expect(wrapper.text()).toContain('Batch Audit Rutin Pagi');
      expect(wrapper.text()).toContain('Gudang Utama Farmasi');
      expect(wrapper.text()).toContain('Paracetamol 500 mg');
      expect(wrapper.text()).toContain('-2');
    });
  });

  describe('StockOpnameView Integration', () => {
    it('mounts StockOpnameView successfully with breadcrumb, header, cards, and table', () => {
      const wrapper = mount(StockOpnameView, {
        global: {
          stubs: {
            teleport: true
          }
        }
      });

      expect(wrapper.text()).toContain('Logistik Farmasi');
      expect(wrapper.text()).toContain('Stok Opname Farmasi');
      expect(wrapper.text()).toContain('SIMRS-LOG');
      expect(wrapper.text()).toContain('SEDANG BERLANGSUNG');
      expect(wrapper.text()).toContain('Pencarian & Filter Data Stok Opname');
      expect(wrapper.text()).toContain('DAFTAR SESI AUDIT STOK');
    });
  });
});
