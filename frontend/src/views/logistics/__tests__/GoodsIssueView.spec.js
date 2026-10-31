import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount } from '@vue/test-utils';
import GoodsIssueView from '../GoodsIssueView.vue';
import GoodsIssueTable from '../components/GoodsIssueTable.vue';
import GoodsIssueFormModal from '../components/GoodsIssueFormModal.vue';
import GoodsIssueDeleteDialog from '../components/GoodsIssueDeleteDialog.vue';
import GoodsIssueStockSelect from '../components/GoodsIssueStockSelect.vue';
import GoodsIssueDetailView from '../GoodsIssueDetailView.vue';
import { useGoodsIssues } from '@/composables/useGoodsIssues';
import goodsIssuesService from '@/services/logistics/goodsIssues.service';

vi.mock('@/services/logistics/goodsIssues.service', () => ({
  default: {
    getGoodsIssues: vi.fn(),
    getGoodsIssue: vi.fn(),
    getSummary: vi.fn(),
    getFormOptions: vi.fn(),
    getWarehouseStocks: vi.fn(),
    getAvailableStocks: vi.fn(),
    createGoodsIssue: vi.fn(),
    updateGoodsIssue: vi.fn(),
    finalizeGoodsIssue: vi.fn(),
    cancelGoodsIssue: vi.fn(),
    deleteGoodsIssue: vi.fn(),
    addItem: vi.fn(),
    updateItem: vi.fn(),
    deleteItem: vi.fn(),
    exportCsv: vi.fn()
  }
}));

const mockPush = vi.fn();
vi.mock('vue-router', () => ({
  useRouter: () => ({
    push: mockPush
  }),
  useRoute: () => ({
    params: { id: '1' }
  }),
  RouterLink: {
    template: '<a><slot /></a>'
  }
}));

