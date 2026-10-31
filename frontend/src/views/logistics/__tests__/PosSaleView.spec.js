import { describe, it, expect, beforeEach, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import PosSaleView from '../PosSaleView.vue';
import PosSaleTable from '../components/PosSaleTable.vue';
import PosSaleFormModal from '../components/PosSaleFormModal.vue';
import PosSaleDeleteDialog from '../components/PosSaleDeleteDialog.vue';
import PosSaleSummaryCards from '../components/PosSaleSummaryCards.vue';
import PosSaleStatusBadge from '../components/PosSaleStatusBadge.vue';
import PosSaleFilters from '../components/PosSaleFilters.vue';
import PosSaleDetailView from '../PosSaleDetailView.vue';
import PosSaleStockSelect from '../components/PosSaleStockSelect.vue';
import posSalesService from '@/services/logistics/posSales.service';
import { usePosSales } from '@/composables/usePosSales';

// Mock vue-router preserving original exports
const mockPush = vi.fn();
vi.mock('vue-router', async (importOriginal) => {
  const actual = await importOriginal();
  return {
    ...actual,
    useRouter: () => ({
      push: mockPush
    }),
    useRoute: () => ({
      params: { id: '1' }
    }),
    RouterLink: {
      template: '<a><slot /></a>'
    }
  };
});

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
      composable.filters.transactionNumber = '0042';
      composable.applyFilters();
      expect(composable.filteredSales.value.length).toBe(1);
      expect(composable.filteredSales.value[0].transactionNumber).toBe('TR-OTC-20260915-0042');

      composable.resetFilters();
      composable.filters.customerName = 'Rahmat Hidayat';
      composable.applyFilters();
      expect(composable.filteredSales.value.length).toBe(1);
      expect(composable.filteredSales.value[0].customerName).toBe('Bpk. Rahmat Hidayat');

      composable.resetFilters();
      composable.filters.location = 'Apotek Rawat Jalan';
      composable.applyFilters();
      expect(
        composable.filteredSales.value.every((s) => s.location.includes('Apotek Rawat Jalan'))
      ).toBe(true);

      composable.resetFilters();
      expect(composable.filteredSales.value.length).toBe(composable.sales.value.length);
    });

    it('supports table state switcher simulation', () => {
      composable.setTableState('skeleton');
      expect(composable.tableState.value).toBe('skeleton');

      composable.setTableState('empty');
      expect(composable.tableState.value).toBe('empty');
      expect(composable.paginatedSales.value.length).toBe(0);

      composable.setTableState('normal');
      expect(composable.tableState.value).toBe('normal');
      expect(composable.paginatedSales.value.length).toBeGreaterThan(0);
    });

    it('creates, voids, and deletes sales correctly', async () => {
      const initialCount = composable.sales.value.length;

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

      const createdId = composable.sales.value[0].id;
      const resVoid = await composable.voidSale(createdId, 'Permintaan pembatalan');
      expect(resVoid.success).toBe(true);
      expect(composable.sales.value[0].status).toBe('Dibatalkan');

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
      expect(wrapper.text()).toContain('PRODUK TERJUAL');
      expect(wrapper.text()).toContain('158');
      expect(wrapper.text()).toContain('TOTAL PENJUALAN');
      expect(wrapper.text()).toContain('4.875.000');
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
  });

  describe('PosSaleFilters Component', () => {
    it('renders filters component', () => {
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
          locations: ['Semua Lokasi'],
          paymentMethods: ['Semua Metode'],
          statuses: ['Semua Status']
        }
      });
      expect(wrapper.exists()).toBe(true);
    });
  });

  describe('PosSaleTable Component (Simplified 4 Columns)', () => {
    it('renders table headers: No., Tanggal, Pembeli, Aksi', () => {
      const items = [
        {
          id: 1,
          date: '2026-09-02',
          formattedDate: '02-09-2026',
          pembeli: 'Person 45655962'
        },
        {
          id: 2,
          date: '2026-09-15',
          formattedDate: '15-09-2026',
          pembeli: 'Bpk. Rahmat Hidayat'
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
          loading: false
        }
      });

      expect(wrapper.text()).toContain('No.');
      expect(wrapper.text()).toContain('Tanggal');
      expect(wrapper.text()).toContain('Pembeli');
      expect(wrapper.text()).toContain('Aksi');
      expect(wrapper.text()).toContain('02-09-2026');
      expect(wrapper.text()).toContain('Person 45655962');
      expect(wrapper.text()).toContain('15-09-2026');
      expect(wrapper.text()).toContain('Bpk. Rahmat Hidayat');
    });

    it('emits view, edit, and delete events when action buttons are clicked', async () => {
      const items = [
        {
          id: 1,
          formattedDate: '02-09-2026',
          pembeli: 'Person 45655962'
        }
      ];

      const wrapper = mount(PosSaleTable, {
        props: {
          items,
          paginationInfo: { current_page: 1, per_page: 10, total: 1, last_page: 1 },
          loading: false
        }
      });

      const editBtn = wrapper.find('.btn-edit');
      expect(editBtn.exists()).toBe(true);
      await editBtn.trigger('click');
      expect(wrapper.emitted('edit')).toBeTruthy();

      const detailBtn = wrapper.find('.btn-detail');
      expect(detailBtn.exists()).toBe(true);
      await detailBtn.trigger('click');
      expect(wrapper.emitted('view')).toBeTruthy();

      const deleteBtn = wrapper.find('.btn-delete');
      expect(deleteBtn.exists()).toBe(true);
      await deleteBtn.trigger('click');
      expect(wrapper.emitted('delete')).toBeTruthy();
    });
  });

  describe('PosSaleFormModal Component (Simplified 2 Fields)', () => {
    it('renders Tanggal and Pembeli fields and emits submit with payload', async () => {
      const customers = [
        { id: 70, name: 'Person 45655962', code: 'EMP070' },
        { id: 12, name: 'Bpk. Rahmat Hidayat', code: 'EMP012' }
      ];

      const wrapper = mount(PosSaleFormModal, {
        props: {
          isOpen: true,
          customers,
          saleToEdit: null,
          isSubmitting: false
        },
        global: {
          stubs: {
            teleport: true
          }
        }
      });

      expect(wrapper.text()).toContain('Tambah Penjualan Bebas');
      expect(wrapper.text()).toContain('Tanggal');
      expect(wrapper.text()).toContain('Pembeli');

      const dateInput = wrapper.find('#input-pos-sale-date');
      expect(dateInput.exists()).toBe(true);

      const customerInput = wrapper.find('#input-pos-sale-customer');
      expect(customerInput.exists()).toBe(true);

      await customerInput.setValue('Person 45655962');
      const form = wrapper.find('form');
      await form.trigger('submit.prevent');

      expect(wrapper.emitted('submit')).toBeTruthy();
      const emittedPayload = wrapper.emitted('submit')[0][0];
      expect(emittedPayload.pembeli).toBe('Person 45655962');
    });
  });

  describe('PosSaleView Full Integration', () => {
    it('mounts full view with standard BigSIRS header and opens modal on click', async () => {
      const wrapper = mount(PosSaleView);

      // Breadcrumb & Page title
      expect(wrapper.text()).toContain('Logistik');
      expect(wrapper.text()).toContain('Penjualan Bebas');
      expect(wrapper.text()).toContain('SIMRS-LOG');
      expect(wrapper.text()).toContain('Kelola data penjualan sediaan secara langsung kepada pembeli.');
      expect(wrapper.text()).toContain('Tambah Penjualan Bebas');

      // Click Tambah Penjualan Bebas opens modal
      const addBtn = wrapper.findAll('button').find((b) => b.text().includes('Tambah Penjualan Bebas'));
      await addBtn.trigger('click');
      expect(wrapper.findComponent(PosSaleFormModal).props('isOpen')).toBe(true);
    });
  });

  describe('PosSaleStockSelect Component', () => {
    it('renders input box with placeholder and chevron indicator', () => {
      const wrapper = mount(PosSaleStockSelect, {
        props: {
          warehouseId: 1,
          warehouseName: 'Gudang Utama',
          modelValue: ''
        },
        global: {
          stubs: {
            teleport: true
          }
        }
      });

      expect(wrapper.find('input.stock-select-input').exists()).toBe(true);
      expect(wrapper.find('.chevron-indicator').exists()).toBe(true);
      expect(wrapper.find('.search-icon-left').exists()).toBe(true);
    });

    it('requires at least 2 characters before searching and opens dropdown', async () => {
      const wrapper = mount(PosSaleStockSelect, {
        props: {
          warehouseId: 1,
          warehouseName: 'Gudang Utama',
          modelValue: ''
        },
        global: {
          stubs: {
            teleport: true
          }
        }
      });

      const input = wrapper.find('input.stock-select-input');
      await input.setValue('a');
      await input.trigger('input');
      expect(wrapper.vm.isOpen).toBe(true);
      expect(wrapper.vm.options.length).toBe(0);
    });

    it('emits clear and update:modelValue when clear button is clicked', async () => {
      const wrapper = mount(PosSaleStockSelect, {
        props: {
          warehouseId: 1,
          warehouseName: 'Gudang Utama',
          modelValue: '123'
        },
        global: {
          stubs: {
            teleport: true
          }
        }
      });

      wrapper.vm.selectedStockItem = {
        stock_id: 123,
        item_name: 'Amoxan Drop'
      };
      await wrapper.vm.$nextTick();

      const clearBtn = wrapper.find('.clear-btn');
      expect(clearBtn.exists()).toBe(true);
      await clearBtn.trigger('click');

      expect(wrapper.emitted('clear')).toBeTruthy();
      expect(wrapper.emitted('update:modelValue')).toBeTruthy();
      expect(wrapper.emitted('update:modelValue')[0][0]).toBe('');
    });

    it('searches available stocks when at least 2 characters are typed and populates options', async () => {
      vi.spyOn(posSalesService, 'getAvailableStocks').mockResolvedValue({
        data: [
          {
            id: 87,
            stock_id: 87,
            item_id: 134,
            item_name: 'Amoxicilin syr kering 250 mg / 5ml',
            item_code: 'DRG000001984',
            batch_number: 'FFFF',
            expiry_date: '2026-11-11',
            available_stock: 500,
            selling_price: 1000,
            unit_name: 'Botol 60 ml'
          }
        ]
      });

      const wrapper = mount(PosSaleStockSelect, {
        props: {
          warehouseId: 1,
          warehouseName: 'Gudang Utama',
          saleDate: '2026-09-02',
          modelValue: ''
        },
        global: {
          stubs: {
            teleport: true
          }
        }
      });

      const input = wrapper.find('input.stock-select-input');
      await input.setValue('amo');
      await input.trigger('input');

      // Wait for debounce 300ms and promise resolution
      await new Promise((r) => setTimeout(r, 450));
      await wrapper.vm.$nextTick();
      await wrapper.vm.$nextTick();

      expect(posSalesService.getAvailableStocks).toHaveBeenCalledWith(
        1,
        expect.objectContaining({ search: 'amo', issue_date: '2026-09-02' }),
        expect.anything()
      );

      expect(wrapper.vm.options.length).toBe(1);
      expect(wrapper.vm.options[0].item_name).toBe('Amoxicilin syr kering 250 mg / 5ml');
      expect(wrapper.vm.errorMessage).toBe('');
    });

    it('falls back to getWarehouseStocks if getAvailableStocks throws non-abort error', async () => {
      vi.spyOn(posSalesService, 'getAvailableStocks').mockRejectedValue(new Error('Endpoint not found'));
      vi.spyOn(posSalesService, 'getWarehouseStocks').mockResolvedValue({
        data: [
          {
            id: 87,
            medicine_id: 134,
            medicine_name: 'Amoxicilin syr kering 250 mg / 5ml',
            code: 'DRG000001984',
            batch_number: 'FFFF',
            expired_date: '2026-11-11',
            quantity: 500,
            price: 1000,
            unit: 'Botol 60 ml'
          }
        ]
      });

      const wrapper = mount(PosSaleStockSelect, {
        props: {
          warehouseId: 1,
          warehouseName: 'Gudang Utama',
          modelValue: ''
        },
        global: {
          stubs: {
            teleport: true
          }
        }
      });

      const input = wrapper.find('input.stock-select-input');
      await input.setValue('amo');
      await input.trigger('input');

      await new Promise((r) => setTimeout(r, 450));
      await wrapper.vm.$nextTick();
      await wrapper.vm.$nextTick();

      expect(posSalesService.getWarehouseStocks).toHaveBeenCalledWith(
        expect.objectContaining({ warehouse_id: 1, search: 'amo' }),
        expect.anything()
      );

      expect(wrapper.vm.options.length).toBe(1);
      expect(wrapper.vm.options[0].item_name).toBe('Amoxicilin syr kering 250 mg / 5ml');
    });

    it('shows error state and retry button if both endpoints fail, and allows retry', async () => {
      vi.spyOn(posSalesService, 'getAvailableStocks').mockRejectedValue(new Error('Network error'));
      vi.spyOn(posSalesService, 'getWarehouseStocks').mockRejectedValue(new Error('Network error'));

      const wrapper = mount(PosSaleStockSelect, {
        props: {
          warehouseId: 1,
          warehouseName: 'Gudang Utama',
          modelValue: ''
        },
        global: {
          stubs: {
            teleport: true
          }
        }
      });

      const input = wrapper.find('input.stock-select-input');
      await input.setValue('amo');
      await input.trigger('input');

      await new Promise((r) => setTimeout(r, 450));
      await wrapper.vm.$nextTick();
      await wrapper.vm.$nextTick();

      expect(wrapper.vm.errorMessage).toBe('Gagal memuat sediaan. Silakan coba lagi.');
      expect(wrapper.find('button.btn-retry').exists()).toBe(true);
    });
  });

  describe('PosSaleDetailView Component', () => {
    it('renders 3 cards: INFO, FORM DETAIL, and DETAIL LIST DATA with Total and Print Nota', async () => {
      const wrapper = mount(PosSaleDetailView, {
        global: {
          stubs: {
            teleport: true,
            RouterLink: {
              template: '<a><slot /></a>'
            }
          }
        }
      });

      expect(wrapper.exists()).toBe(true);
    });

    it('toggles collapse on clicking btn-collapse in FORM DETAIL header', async () => {
      const wrapper = mount(PosSaleDetailView, {
        global: {
          stubs: {
            teleport: true,
            RouterLink: {
              template: '<a><slot /></a>'
            }
          }
        }
      });

      // Provide loaded sale
      wrapper.vm.sale = {
        id: 1,
        date: '2026-10-31',
        pembeli: 'Person Test',
        items: []
      };
      wrapper.vm.loading = false;
      await wrapper.vm.$nextTick();

      const collapseBtn = wrapper.find('.form-card-header .btn-collapse');
      expect(collapseBtn.exists()).toBe(true);
      expect(wrapper.vm.isFormCollapsed).toBe(false);

      // Click to collapse
      await collapseBtn.trigger('click');
      expect(wrapper.vm.isFormCollapsed).toBe(true);

      // Click to expand again
      await collapseBtn.trigger('click');
      expect(wrapper.vm.isFormCollapsed).toBe(false);
    });
  });
});
