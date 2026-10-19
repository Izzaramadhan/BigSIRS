import { describe, it, expect, beforeEach } from 'vitest';
import { mount } from '@vue/test-utils';
import PosSaleView from '../PosSaleView.vue';
import PosSaleSummaryCards from '../components/PosSaleSummaryCards.vue';
import PosSaleFilters from '../components/PosSaleFilters.vue';
import PosSaleStatusBadge from '../components/PosSaleStatusBadge.vue';
import PosSaleTable from '../components/PosSaleTable.vue';
import PosSaleFormModal from '../components/PosSaleFormModal.vue';
import PosSaleDetailModal from '../components/PosSaleDetailModal.vue';
import PosSaleVoidDialog from '../components/PosSaleVoidDialog.vue';
import PosSaleDeleteDialog from '../components/PosSaleDeleteDialog.vue';
import { usePosSales } from '@/composables/usePosSales';

describe('Penjualan Bebas (OTC) Module Tests', () => {
  describe('usePosSales Composable', () => {
    let composable;

    beforeEach(() => {
      composable = usePosSales();
    });

    it('initializes with default mock sales and summary metrics', () => {
      expect(composable.sales.value.length).toBeGreaterThanOrEqual(10);
      expect(composable.summary.value.todayTransactions).toBe('42');
      expect(composable.summary.value.todayUnit).toBe('Nota');
      expect(composable.summary.value.productsSold).toBe('158');
      expect(composable.summary.value.totalSalesValue).toBe('4.875.000');
      expect(composable.summary.value.cancelledTransactions).toBe('2');
    });

    it('filters sales by transaction number, customer name, location, payment, and status', () => {
      // 1. Filter by transaction number
      composable.filters.transactionNumber = '0042';
      composable.applyFilters();
      expect(composable.filteredSales.value.length).toBe(1);
      expect(composable.filteredSales.value[0].transactionNumber).toBe('TR-OTC-20260915-0042');

      // 2. Filter by customer name
      composable.resetFilters();
      composable.filters.customerName = 'Rahmat Hidayat';
      composable.applyFilters();
      expect(composable.filteredSales.value.length).toBe(1);
      expect(composable.filteredSales.value[0].customerName).toBe('Bpk. Rahmat Hidayat');

      // 3. Filter by location
      composable.resetFilters();
      composable.filters.location = 'Apotek Rawat Jalan';
      composable.applyFilters();
      expect(
        composable.filteredSales.value.every((s) => s.location.includes('Apotek Rawat Jalan'))
      ).toBe(true);

      // 4. Filter by payment method
      composable.resetFilters();
      composable.filters.paymentMethod = 'QRIS';
      composable.applyFilters();
      expect(
        composable.filteredSales.value.every((s) => s.paymentMethod.toLowerCase() === 'qris')
      ).toBe(true);

      // 5. Filter by status
      composable.resetFilters();
      composable.filters.status = 'Dibatalkan';
      composable.applyFilters();
      expect(
        composable.filteredSales.value.every((s) => s.status === 'Dibatalkan')
      ).toBe(true);

      // 6. Reset filters
      composable.resetFilters();
      expect(composable.filteredSales.value.length).toBe(composable.sales.value.length);
    });

    it('supports table state switcher simulation', () => {
      composable.setTableState('skeleton');
      expect(composable.tableState.value).toBe('skeleton');

      composable.setTableState('empty');
      expect(composable.tableState.value).toBe('empty');
      expect(composable.paginatedSales.value.length).toBe(0);

      composable.setTableState('error');
      expect(composable.tableState.value).toBe('error');
      expect(composable.errorMessage.value).toContain('Gagal memuat data transaksi');

      composable.setTableState('normal');
      expect(composable.tableState.value).toBe('normal');
      expect(composable.paginatedSales.value.length).toBeGreaterThan(0);
    });

    it('creates, voids, and deletes sales correctly', async () => {
      const initialCount = composable.sales.value.length;

      // 1. Create paid sale
      const resCreate = await composable.createSale({
        customerName: 'Test Pembeli Baru',
        customerType: 'Umum',
        location: 'Farmasi Pusat (Kasir 1)',
        paymentMethod: 'Tunai',
        items: [
          { name: 'Paracetamol 500 mg Tablet', price: 15000, qty: 2, subtotal: 30000 }
        ]
      }, false);

      expect(resCreate.success).toBe(true);
      expect(composable.sales.value.length).toBe(initialCount + 1);
      expect(composable.sales.value[0].status).toBe('Lunas');
      expect(composable.sales.value[0].customerName).toBe('Test Pembeli Baru');

      // 2. Void sale
      const createdId = composable.sales.value[0].id;
      const resVoid = await composable.voidSale(createdId, 'Permintaan pembatalan');
      expect(resVoid.success).toBe(true);
      expect(composable.sales.value[0].status).toBe('Dibatalkan');
      expect(composable.sales.value[0].voidReason).toBe('Permintaan pembatalan');

      // 3. Delete sale
      const resDel = await composable.deleteSale(createdId);
      expect(resDel.success).toBe(true);
      expect(composable.sales.value.length).toBe(initialCount);
    });
  });

  describe('PosSaleSummaryCards Component', () => {
    it('renders all four statistical summary cards properly', () => {
      const summary = {
        todayTransactions: '42',
        todayUnit: 'Nota',
        todayChange: '+12% vs kemarin',
        todayCashier: 'Shift 1 & 2 Aktif',
        productsSold: '158',
        productsUnit: 'Unit Sediaan',
        productsSubtitle: 'Obat OTC, Vitamin, BMHP',
        totalSalesCurrency: 'Rp',
        totalSalesValue: '4.875.000',
        totalSalesNet: '100% Kas Masuk Bersih',
        totalSalesAverage: 'Rp 116.071/nota',
        cancelledTransactions: '2',
        cancelledUnit: 'Void Kasir',
        cancelledTotal: 'Rp 142.000',
        cancelledNote: 'Stok dikembalikan'
      };

      const wrapper = mount(PosSaleSummaryCards, {
        props: { summary }
      });

      expect(wrapper.text()).toContain('TRANSAKSI HARI INI');
      expect(wrapper.text()).toContain('42');
      expect(wrapper.text()).toContain('+12% vs kemarin');
      expect(wrapper.text()).toContain('Shift 1 & 2 Aktif');

      expect(wrapper.text()).toContain('PRODUK TERJUAL');
      expect(wrapper.text()).toContain('158');

      expect(wrapper.text()).toContain('TOTAL PENJUALAN');
      expect(wrapper.text()).toContain('4.875.000');
      expect(wrapper.text()).toContain('100% Kas Masuk Bersih');

      expect(wrapper.text()).toContain('TRANSAKSI DIBATALKAN');
      expect(wrapper.text()).toContain('2');
      expect(wrapper.text()).toContain('Rp 142.000');
    });
  });

  describe('PosSaleStatusBadge Component', () => {
    it('renders proper status badges', () => {
      const paid = mount(PosSaleStatusBadge, { props: { value: 'Lunas', type: 'status' } });
      expect(paid.classes()).toContain('badge-paid');

      const draft = mount(PosSaleStatusBadge, { props: { value: 'Draft', type: 'status' } });
      expect(draft.classes()).toContain('badge-draft');

      const cancelled = mount(PosSaleStatusBadge, { props: { value: 'Dibatalkan', type: 'status' } });
      expect(cancelled.classes()).toContain('badge-cancelled');
    });

    it('renders proper payment and customer badges', () => {
      const qris = mount(PosSaleStatusBadge, { props: { value: 'QRIS', type: 'payment' } });
      expect(qris.classes()).toContain('payment-qris');

      const cash = mount(PosSaleStatusBadge, { props: { value: 'Tunai', type: 'payment' } });
      expect(cash.classes()).toContain('payment-cash');

      const staff = mount(PosSaleStatusBadge, { props: { value: 'Staf RS', type: 'customer' } });
      expect(staff.classes()).toContain('cust-staff');
    });
  });

  describe('PosSaleFilters Component', () => {
    it('toggles collapse and emits apply and reset events', async () => {
      const wrapper = mount(PosSaleFilters, {
        props: {
          filters: {
            transactionNumber: '',
            customerName: '',
            startDate: '',
            endDate: '',
            location: 'Semua Lokasi',
            paymentMethod: 'Semua Metode',
            status: 'Semua Status'
          },
          locations: ['Semua Lokasi', 'Farmasi Pusat (Kasir 1)'],
          paymentMethods: ['Semua Metode', 'Tunai', 'QRIS'],
          statuses: ['Semua Status', 'Lunas']
        }
      });

      // Toggle collapse
      const toggleBtn = wrapper.find('.btn-toggle-filter');
      expect(toggleBtn.text()).toContain('Sembunyikan Filter');
      await toggleBtn.trigger('click');
      expect(toggleBtn.text()).toContain('Tampilkan Filter');

      // Submit apply
      await wrapper.find('form').trigger('submit');
      expect(wrapper.emitted('apply')).toBeTruthy();

      // Reset
      await wrapper.find('.btn-reset').trigger('click');
      expect(wrapper.emitted('reset')).toBeTruthy();
    });
  });

  describe('PosSaleTable Component', () => {
    it('renders live state switcher and rows', async () => {
      const items = [
        {
          id: 1,
          transactionNumber: 'TR-OTC-20260915-0042',
          dateDisplay: '15 Sep 2026',
          timeDisplay: '11:24 WIB',
          customerName: 'Bpk. Rahmat Hidayat',
          customerType: 'Umum',
          itemCount: 3,
          itemCountText: '3 Jenis',
          totalQtyText: '4 Strip, 1 Btl',
          totalAmount: 145000,
          paymentMethod: 'QRIS',
          paymentMethodDisplay: 'QRIS',
          status: 'Lunas',
          cashier: 'Rina Marlina (Kasir 1)'
        },
        {
          id: 2,
          transactionNumber: 'TR-OTC-20260915-0041',
          dateDisplay: '15 Sep 2026',
          timeDisplay: '10:50 WIB',
          customerName: 'Ibu Siti Nurhaliza',
          customerType: 'Umum',
          itemCount: 1,
          itemCountText: '1 Jenis',
          totalQtyText: '1 Botol',
          totalAmount: 68500,
          paymentMethod: 'Tunai',
          paymentMethodDisplay: 'Tunai (Cash)',
          status: 'Dibatalkan',
          cashier: 'Rina Marlina (Kasir 1)'
        }
      ];

      const wrapper = mount(PosSaleTable, {
        props: {
          items,
          paginationInfo: {
            current_page: 1,
            per_page: 10,
            last_page: 1,
            total: 2,
            from: 1,
            to: 2
          },
          tableState: 'normal'
        }
      });

      // Header and state switcher
      expect(wrapper.text()).toContain('Daftar Transaksi Kasir OTC');
      expect(wrapper.text()).toContain('Simulasi Tampilan Tabel (Live State Switcher)');
      expect(wrapper.text()).toContain('TR-OTC-20260915-0042');
      expect(wrapper.text()).toContain('Bpk. Rahmat Hidayat');

      // Strikethrough row for cancelled item
      const cancelledRow = wrapper.find('.row-cancelled');
      expect(cancelledRow.exists()).toBe(true);
      expect(cancelledRow.find('.text-cancelled.line-through').exists()).toBe(true);

      // Trigger state switcher button
      const skelBtn = wrapper.findAll('.state-btn').find((b) => b.text().includes('Skeleton'));
      await skelBtn.trigger('click');
      expect(wrapper.emitted('set-table-state')[0]).toEqual(['skeleton']);
    });
  });

  describe('PosSaleView Full Integration', () => {
    it('mounts full view with breadcrumb, titles, badges, and modals', async () => {
      const wrapper = mount(PosSaleView);

      // Breadcrumb & Page title
      expect(wrapper.text()).toContain('Logistik Farmasi');
      expect(wrapper.text()).toContain('Penjualan Bebas (OTC)');
      expect(wrapper.text()).toContain('Penjualan Bebas');
      expect(wrapper.text()).toContain('OTC-FARMASI');
      expect(wrapper.text()).toContain('POS V2.6');

      // Action buttons
      expect(wrapper.text()).toContain('Export Excel');
      expect(wrapper.text()).toContain('Rekap Kasir Harian');
      expect(wrapper.text()).toContain('+ Transaksi Baru');

      // Click + Transaksi Baru opens modal
      const newBtn = wrapper.findAll('button').find((b) => b.text().includes('+ Transaksi Baru'));
      await newBtn.trigger('click');
      expect(wrapper.findComponent(PosSaleFormModal).props('isOpen')).toBe(true);
    });
  });
});
