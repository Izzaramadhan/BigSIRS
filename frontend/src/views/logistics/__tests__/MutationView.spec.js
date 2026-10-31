import { describe, it, expect, beforeEach, vi } from 'vitest';
import { mount, flushPromises } from '@vue/test-utils';
import MutationView from '../MutationView.vue';
import MutationSummaryCards from '../components/MutationSummaryCards.vue';
import MutationFilters from '../components/MutationFilters.vue';
import MutationAuditBanner from '../components/MutationAuditBanner.vue';
import MutationTable from '../components/MutationTable.vue';
import MutationFormModal from '../components/MutationFormModal.vue';
import MutationDetailModal from '../components/MutationDetailModal.vue';
import { useMutations } from '@/composables/useMutations';
import mutationsService from '@/services/logistics/mutations.service';

vi.mock('@/services/logistics/mutations.service', () => ({
  default: {
    getMutations: vi.fn(),
    getSummary: vi.fn(),
    getMutation: vi.fn(),
    createMutation: vi.fn(),
    getWarehouseStocks: vi.fn(),
    getWarehousesLookup: vi.fn(),
    exportCsv: vi.fn()
  }
}));

describe('Mutation Module Integration & Component Tests', () => {
  const mockWarehouseList = [
    { id: 1, code: 'GDF', name: 'Gudang Utama' },
    { id: 2, code: 'AP-RJ', name: 'Apotek Rawat Jalan' },
    { id: 3, code: 'AP-IGD', name: 'Apotek IGD' }
  ];

  const mockSummaryData = {
    totalTransactions: '12 Transaksi',
    totalCount: 12,
    estimatedValue: 'Rp15.500.000',
    topSource: 'Gudang Utama',
    topSourceCount: 8,
    topSourceSubtitle: '8 mutasi keluar (66.7% volume)',
    topTarget: 'Apotek Rawat Jalan',
    topTargetCount: 5,
    topTargetSubtitle: '5 mutasi masuk diproses',
    integrity: '100%',
    integrityBadge: 'Tersinkronisasi',
    integritySubtitle: 'Audit log sinkron tanpa selisih ledger'
  };

  const mockMutationList = [
    {
      id: 1,
      mutationNumber: 'MUT-202610-0001',
      mutationDate: '2026-10-29T09:30:00+07:00',
      dateDisplay: '29 Okt 2026, 09:30 WIB',
      sourceWarehouseId: 1,
      sourceWarehouse: 'Gudang Utama',
      targetWarehouseId: 2,
      targetWarehouse: 'Apotek Rawat Jalan',
      itemCount: 2,
      totalQuantity: 80,
      unit: 'Tablet',
      notes: 'Restock rutin pagi',
      status: 'validated',
      createdBy: 'Apt. Novita Anggraini',
      createdInitials: 'NA',
      items: [
        { id: 1, medicineId: 10, medicineName: 'Paracetamol 500 mg', quantity: 50, unit: 'Tablet' },
        { id: 2, medicineId: 11, medicineName: 'Amoxicillin 500 mg', quantity: 30, unit: 'Kapsul' }
      ]
    }
  ];

  const createMockMutations = (count) => {
    return Array.from({ length: count }, (_, i) => ({
      id: i + 1,
      mutationNumber: `MUT-202610-${String(i + 1).padStart(4, '0')}`,
      mutationDate: '2026-10-29T09:30:00+07:00',
      dateDisplay: '29 Okt 2026, 09:30 WIB',
      sourceWarehouseId: 1,
      sourceWarehouse: 'Gudang Utama',
      targetWarehouseId: 2,
      targetWarehouse: 'Apotek Rawat Jalan',
      itemCount: 1,
      totalQuantity: 10 * (i + 1),
      unit: 'Tablet',
      notes: `Mutasi ke-${i + 1}`,
      status: 'validated',
      createdBy: 'Apt. Novita Anggraini',
      createdInitials: 'NA',
      items: [
        { id: i + 1, medicineId: 10, medicineName: 'Paracetamol 500 mg', quantity: 10, unit: 'Tablet' }
      ]
    }));
  };

  beforeEach(() => {
    vi.clearAllMocks();
    mutationsService.getWarehousesLookup.mockResolvedValue({ data: mockWarehouseList });
    mutationsService.getSummary.mockResolvedValue(mockSummaryData);
    mutationsService.getMutations.mockResolvedValue({
      data: mockMutationList,
      meta: {
        current_page: 1,
        per_page: 10,
        last_page: 1,
        total: 1,
        from: 1,
        to: 1
      }
    });
  });

  describe('useMutations Composable', () => {
    it('initializes and loads data via API without mock fallback', async () => {
      const composable = useMutations();
      expect(composable.mutations.value).toEqual([]);

      await composable.fetchInitialData();

      expect(mutationsService.getWarehousesLookup).toHaveBeenCalledTimes(1);
      expect(mutationsService.getSummary).toHaveBeenCalledTimes(1);
      expect(mutationsService.getMutations).toHaveBeenCalledTimes(1);

      expect(composable.mutations.value.length).toBe(1);
      expect(composable.warehouses.value.length).toBe(3);
      expect(composable.summary.value.totalTransactions).toBe('12 Transaksi');
    });

    it('filters mutations and sends query to backend', async () => {
      const composable = useMutations();

      composable.filters.keyword = 'Paracetamol';
      composable.filters.sourceWarehouseId = 1;
      composable.applyFilters();

      await flushPromises();

      expect(mutationsService.getMutations).toHaveBeenCalledWith(
        expect.objectContaining({
          search: 'Paracetamol',
          source_warehouse_id: 1,
          page: 1
        })
      );
    });

    it('handles pagination setPage and setPerPage', async () => {
      const composable = useMutations();
      composable.pagination.last_page = 3;

      composable.setPage(2);
      expect(mutationsService.getMutations).toHaveBeenCalledWith(
        expect.objectContaining({ page: 2 })
      );

      composable.setPerPage(25);
      expect(mutationsService.getMutations).toHaveBeenCalledWith(
        expect.objectContaining({ page: 1, per_page: 25 })
      );
    });

    it('submits new mutation to backend and refreshes list', async () => {
      const composable = useMutations();
      mutationsService.createMutation.mockResolvedValueOnce({
        data: { id: 2, mutationNumber: 'MUT-202610-0002' }
      });

      const payload = {
        date: '2026-10-29 10:00',
        sourceWarehouseId: 1,
        targetWarehouseId: 2,
        notes: 'Buffer darurat',
        items: [{ medicineId: 10, quantity: 20 }]
      };

      const res = await composable.addMutation(payload);

      expect(res.success).toBe(true);
      expect(mutationsService.createMutation).toHaveBeenCalledWith({
        mutationDate: '2026-10-29 10:00',
        sourceWarehouseId: 1,
        targetWarehouseId: 2,
        notes: 'Buffer darurat',
        items: [{ medicineId: 10, quantity: 20 }]
      });
      // Verification that mutations and summary are refreshed
      expect(mutationsService.getMutations).toHaveBeenCalled();
      expect(mutationsService.getSummary).toHaveBeenCalled();
    });

    it('exports CSV by invoking service blob download', async () => {
      const composable = useMutations();
      const mockBlob = new Blob(['csv content'], { type: 'text/csv' });
      mutationsService.exportCsv.mockResolvedValueOnce(mockBlob);

      // Mock DOM URL and createElement
      window.URL.createObjectURL = vi.fn().mockReturnValue('blob:test');
      window.URL.revokeObjectURL = vi.fn();

      const success = await composable.exportMutationsCsv();
      expect(success).toBe(true);
      expect(mutationsService.exportCsv).toHaveBeenCalled();
    });

    it('fetches mutations and binds 3 or 9 records directly from API response meta without mock data', async () => {
      const composable = useMutations();

      // Case 1: API returns 3 records
      mutationsService.getMutations.mockResolvedValueOnce({
        data: createMockMutations(3),
        meta: { current_page: 1, per_page: 10, last_page: 1, total: 3, from: 1, to: 3 }
      });
      await composable.fetchMutations();
      expect(composable.mutations.value.length).toBe(3);
      expect(composable.paginationInfo.value.total).toBe(3);
      expect(composable.paginationInfo.value.from).toBe(1);
      expect(composable.paginationInfo.value.to).toBe(3);

      // Case 2: API returns 9 records
      mutationsService.getMutations.mockResolvedValueOnce({
        data: createMockMutations(9),
        meta: { current_page: 1, per_page: 10, last_page: 1, total: 9, from: 1, to: 9 }
      });
      await composable.fetchMutations();
      expect(composable.mutations.value.length).toBe(9);
      expect(composable.paginationInfo.value.total).toBe(9);
      expect(composable.paginationInfo.value.from).toBe(1);
      expect(composable.paginationInfo.value.to).toBe(9);
    });

    it('sets error and clears mutations when API fails without fallback to mock', async () => {
      const composable = useMutations();
      mutationsService.getMutations.mockRejectedValueOnce(new Error('Server Error 500'));

      await composable.fetchMutations();

      expect(composable.error.value).toBe('Server Error 500');
      expect(composable.mutations.value).toEqual([]);
    });
  });

  describe('MutationSummaryCards Component', () => {
    it('renders 4 summary cards correctly with database metrics', () => {
      const wrapper = mount(MutationSummaryCards, {
        props: { summary: mockSummaryData }
      });

      expect(wrapper.text()).toContain('TOTAL MUTASI BULAN INI');
      expect(wrapper.text()).toContain('12 Transaksi');
      expect(wrapper.text()).toContain('Rp15.500.000');
      expect(wrapper.text()).toContain('Gudang Utama');
      expect(wrapper.text()).toContain('Apotek Rawat Jalan');
      expect(wrapper.text()).toContain('100%');
      expect(wrapper.text()).toContain('Tersinkronisasi');
    });
  });

  describe('MutationFilters Component', () => {
    it('renders warehouse options with IDs and emits filter changes', async () => {
      const filters = {
        startDate: '',
        endDate: '',
        sourceWarehouseId: '',
        targetWarehouseId: '',
        keyword: ''
      };

      const wrapper = mount(MutationFilters, {
        props: {
          filters,
          warehouses: mockWarehouseList,
          loading: false
        }
      });

      const options = wrapper.findAll('#filter-source option');
      expect(options.length).toBe(4); // 1 placeholder + 3 warehouses
      expect(options[1].text()).toBe('Gudang Utama');
      expect(options[1].attributes('value')).toBe('1');

      await wrapper.find('form').trigger('submit.prevent');
      expect(wrapper.emitted('apply')).toBeTruthy();

      await wrapper.find('button.btn-cancel').trigger('click');
      expect(wrapper.emitted('reset')).toBeTruthy();
    });
  });

  describe('MutationTable Component', () => {
    it('renders exactly 6 columns with legacy headers and without removed columns', () => {
      const paginationInfo = {
        current_page: 1,
        per_page: 10,
        last_page: 1,
        total: 1,
        from: 1,
        to: 1
      };

      const wrapper = mount(MutationTable, {
        props: {
          items: mockMutationList,
          paginationInfo,
          loading: false
        }
      });

      // 1. Exactly 6 columns
      const ths = wrapper.findAll('thead th');
      expect(ths.length).toBe(6);

      // 2-7. Headers No, Tanggal, Gudang Asal, Gudang Tujuan, Catatan, Aksi
      const headerTexts = ths.map((th) => th.text());
      expect(headerTexts).toEqual(['No', 'Tanggal', 'Gudang Asal', 'Gudang Tujuan', 'Catatan', 'Aksi']);

      // 8-11. Removed columns must NOT appear in header
      const theadText = wrapper.find('thead').text();
      expect(theadText).not.toContain('Nomor Mutasi');
      expect(theadText).not.toContain('Jenis Item');
      expect(theadText).not.toContain('Jumlah Sediaan');
      expect(theadText).not.toContain('Total Kuantitas');
      expect(theadText).not.toContain('Dibuat Oleh');
      expect(theadText).not.toContain('Status');

      // Row cells: exactly 6 cells and removed column data not shown
      const firstRow = wrapper.find('tbody tr');
      const rowTds = firstRow.findAll('td');
      expect(rowTds.length).toBe(6);
      expect(firstRow.text()).toContain('Gudang Utama');
      expect(firstRow.text()).toContain('Apotek Rawat Jalan');
      expect(firstRow.text()).toContain('Restock rutin pagi');

      expect(firstRow.text()).not.toContain('MUT-202610-0001');
      expect(firstRow.text()).not.toContain('80 Tablet');
      expect(firstRow.text()).not.toContain('2 Sediaan');
      expect(firstRow.text()).not.toContain('Apt. Novita');
    });

    it('calculates row numbers correctly based on pagination', () => {
      const items = createMockMutations(2);
      // Page 2, per_page 10 -> items should be 11, 12
      const wrapper = mount(MutationTable, {
        props: {
          items,
          paginationInfo: {
            current_page: 2,
            per_page: 10,
            last_page: 2,
            total: 12,
            from: 11,
            to: 12
          },
          loading: false
        }
      });

      const rows = wrapper.findAll('tbody tr');
      expect(rows[0].find('.col-no').text()).toBe('11');
      expect(rows[1].find('.col-no').text()).toBe('12');
    });

    it('emits view event with full mutation data when Lihat Detail button is clicked', async () => {
      const wrapper = mount(MutationTable, {
        props: {
          items: mockMutationList,
          paginationInfo: { current_page: 1, per_page: 10, last_page: 1, total: 1, from: 1, to: 1 },
          loading: false
        }
      });

      const viewBtn = wrapper.find('button.btn-detail');
      expect(viewBtn.text()).toContain('Lihat Detail');
      expect(viewBtn.find('svg').exists()).toBe(true);

      await viewBtn.trigger('click');
      expect(wrapper.emitted('view')).toBeTruthy();
      expect(wrapper.emitted('view')[0][0]).toEqual(mockMutationList[0]);
    });

    it('renders MasterDataSkeletonRow when loading is true', () => {
      const wrapper = mount(MutationTable, {
        props: {
          items: [],
          paginationInfo: { current_page: 1, per_page: 10, last_page: 1, total: 0, from: 0, to: 0 },
          loading: true
        }
      });

      expect(wrapper.findComponent({ name: 'MasterDataSkeletonRow' }).exists()).toBe(true);
    });

    it('renders empty state with colspan 6 when items list is empty and not loading', () => {
      const wrapper = mount(MutationTable, {
        props: {
          items: [],
          paginationInfo: { current_page: 1, per_page: 10, last_page: 1, total: 0, from: 0, to: 0 },
          loading: false
        }
      });

      expect(wrapper.findComponent({ name: 'MasterDataSkeletonRow' }).exists()).toBe(false);
      expect(wrapper.find('.empty-cell').attributes('colspan')).toBe('6');
      expect(wrapper.text()).toContain('Tidak ada riwayat mutasi barang yang cocok');
    });

    it('renders exactly 3 rows when given 3 items and displays correct pagination text', () => {
      const threeItems = createMockMutations(3);
      const paginationInfo = {
        current_page: 1,
        per_page: 10,
        last_page: 1,
        total: 3,
        from: 1,
        to: 3
      };

      const wrapper = mount(MutationTable, {
        props: {
          items: threeItems,
          paginationInfo,
          loading: false
        }
      });

      const rows = wrapper.findAll('tbody tr:not(.empty-cell)');
      expect(rows.length).toBe(3);
      expect(wrapper.text()).toContain('Menampilkan 1–3 dari 3 transaksi');
    });

    it('renders exactly 9 rows when given 9 items and displays correct pagination text', () => {
      const nineItems = createMockMutations(9);
      const paginationInfo = {
        current_page: 1,
        per_page: 10,
        last_page: 1,
        total: 9,
        from: 1,
        to: 9
      };

      const wrapper = mount(MutationTable, {
        props: {
          items: nineItems,
          paginationInfo,
          loading: false
        }
      });

      const rows = wrapper.findAll('tbody tr:not(.empty-cell)');
      expect(rows.length).toBe(9);
      expect(wrapper.text()).toContain('Menampilkan 1–9 dari 9 transaksi');
    });
  });

  describe('MutationFormModal Component', () => {
    const mockStocks = [
      { id: 101, medicineId: 10, medicineCode: 'MED-001', medicineName: 'Paracetamol 500 mg', availableStock: 100, unit: 'Tablet', unitPrice: 800 },
      { id: 102, medicineId: 11, medicineCode: 'MED-002', medicineName: 'Amoxicillin 500 mg', availableStock: 0, unit: 'Kapsul', unitPrice: 1500 }
    ];

    it('has two distinct sections: Data Mutasi and List Sediaan, and info banner Perhatian', () => {
      const wrapper = mount(MutationFormModal, {
        props: {
          isOpen: true,
          warehouses: mockWarehouseList,
          isSubmitting: false
        },
        global: { stubs: { teleport: true } }
      });

      expect(wrapper.text()).toContain('Perhatian');
      expect(wrapper.text()).toContain('Data Mutasi');
      expect(wrapper.text()).toContain('List Sediaan');

      // Nomor Mutasi must NOT appear as input
      expect(wrapper.find('#form-mutation-num').exists()).toBe(false);

      // Data Mutasi has exactly 4 fields
      expect(wrapper.find('#form-mutation-date').exists()).toBe(true);
      expect(wrapper.find('#form-notes').exists()).toBe(true);
      expect(wrapper.find('#form-source-wh').exists()).toBe(true);
      expect(wrapper.find('#form-target-wh').exists()).toBe(true);
    });

    it('disables sediaan search before source warehouse is selected', () => {
      const wrapper = mount(MutationFormModal, {
        props: {
          isOpen: true,
          warehouses: mockWarehouseList,
          isSubmitting: false
        },
        global: { stubs: { teleport: true } }
      });

      const searchInput = wrapper.find('#search-sediaan');
      expect(searchInput.attributes('disabled')).toBeDefined();
    });

    it('disables target warehouse option if it matches source warehouse', async () => {
      const wrapper = mount(MutationFormModal, {
        props: {
          isOpen: true,
          warehouses: mockWarehouseList,
          isSubmitting: false
        },
        global: { stubs: { teleport: true } }
      });

      await wrapper.find('#form-source-wh').setValue(1);
      await wrapper.find('#form-source-wh').trigger('change');

      const targetOptions = wrapper.findAll('#form-target-wh option');
      const sameOption = targetOptions.find((opt) => opt.attributes('value') === '1');
      expect(sameOption.attributes('disabled')).toBeDefined();
    });

    it('searches and adds sediaan with stock > 0 and displays 5 columns', async () => {
      mutationsService.getWarehouseStocks.mockResolvedValue({ data: mockStocks });

      const wrapper = mount(MutationFormModal, {
        props: {
          isOpen: true,
          warehouses: mockWarehouseList,
          isSubmitting: false
        },
        global: { stubs: { teleport: true } }
      });

      await wrapper.find('#form-source-wh').setValue(1);
      await wrapper.find('#form-source-wh').trigger('change');
      await flushPromises();

      expect(mutationsService.getWarehouseStocks).toHaveBeenCalledWith({ warehouse_id: 1, all: 1 });

      const searchInput = wrapper.find('#search-sediaan');
      expect(searchInput.attributes('disabled')).toBeUndefined();

      // Search medicine
      await searchInput.setValue('Paracetamol');
      await searchInput.trigger('focus');

      // Result appears (only stock > 0)
      const results = wrapper.findAll('.dropdown-result-item');
      expect(results.length).toBe(1);
      expect(results[0].text()).toContain('Paracetamol 500 mg');

      // Click to add item
      await results[0].trigger('click');

      // Table now has columns: Nama, Satuan, Jumlah, Harga, Aksi
      const ths = wrapper.findAll('.sediaan-table th');
      expect(ths.map((th) => th.text())).toEqual(['Nama', 'Satuan', 'Jumlah', 'Harga', 'Aksi']);

      // No separate "Stok Tersedia" column
      expect(wrapper.find('.sediaan-table thead').text()).not.toContain('Stok Tersedia');

      const row = wrapper.find('.sediaan-table tbody tr');
      expect(row.find('.col-name').text()).toContain('Paracetamol 500 mg');
      expect(row.find('.col-unit').text()).toBe('Tablet');
      expect(row.find('.col-qty input').element.value).toBe('1');
      expect(row.find('.col-price').text()).toBe('Rp800');
    });

    it('prevents adding duplicate item and warns user without window.alert', async () => {
      mutationsService.getWarehouseStocks.mockResolvedValue({ data: mockStocks });
      const alertSpy = vi.spyOn(window, 'alert').mockImplementation(() => {});

      const wrapper = mount(MutationFormModal, {
        props: {
          isOpen: true,
          warehouses: mockWarehouseList,
          isSubmitting: false
        },
        global: { stubs: { teleport: true } }
      });

      await wrapper.find('#form-source-wh').setValue(1);
      await wrapper.find('#form-source-wh').trigger('change');
      await flushPromises();

      const searchInput = wrapper.find('#search-sediaan');
      await searchInput.setValue('Paracetamol');
      await wrapper.find('.dropdown-result-item').trigger('click');

      // Try adding again
      await searchInput.setValue('Paracetamol');
      await wrapper.find('.dropdown-result-item').trigger('click');

      expect(alertSpy).not.toHaveBeenCalled();
      expect(wrapper.text()).toContain('Sediaan tersebut sudah ditambahkan.');
      expect(wrapper.findAll('.sediaan-table tbody tr').length).toBe(1);

      alertSpy.mockRestore();
    });

    it('validates quantity cannot exceed available stock and recalculates total', async () => {
      mutationsService.getWarehouseStocks.mockResolvedValue({ data: mockStocks });

      const wrapper = mount(MutationFormModal, {
        props: {
          isOpen: true,
          warehouses: mockWarehouseList,
          isSubmitting: false
        },
        global: { stubs: { teleport: true } }
      });

      await wrapper.find('#form-source-wh').setValue(1);
      await wrapper.find('#form-source-wh').trigger('change');
      await flushPromises();

      await wrapper.find('#search-sediaan').setValue('Paracetamol');
      await wrapper.find('.dropdown-result-item').trigger('click');

      const qtyInput = wrapper.find('.qty-input');
      await qtyInput.setValue(10);

      // Price: 10 * 800 = 8.000
      expect(wrapper.find('tbody .col-price').text()).toBe('Rp8.000');
      expect(wrapper.find('.total-summary-row').text()).toContain('Rp8.000');

      // Exceed available stock (100)
      await qtyInput.setValue(150);
      expect(wrapper.text()).toContain('Jumlah melebihi stok yang tersedia. Stok saat ini: 100 Tablet.');
    });

    it('deletes item when delete trash button is clicked', async () => {
      mutationsService.getWarehouseStocks.mockResolvedValue({ data: mockStocks });

      const wrapper = mount(MutationFormModal, {
        props: {
          isOpen: true,
          warehouses: mockWarehouseList,
          isSubmitting: false
        },
        global: { stubs: { teleport: true } }
      });

      await wrapper.find('#form-source-wh').setValue(1);
      await wrapper.find('#form-source-wh').trigger('change');
      await flushPromises();

      await wrapper.find('#search-sediaan').setValue('Paracetamol');
      await wrapper.find('.dropdown-result-item').trigger('click');
      expect(wrapper.findAll('.sediaan-table tbody tr').length).toBe(1);

      await wrapper.find('.btn-delete-row').trigger('click');
      expect(wrapper.text()).toContain('Belum ada sediaan yang ditambahkan.');
    });

    it('prompts confirmation when changing source warehouse with existing items', async () => {
      mutationsService.getWarehouseStocks.mockResolvedValue({ data: mockStocks });

      const wrapper = mount(MutationFormModal, {
        props: {
          isOpen: true,
          warehouses: mockWarehouseList,
          isSubmitting: false
        },
        global: { stubs: { teleport: true } }
      });

      await wrapper.find('#form-source-wh').setValue(1);
      await wrapper.find('#form-source-wh').trigger('change');
      await flushPromises();

      await wrapper.find('#search-sediaan').setValue('Paracetamol');
      await wrapper.find('.dropdown-result-item').trigger('click');

      // Change source warehouse to 2
      await wrapper.find('#form-source-wh').setValue(2);
      await wrapper.find('#form-source-wh').trigger('change');

      // Confirmation overlay appears
      expect(wrapper.find('.confirm-dialog-overlay').exists()).toBe(true);
      expect(wrapper.text()).toContain('Ganti Gudang Asal?');

      // Cancel keeps item
      await wrapper.find('.confirm-dialog-overlay button.btn-secondary').trigger('click');
      expect(wrapper.find('.confirm-dialog-overlay').exists()).toBe(false);
      expect(wrapper.findAll('.sediaan-table tbody tr').length).toBe(1);

      // Trigger change again and confirm
      await wrapper.find('#form-source-wh').setValue(2);
      await wrapper.find('#form-source-wh').trigger('change');
      await wrapper.find('button.btn-danger-action').trigger('click');
      await flushPromises();

      // List is emptied
      expect(wrapper.text()).toContain('Belum ada sediaan yang ditambahkan.');
    });

    it('submits valid mutation payload with warehouse and medicine IDs', async () => {
      mutationsService.getWarehouseStocks.mockResolvedValue({ data: mockStocks });

      const wrapper = mount(MutationFormModal, {
        props: {
          isOpen: true,
          warehouses: mockWarehouseList,
          isSubmitting: false
        },
        global: { stubs: { teleport: true } }
      });

      await wrapper.find('#form-source-wh').setValue(1);
      await wrapper.find('#form-source-wh').trigger('change');
      await wrapper.find('#form-target-wh').setValue(2);
      await wrapper.find('#form-notes').setValue('Catatan tes');
      await flushPromises();

      await wrapper.find('#search-sediaan').setValue('Paracetamol');
      await wrapper.find('.dropdown-result-item').trigger('click');

      const qtyInput = wrapper.find('.qty-input');
      await qtyInput.setValue(10);

      await wrapper.find('form').trigger('submit.prevent');

      expect(wrapper.emitted('submit')).toBeTruthy();
      expect(wrapper.emitted('submit')[0][0]).toEqual({
        date: expect.any(String),
        mutationDate: expect.any(String),
        sourceWarehouseId: 1,
        targetWarehouseId: 2,
        notes: 'Catatan tes',
        items: [
          {
            warehouseStockId: 101,
            medicineId: 10,
            quantity: 10
          }
        ]
      });
    });
  });

  describe('MutationDetailModal Component', () => {
    it('renders read-only Data Mutasi and List Sediaan with Nama, Satuan, Jumlah, Harga, and Total', () => {
      const detailMutation = {
        id: 1,
        mutationNumber: 'MUT-202610-0001',
        dateDisplay: '29 Okt 2026, 09:30 WIB',
        sourceWarehouse: 'Gudang Utama',
        targetWarehouse: 'Apotek Rawat Jalan',
        notes: 'Restock rutin pagi',
        totalValue: 80000,
        items: [
          {
            id: 1,
            medicineId: 10,
            medicineName: 'Paracetamol 500 mg',
            medicineCode: 'DRG000031917',
            quantity: 10,
            unit: 'Tablet',
            unitPrice: 800,
            subtotal: 8000
          }
        ]
      };

      const wrapper = mount(MutationDetailModal, {
        props: {
          isOpen: true,
          mutation: detailMutation
        },
        global: {
          stubs: { teleport: true }
        }
      });

      expect(wrapper.text()).toContain('MUT-202610-0001');
      expect(wrapper.text()).toContain('Data Mutasi');
      expect(wrapper.text()).toContain('List Sediaan');
      expect(wrapper.text()).toContain('Gudang Utama');
      expect(wrapper.text()).toContain('Apotek Rawat Jalan');
      expect(wrapper.text()).toContain('Restock rutin pagi');

      const ths = wrapper.findAll('.detail-table th');
      expect(ths.map((th) => th.text())).toEqual(['Nama', 'Satuan', 'Jumlah', 'Harga']);

      // Aksi must not be in detail table
      expect(wrapper.find('.detail-table thead').text()).not.toContain('Aksi');

      expect(wrapper.text()).toContain('Paracetamol 500 mg');
      expect(wrapper.text()).toContain('Tablet');
      expect(wrapper.text()).toContain('10');
      expect(wrapper.text()).toContain('Rp8.000');
      expect(wrapper.text()).toContain('TOTAL NILAI SEDIAAN');
    });
  });

  describe('MutationView Page Component', () => {
    it('fetches initial data on mount and renders full layout', async () => {
      const wrapper = mount(MutationView);
      await flushPromises();

      expect(mutationsService.getWarehousesLookup).toHaveBeenCalled();
      expect(mutationsService.getSummary).toHaveBeenCalled();
      expect(mutationsService.getMutations).toHaveBeenCalled();

      expect(wrapper.find('.page-title').text()).toBe('Mutasi Barang');
      expect(wrapper.findComponent(MutationSummaryCards).exists()).toBe(true);
      expect(wrapper.findComponent(MutationFilters).exists()).toBe(true);
      expect(wrapper.findComponent(MutationAuditBanner).exists()).toBe(true);
      expect(wrapper.findComponent(MutationTable).exists()).toBe(true);
    });

    it('handles form submission error without showing success toast', async () => {
      mutationsService.createMutation.mockRejectedValueOnce({
        response: {
          status: 422,
          data: {
            message: 'Stok tidak mencukupi untuk Paracetamol',
            errors: { 'items.0.quantity': ['Stok tidak mencukupi.'] }
          }
        }
      });

      const wrapper = mount(MutationView);
      await flushPromises();

      // Open modal and submit invalid payload
      wrapper.vm.openAddModal();
      await wrapper.vm.handleFormSubmit({
        date: '2026-10-29 10:00',
        sourceWarehouseId: 1,
        targetWarehouseId: 2,
        items: [{ medicineId: 10, quantity: 999 }]
      });

      await flushPromises();

      expect(wrapper.vm.toast.show).toBe(true);
      expect(wrapper.vm.toast.type).toBe('error');
      expect(wrapper.vm.toast.message).toContain('Stok tidak mencukupi');
    });
  });
});