describe('Goods Issue (Pengeluaran Barang) Module Tests', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    goodsIssuesService.getGoodsIssues.mockResolvedValue({
      data: [
        {
          id: 1,
          issue_date: '2022-12-13',
          formattedDate: '13-12-2022',
          officer_id: 1,
          officerName: 'Person 45655962',
          purpose: 'Kebutuhan Internal',
          notes: ''
        },
        {
          id: 2,
          issue_date: '2022-12-13',
          formattedDate: '13-12-2022',
          officer_id: 2,
          officerName: 'Person 13552',
          purpose: 'ya',
          notes: 'ya'
        }
      ],
      meta: {
        current_page: 1,
        per_page: 10,
        last_page: 1,
        total: 2,
        from: 1,
        to: 2
      }
    });

    goodsIssuesService.getFormOptions.mockResolvedValue({
      data: {
        warehouses: [
          { id: 1, name: 'Gudang Utama' },
          { id: 2, name: 'Apotek Rajal' }
        ],
        officers: [
          { id: 1, name: 'Person 45655962' },
          { id: 2, name: 'Person 13552' }
        ]
      }
    });

    goodsIssuesService.getWarehouseStocks.mockResolvedValue({
      data: [
        { id: 1, stock_id: 1, medicineId: 10, item_id: 10, name: 'Amoxan Drop Botol 15 ml', item_name: 'Amoxan Drop Botol 15 ml', availableStock: 100, available_stock: 100, price: 2252, selling_price: 2252, unit: 'Botol', unit_name: 'Botol', batch_number: 'B24001', item_code: 'AMX001' }
      ]
    });

    goodsIssuesService.getAvailableStocks.mockResolvedValue({
      data: [
        {
          id: 81,
          stock_id: 81,
          item_id: 17,
          medicineId: 17,
          item_code: 'DRG000047586',
          item_name: 'Vitamin C 50 mg (asam acsorbat)',
          name: 'Vitamin C 50 mg (asam acsorbat)',
          unit_name: 'Tablet',
          unit: 'Tablet',
          batch_number: '5524958',
          expiry_date: '2023-12-12',
          available_stock: 10,
          availableStock: 10,
          selling_price: 500,
          price: 500,
          warehouse_id: 3,
          warehouse_name: 'Apotek Ranap'
        }
      ]
    });

    goodsIssuesService.getGoodsIssue.mockResolvedValue({
      data: {
        id: 1,
        issue_date: '2022-12-13',
        formattedDate: '13-12-2022',
        officerName: 'Person 45655962',
        notes: '',
        purpose: '-',
        items: [
          {
            id: 101,
            medicineName: 'Amoxan Drop',
            medicineUnit: 'Botol 15 ml',
            unitPrice: 2252,
            quantity: 1,
            subtotal: 2252,
            warehouseName: 'Gudang Utama',
            warehouseId: 1
          }
        ]
      }
    });
  });

  describe('useGoodsIssues Composable', () => {
    let composable;

    beforeEach(() => {
      composable = useGoodsIssues();
    });

    it('initializes with default filters and pagination', () => {
      expect(composable.pagination.current_page).toBe(1);
      expect(composable.pagination.per_page).toBe(10);
    });

    it('fetches goods issues from service with correct parameters', async () => {
      await composable.fetchIssues();
      expect(goodsIssuesService.getGoodsIssues).toHaveBeenCalled();
      expect(composable.issues.value.length).toBe(2);
      expect(composable.issues.value[0].officerName).toBe('Person 45655962');
    });

    it('creates, updates, and deletes goods issues correctly via service', async () => {
      goodsIssuesService.createGoodsIssue.mockResolvedValueOnce({
        data: { id: 10, notes: 'Pengeluaran baru' }
      });
      goodsIssuesService.updateGoodsIssue.mockResolvedValueOnce({
        data: { id: 10, notes: 'Updated notes' }
      });
      goodsIssuesService.deleteGoodsIssue.mockResolvedValueOnce({ success: true });

      const resCreate = await composable.createIssue({ notes: 'Pengeluaran baru' });
      expect(resCreate.success).toBe(true);
      expect(goodsIssuesService.createGoodsIssue).toHaveBeenCalled();

      const resUpdate = await composable.updateIssue(10, { notes: 'Updated notes' });
      expect(resUpdate.success).toBe(true);
      expect(goodsIssuesService.updateGoodsIssue).toHaveBeenCalled();

      const resDel = await composable.deleteIssue(10);
      expect(resDel.success).toBe(true);
      expect(goodsIssuesService.deleteGoodsIssue).toHaveBeenCalledWith(10);
    });

    it('manages issue items (add, update, delete)', async () => {
      goodsIssuesService.addItem.mockResolvedValueOnce({ data: { id: 50, quantity: 2 } });
      goodsIssuesService.updateItem.mockResolvedValueOnce({ data: { id: 50, quantity: 5 } });
      goodsIssuesService.deleteItem.mockResolvedValueOnce({ success: true });

      const resAdd = await composable.addItem(1, { medicine_id: 10, quantity: 2, warehouse_id: 1 });
      expect(resAdd.success).toBe(true);
      expect(goodsIssuesService.addItem).toHaveBeenCalled();

      const resUpd = await composable.updateItem(1, 50, { quantity: 5 });
      expect(resUpd.success).toBe(true);
      expect(goodsIssuesService.updateItem).toHaveBeenCalled();

      const resDel = await composable.deleteItem(1, 50);
      expect(resDel.success).toBe(true);
      expect(goodsIssuesService.deleteItem).toHaveBeenCalled();
    });
  });

  describe('GoodsIssueTable Component', () => {
    const itemsMock = [
      {
        id: 1,
        formattedDate: '13-12-2022',
        officerName: 'Person 45655962',
        notes: '',
        purpose: 'Kebutuhan Internal'
      },
      {
        id: 2,
        formattedDate: '13-12-2022',
        officerName: 'Person 13552',
        notes: 'ya',
        purpose: 'ya'
      },
      {
        id: 3,
        formattedDate: '30-08-2022',
        officerName: 'Person 49460928',
        notes: 'test ks',
        purpose: 'test ks'
      }
    ];

    const paginationInfoMock = {
      current_page: 1,
      per_page: 10,
      last_page: 1,
      total: 3,
      from: 1,
      to: 3
    };

    it('renders exactly 5 columns matching the legacy structure: No., Tanggal, Petugas, Keperluan, Aksi', () => {
      const wrapper = mount(GoodsIssueTable, {
        props: {
          items: itemsMock,
          paginationInfo: paginationInfoMock,
          loading: false,
          search: ''
        }
      });

      const ths = wrapper.findAll('thead th');
      expect(ths.length).toBe(5);
      expect(ths[0].text()).toContain('No.');
      expect(ths[1].text()).toContain('Tanggal');
      expect(ths[2].text()).toContain('Petugas');
      expect(ths[3].text()).toContain('Keperluan');
      expect(ths[4].text()).toContain('Aksi');
    });

    it('renders row data with pagination row numbers and correct formatting', () => {
      const wrapper = mount(GoodsIssueTable, {
        props: {
          items: itemsMock,
          paginationInfo: paginationInfoMock,
          loading: false,
          search: ''
        }
      });

      const rows = wrapper.findAll('tbody tr');
      expect(rows.length).toBe(3);

      // First row
      expect(rows[0].text()).toContain('1');
      expect(rows[0].text()).toContain('13-12-2022');
      expect(rows[0].text()).toContain('Person 45655962');
      expect(rows[0].text()).toContain('-'); // Empty notes/purpose should fallback to '-'

      // Second row
      expect(rows[1].text()).toContain('2');
      expect(rows[1].text()).toContain('Person 13552');
      expect(rows[1].text()).toContain('ya');
    });

    it('renders 3 action buttons (Detail, Edit, Hapus) and emits row actions', async () => {
      const wrapper = mount(GoodsIssueTable, {
        props: {
          items: itemsMock,
          paginationInfo: paginationInfoMock,
          loading: false,
          search: ''
        }
      });

      const firstRow = wrapper.findAll('tbody tr')[0];
      const buttons = firstRow.findAll('.btn-action-icon');
      expect(buttons.length).toBe(3);

      const btnDetail = buttons[0];
      const btnEdit = buttons[1];
      const btnDelete = buttons[2];

      await btnDetail.trigger('click');
      expect(wrapper.emitted('view')).toBeTruthy();
      expect(wrapper.emitted('view')[0][0]).toEqual(itemsMock[0]);

      await btnEdit.trigger('click');
      expect(wrapper.emitted('edit')).toBeTruthy();
      expect(wrapper.emitted('edit')[0][0]).toEqual(itemsMock[0]);

      await btnDelete.trigger('click');
      expect(wrapper.emitted('delete')).toBeTruthy();
      expect(wrapper.emitted('delete')[0][0]).toEqual(itemsMock[0]);
    });

    it('renders records-per-page select and search input', async () => {
      const wrapper = mount(GoodsIssueTable, {
        props: {
          items: itemsMock,
          paginationInfo: paginationInfoMock,
          loading: false,
          search: ''
        }
      });

      const select = wrapper.find('select.records-select');
      expect(select.exists()).toBe(true);
      await select.setValue(25);
      expect(wrapper.emitted('per-page-change')).toBeTruthy();
      expect(wrapper.emitted('per-page-change')[0][0]).toBe(25);

      const searchInput = wrapper.find('input.search-input');
      expect(searchInput.exists()).toBe(true);
    });

    it('renders empty state when items is empty', () => {
      const wrapper = mount(GoodsIssueTable, {
        props: {
          items: [],
          paginationInfo: { current_page: 1, per_page: 10, last_page: 1, total: 0, from: 0, to: 0 },
          loading: false,
          search: ''
        }
      });

      expect(wrapper.text()).toContain('Data pengeluaran barang tidak ditemukan');
    });
  });

  describe('GoodsIssueFormModal Component', () => {
    it('renders FORM INPUT modal with only Tanggal, Petugas, and Keperluan fields', async () => {
      const wrapper = mount(GoodsIssueFormModal, {
        props: {
          isOpen: true,
          issueToEdit: null,
          officers: [
            { id: 1, name: 'Person 45655962' },
            { id: 2, name: 'Person 13552' }
          ],
          isSubmitting: false
        },
        global: {
          stubs: {
            teleport: true
          }
        }
      });

      expect(wrapper.text()).toContain('Tanggal');
      expect(wrapper.text()).toContain('Petugas');
      expect(wrapper.text()).toContain('Keperluan');

      // Check fields
      expect(wrapper.find('input[type="date"]').exists()).toBe(true);
      expect(wrapper.find('select.form-select').exists()).toBe(true);
      expect(wrapper.find('textarea.form-textarea').exists()).toBe(true);

      // Does not contain items or warehouse fields
      expect(wrapper.find('input[type="number"]').exists()).toBe(false);

      // Buttons
      expect(wrapper.find('button.btn-cancel').exists()).toBe(true);
      expect(wrapper.find('button.btn-reset').exists()).toBe(true);
      expect(wrapper.find('button.btn-primary').exists()).toBe(true);

      // Submit form
      await wrapper.find('textarea.form-textarea').setValue('Baksos');
      await wrapper.find('form').trigger('submit.prevent');
      expect(wrapper.emitted('submit')).toBeTruthy();
      expect(wrapper.emitted('submit')[0][0].notes).toBe('Baksos');
    });
  });

  describe('GoodsIssueDeleteDialog Component', () => {
    it('renders confirmation dialog and emits confirm', async () => {
      const wrapper = mount(GoodsIssueDeleteDialog, {
        props: {
          isOpen: true,
          issue: { id: 1, formattedDate: '13-12-2022', officerName: 'Person 45655962', notes: 'Baksos' },
          isSubmitting: false
        },
        global: {
          stubs: {
            teleport: true
          }
        }
      });

      expect(wrapper.text()).toContain('Konfirmasi Hapus Data');
      expect(wrapper.text()).toContain('Person 45655962');
      await wrapper.find('button.btn-danger').trigger('click');
      expect(wrapper.emitted('confirm')).toBeTruthy();
    });
  });

  describe('GoodsIssueView Integration', () => {
    it('mounts GoodsIssueView with simplified layout: no summary cards, no non-sales filters, only Tambah Pengeluaran button', () => {
      const wrapper = mount(GoodsIssueView, {
        global: {
          stubs: {
            teleport: true
          }
        }
      });

      // Breadcrumb, title, badge
      expect(wrapper.text()).toContain('Logistik');
      expect(wrapper.text()).toContain('Pengeluaran Barang');
      expect(wrapper.text()).toContain('SIMRS-LOG');

      // No summary cards or filter cards
      expect(wrapper.text()).not.toContain('PENGELUARAN HARI INI');
      expect(wrapper.text()).not.toContain('Filter Pengeluaran Nonpenjualan');
      expect(wrapper.text()).not.toContain('Export Excel');
      expect(wrapper.text()).not.toContain('Cetak Rekap Bulanan');

      // Only single Tambah Pengeluaran button
      const headerActions = wrapper.find('.header-actions');
      const actionButtons = headerActions.findAll('button');
      expect(actionButtons.length).toBe(1);
      expect(actionButtons[0].text()).toContain('Tambah Pengeluaran');
      expect(actionButtons[0].text()).not.toContain('+ +');
    });

    it('opens add modal on clicking Tambah Pengeluaran', async () => {
      const wrapper = mount(GoodsIssueView, {
        global: {
          stubs: {
            teleport: true
          }
        }
      });

      const addBtn = wrapper.find('.header-actions button.btn-primary');
      await addBtn.trigger('click');
      expect(wrapper.findComponent(GoodsIssueFormModal).props('isOpen')).toBe(true);
    });

    it('navigates to detail page on view emit from table', async () => {
      const wrapper = mount(GoodsIssueView, {
        global: {
          stubs: {
            teleport: true
          }
        }
      });

      const table = wrapper.findComponent(GoodsIssueTable);
      await table.vm.$emit('view', { id: 5 });
      expect(mockPush).toHaveBeenCalledWith({
        name: 'logistics.goods-issues.detail',
        params: { id: 5 }
      });
    });
  });

  describe('GoodsIssueStockSelect Component', () => {
    it('is disabled and shows "Pilih gudang terlebih dahulu" when warehouseId is not set', () => {
      const wrapper = mount(GoodsIssueStockSelect, {
        props: {
          warehouseId: '',
          modelValue: ''
        }
      });

      expect(wrapper.text()).toContain('Pilih gudang terlebih dahulu');
      const box = wrapper.find('.select-box');
      expect(box.classes()).toContain('is-disabled');
      expect(wrapper.find('.dropdown-popover').exists()).toBe(false);
    });

    it('enables and shows search placeholder when warehouseId is provided', () => {
      const wrapper = mount(GoodsIssueStockSelect, {
        props: {
          warehouseId: 3,
          warehouseName: 'Apotek Ranap',
          modelValue: ''
        }
      });

      expect(wrapper.text()).toContain('Cari nama atau kode sediaan...');
      const box = wrapper.find('.select-box');
      expect(box.classes()).not.toContain('is-disabled');
      // When input is empty, dropdown remains closed and no request is sent
      expect(wrapper.find('.dropdown-popover').exists()).toBe(false);
      expect(goodsIssuesService.getAvailableStocks).not.toHaveBeenCalled();
    });

    it('does not send request and keeps dropdown closed when input is empty on click/focus', async () => {
      const wrapper = mount(GoodsIssueStockSelect, {
        props: {
          warehouseId: 3,
          warehouseName: 'Apotek Ranap',
          modelValue: ''
        }
      });

      await wrapper.find('.main-search-input').trigger('focus');
      await wrapper.find('.select-box').trigger('click');
      await wrapper.vm.$nextTick();

      expect(wrapper.find('.dropdown-popover').exists()).toBe(false);
      expect(goodsIssuesService.getAvailableStocks).not.toHaveBeenCalled();
    });

    it('shows hint "Ketik minimal 2 karakter" and does not send request when query has only 1 character', async () => {
      const wrapper = mount(GoodsIssueStockSelect, {
        props: {
          warehouseId: 3,
          warehouseName: 'Apotek Ranap',
          modelValue: ''
        }
      });

      const input = wrapper.find('.main-search-input');
      await input.setValue('V');
      await input.trigger('input');
      await wrapper.vm.$nextTick();

      expect(wrapper.find('.dropdown-popover').exists()).toBe(true);
      expect(wrapper.text()).toContain('Ketik minimal 2 karakter');
      expect(goodsIssuesService.getAvailableStocks).not.toHaveBeenCalled();
    });

    it('sends request and displays matching results when query is at least 2 characters', async () => {
      const wrapper = mount(GoodsIssueStockSelect, {
        props: {
          warehouseId: 3,
          warehouseName: 'Apotek Ranap',
          issueDate: '2026-10-30',
          modelValue: ''
        }
      });

      const input = wrapper.find('.main-search-input');
      await input.setValue('Vita');
      await input.trigger('input');

      // Wait debounce 350ms
      await new Promise((r) => setTimeout(r, 400));
      await wrapper.vm.$nextTick();

      expect(goodsIssuesService.getAvailableStocks).toHaveBeenCalledWith(
        3,
        expect.objectContaining({ search: 'Vita', issue_date: '2026-10-30' }),
        expect.anything()
      );

      expect(wrapper.find('.dropdown-popover').exists()).toBe(true);
      expect(wrapper.text()).toContain('Vitamin C 50 mg (asam acsorbat)');
      expect(wrapper.text()).toContain('10 Tablet');
      expect(wrapper.text()).toContain('Batch: 5524958');

      // Click option to select
      await wrapper.find('.option-item').trigger('click');
      expect(wrapper.emitted('update:modelValue')).toBeTruthy();
      expect(wrapper.emitted('update:modelValue')[0][0]).toBe(81);
      expect(wrapper.emitted('select')).toBeTruthy();
      expect(wrapper.emitted('select')[0][0].stock_id).toBe(81);
      expect(wrapper.emitted('select')[0][0].item_name).toBe('Vitamin C 50 mg (asam acsorbat)');

      // Dropdown closes after selection
      expect(wrapper.find('.dropdown-popover').exists()).toBe(false);
    });

    it('cancels previous selection and clears selectedStockId when user types again after choosing an item', async () => {
      const wrapper = mount(GoodsIssueStockSelect, {
        props: {
          warehouseId: 3,
          warehouseName: 'Apotek Ranap',
          modelValue: 81
        }
      });

      // Initially selected item
      wrapper.vm.selectedStockItem = {
        stock_id: 81,
        item_name: 'Vitamin C 50 mg (asam acsorbat)'
      };
      await wrapper.vm.$nextTick();

      // User starts typing again to search something else
      const input = wrapper.find('.main-search-input');
      await input.setValue('Amox');
      await input.trigger('input');

      // Must emit clear and reset modelValue immediately
      expect(wrapper.emitted('update:modelValue')).toBeTruthy();
      expect(wrapper.emitted('update:modelValue')[0][0]).toBe('');
      expect(wrapper.emitted('clear')).toBeTruthy();
      expect(wrapper.vm.selectedStockItem).toBeNull();
    });

    it('clears selection, query, and search results completely when warehouse changes', async () => {
      const wrapper = mount(GoodsIssueStockSelect, {
        props: {
          warehouseId: 3,
          warehouseName: 'Apotek Ranap',
          modelValue: 81
        }
      });

      wrapper.vm.selectedStockItem = { stock_id: 81, item_name: 'Vitamin C 50 mg' };
      wrapper.vm.searchQuery = 'Vita';
      wrapper.vm.options = [{ stock_id: 81, item_name: 'Vitamin C 50 mg' }];

      // Switch warehouse
      await wrapper.setProps({ warehouseId: 2, warehouseName: 'Apotek Rajal' });
      await wrapper.vm.$nextTick();

      expect(wrapper.vm.selectedStockItem).toBeNull();
      expect(wrapper.vm.searchQuery).toBe('');
      expect(wrapper.vm.options).toEqual([]);
      expect(wrapper.emitted('update:modelValue')).toBeTruthy();
      expect(wrapper.find('.dropdown-popover').exists()).toBe(false);
    });

    it('shows empty search message when search returns no results', async () => {
      goodsIssuesService.getAvailableStocks.mockResolvedValue({ data: [] });
      goodsIssuesService.getWarehouseStocks.mockResolvedValue({ data: [] });

      const wrapper = mount(GoodsIssueStockSelect, {
        props: {
          warehouseId: 3,
          warehouseName: 'Apotek Ranap',
          modelValue: ''
        }
      });

      const input = wrapper.find('.main-search-input');
      await input.setValue('Paracetamol');

      await new Promise((r) => setTimeout(r, 400));
      await wrapper.vm.$nextTick();

      expect(wrapper.text()).toContain('Sediaan tidak ditemukan pada gudang ini');
    });

    it('shows error state and allows retry if request fails', async () => {
      goodsIssuesService.getAvailableStocks.mockRejectedValue(new Error('Network error'));
      goodsIssuesService.getWarehouseStocks.mockRejectedValue(new Error('Network error'));

      const wrapper = mount(GoodsIssueStockSelect, {
        props: {
          warehouseId: 3,
          warehouseName: 'Apotek Ranap',
          modelValue: ''
        }
      });

      const input = wrapper.find('.main-search-input');
      await input.setValue('Amox');
      await input.trigger('input');

      await new Promise((r) => setTimeout(r, 400));
      await wrapper.vm.$nextTick();

      expect(wrapper.text()).toContain('Gagal memuat sediaan. Silakan coba lagi.');
      expect(wrapper.find('button.btn-retry').exists()).toBe(true);
    });
  });

  describe('GoodsIssueDetailView Component', () => {
    it('renders INFO PENGELUARAN, FORM PENGELUARAN, and DETAIL LIST DATA PENGELUARAN cards', async () => {
      const wrapper = mount(GoodsIssueDetailView, {
        global: {
          stubs: {
            teleport: true,
            RouterLink: true
          }
        }
      });

      // Wait for data load
      await new Promise((r) => setTimeout(r, 20));

      expect(wrapper.text()).toContain('INFO PENGELUARAN');
      expect(wrapper.text()).toContain('FORM PENGELUARAN');
      expect(wrapper.text()).toContain('DETAIL LIST DATA PENGELUARAN');

      // Info Pengeluaran fields
      expect(wrapper.text()).toContain('Tanggal');
      expect(wrapper.text()).toContain('Petugas');
      expect(wrapper.text()).toContain('Keperluan');

      // Form Pengeluaran fields
      expect(wrapper.text()).toContain('Nama Sediaan');
      expect(wrapper.text()).toContain('Gudang');
      expect(wrapper.text()).toContain('Jumlah');

      // Detail table columns
      expect(wrapper.text()).toContain('Amoxan Drop');
      expect(wrapper.text()).toContain('Rp 2.252');
      expect(wrapper.text()).toContain('Total :');
    });

    it('disables Jumlah field and Simpan button before medicine is selected, and validates quantity limit', async () => {
      const wrapper = mount(GoodsIssueDetailView, {
        global: {
          stubs: {
            teleport: true,
            RouterLink: true
          }
        }
      });

      await new Promise((r) => setTimeout(r, 20));

      const qtyInput = wrapper.find('#detail-input-qty');
      const saveBtn = wrapper.find('.form-actions button.btn-primary');

      // Initially no medicine is selected: Jumlah and Simpan button must be disabled
      expect(qtyInput.attributes('disabled')).toBeDefined();
      expect(saveBtn.attributes('disabled')).toBeDefined();

      // Simulate stock selection
      const stockSelect = wrapper.findComponent(GoodsIssueStockSelect);
      stockSelect.vm.$emit('select', {
        id: 81,
        stock_id: 81,
        medicine_id: 17,
        item_name: 'Vitamin C 50 mg',
        available_stock: 10,
        unit_name: 'Tablet',
        selling_price: 500,
        batch_number: 'BATCH-81'
      });
      await wrapper.vm.$nextTick();

      // Now Jumlah input and Simpan button are enabled
      expect(qtyInput.attributes('disabled')).toBeUndefined();
      expect(saveBtn.attributes('disabled')).toBeUndefined();
      expect(wrapper.text()).toContain('Stok tersedia: 10 Tablet');

      // If quantity exceeds available stock (10)
      wrapper.vm.formDetail.quantity = 15;
      await wrapper.vm.$nextTick();

      expect(wrapper.text()).toContain('Jumlah melebihi stok (Maks. 10 Tablet)');
      expect(saveBtn.attributes('disabled')).toBeDefined();
    });
  });
});
