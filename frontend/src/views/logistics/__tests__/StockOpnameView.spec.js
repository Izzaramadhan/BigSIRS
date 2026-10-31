import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount, flushPromises } from '@vue/test-utils';
import StockOpnameView from '../StockOpnameView.vue';
import StockOpnameFilters from '../components/StockOpnameFilters.vue';
import StockOpnameTable from '../components/StockOpnameTable.vue';
import StockOpnameEditModal from '../components/StockOpnameEditModal.vue';
import stockOpnamesService from '@/services/logistics/stockOpnames.service';

vi.mock('@/services/logistics/stockOpnames.service', () => ({
  default: {
    getStockOpnames: vi.fn(),
    getStockOpname: vi.fn(),
    updateStockOpname: vi.fn(),
    getWarehousesLookup: vi.fn(),
    exportCsv: vi.fn()
  }
}));

describe('Stock Opname Direct Logistics Tests', () => {
  const mockStocksList = [
    {
      id: 1,
      warehouse_id: 1,
      warehouse: { id: 1, code: 'GDF', name: 'Gudang Utama' },
      medicine_id: 10,
      medicine: { id: 10, code: 'ALP-01', name: 'Alprazolam 0' },
      unit: { id: 1, name: 'Tablet' },
      batch_number: '0987654321',
      expiration_date: '2022-10-20',
      expiration_date_display: '20/10/2022',
      selling_price: 1000,
      formatted_selling_price: 'Rp1.000',
      stock: 500,
      is_expired: true,
      is_expiring_soon: false
    },
    {
      id: 2,
      warehouse_id: 1,
      warehouse: { id: 1, code: 'GDF', name: 'Gudang Utama' },
      medicine_id: 20,
      medicine: { id: 20, code: 'MLX-01', name: 'Meloxicam 7' },
      unit: { id: 1, name: 'Tablet' },
      batch_number: '00011122200',
      expiration_date: '2020-02-20',
      expiration_date_display: '20/02/2020',
      selling_price: 250,
      formatted_selling_price: 'Rp250',
      stock: 55,
      is_expired: true,
      is_expiring_soon: false
    }
  ];

  const mockWarehouses = [
    { id: 1, name: 'Gudang Utama' },
    { id: 2, name: 'Apotek Rajal' }
  ];

  beforeEach(() => {
    vi.clearAllMocks();
    stockOpnamesService.getWarehousesLookup.mockResolvedValue({ data: mockWarehouses });
    stockOpnamesService.getStockOpnames.mockResolvedValue({
      data: mockStocksList,
      meta: {
        current_page: 1,
        per_page: 10,
        last_page: 1,
        total: 2,
        from: 1,
        to: 2
      }
    });
  });

  it('renders header correctly with breadcrumbs and title without any add or start button', async () => {
    const wrapper = mount(StockOpnameView);
    await flushPromises();

    expect(wrapper.find('.page-title').text()).toBe('Data Stok Opname');
    expect(wrapper.find('.breadcrumbs').text()).toContain('Logistik');
    expect(wrapper.find('.breadcrumbs').text()).toContain('Stok Opname');
    expect(wrapper.text()).toContain('Kelola dan sesuaikan stok sediaan pada setiap gudang logistik.');

    // Assert NO add or start session button exists
    expect(wrapper.find('.btn-add').exists()).toBe(false);
    expect(wrapper.find('.btn-start-session').exists()).toBe(false);
    expect(wrapper.text()).not.toContain('Mulai Stok Opname');
    expect(wrapper.text()).not.toContain('Tambah Sesi');
  });

  it('fetches real warehouse stocks and warehouses on mount', async () => {
    mount(StockOpnameView);
    await flushPromises();

    expect(stockOpnamesService.getWarehousesLookup).toHaveBeenCalledTimes(1);
    expect(stockOpnamesService.getStockOpnames).toHaveBeenCalledWith({
      page: 1,
      per_page: 10,
      hide_zero_stock: 1
    });
  });

  it('table has exactly the 9 required columns in the exact order', async () => {
    const wrapper = mount(StockOpnameTable, {
      props: {
        items: mockStocksList,
        paginationInfo: {
          current_page: 1,
          per_page: 10,
          last_page: 1,
          total: 2,
          from: 1,
          to: 2
        },
        loading: false,
        hasActiveFilters: false
      }
    });

    const headers = wrapper.findAll('thead th').map((th) => th.text().trim());
    expect(headers).toEqual([
      'No',
      'Gudang',
      'Nama Sediaan',
      'Satuan',
      'No. Batch',
      'Tgl. Kadaluarsa',
      'Harga Jual',
      'Stok',
      'Aksi'
    ]);
  });

  it('table renders data rows and formats correctly', async () => {
    const wrapper = mount(StockOpnameTable, {
      props: {
        items: mockStocksList,
        paginationInfo: {
          current_page: 1,
          per_page: 10,
          total: 2
        },
        loading: false
      }
    });

    const rows = wrapper.findAll('tbody tr');
    expect(rows.length).toBe(2);

    // Row 1
    const cols = rows[0].findAll('td');
    expect(cols[0].text()).toBe('1'); // No
    expect(cols[1].text()).toBe('Gudang Utama'); // Gudang
    expect(cols[2].text()).toContain('Alprazolam 0'); // Nama Sediaan
    expect(cols[3].text()).toBe('Tablet'); // Satuan
    expect(cols[4].text()).toBe('0987654321'); // No. Batch
    expect(cols[5].text()).toBe('20/10/2022'); // Tgl. Kadaluarsa
    expect(cols[6].text()).toBe('Rp1.000'); // Harga Jual
    expect(cols[7].text()).toBe('500'); // Stok
    expect(cols[8].find('.btn-edit-action').exists()).toBe(true); // Aksi
  });

  it('applies warehouse filter and calls backend endpoint', async () => {
    const wrapper = mount(StockOpnameView);
    await flushPromises();

    const filtersComp = wrapper.findComponent(StockOpnameFilters);
    await filtersComp.find('#filter-warehouse').setValue('1');
    await flushPromises();

    expect(stockOpnamesService.getStockOpnames).toHaveBeenCalledWith(
      expect.objectContaining({
        warehouse_id: 1,
        page: 1
      })
    );
  });

  it('toggles hide zero stock filter', async () => {
    const wrapper = mount(StockOpnameView);
    await flushPromises();

    const filtersComp = wrapper.findComponent(StockOpnameFilters);
    await filtersComp.find('#filter-hide-zero').setValue(false);
    await flushPromises();

    expect(stockOpnamesService.getStockOpnames).toHaveBeenCalledWith(
      expect.objectContaining({
        hide_zero_stock: 0,
        page: 1
      })
    );
  });

  it('filters by search keyword', async () => {
    const wrapper = mount(StockOpnameView);
    await flushPromises();

    const filtersComp = wrapper.findComponent(StockOpnameFilters);
    await filtersComp.find('#filter-search').setValue('Alprazolam');
    await filtersComp.find('form').trigger('submit.prevent');
    await flushPromises();

    expect(stockOpnamesService.getStockOpnames).toHaveBeenCalledWith(
      expect.objectContaining({
        search: 'Alprazolam',
        page: 1
      })
    );
  });

  it('edit modal opens with only the 4 specified fields and readonly warehouse', async () => {
    const wrapper = mount(StockOpnameEditModal, {
      props: {
        isOpen: true,
        item: mockStocksList[0],
        isSubmitting: false,
        apiErrors: {}
      },
      global: {
        stubs: {
          Teleport: true
        }
      }
    });

    expect(wrapper.find('.modal-title').text()).toBe('Edit Stok Opname');

    // 1. Gudang
    const whInput = wrapper.find('#edit-warehouse');
    expect(whInput.exists()).toBe(true);
    expect(whInput.attributes('disabled')).toBeDefined();
    expect(whInput.element.value).toBe('Gudang Utama');

    // 2. Tanggal Kadaluarsa
    const expInput = wrapper.find('#edit-exp-date');
    expect(expInput.exists()).toBe(true);
    expect(expInput.element.value).toBe('2022-10-20');

    // 3. Harga Jual
    const priceInput = wrapper.find('#edit-selling-price');
    expect(priceInput.exists()).toBe(true);
    expect(priceInput.element.value).toBe('1000');

    // 4. Stok Sediaan
    const stockInput = wrapper.find('#edit-stock');
    expect(stockInput.exists()).toBe(true);
    expect(stockInput.element.value).toBe('500');

    // Previous stock helper text
    expect(wrapper.find('.stock-helper').text()).toContain('Stok sebelumnya: 500 Tablet');

    // Exactly 4 form inputs
    const formInputs = wrapper.findAll('.form-control');
    expect(formInputs.length).toBe(4);
  });

  it('edit modal rejects negative stock on submit', async () => {
    const wrapper = mount(StockOpnameEditModal, {
      props: {
        isOpen: true,
        item: mockStocksList[0],
        isSubmitting: false
      },
      global: {
        stubs: {
          Teleport: true
        }
      }
    });

    await wrapper.find('#edit-stock').setValue(-10);
    await wrapper.find('form').trigger('submit.prevent');

    expect(wrapper.text()).toContain('Stok sediaan tidak boleh bernilai negatif.');
    expect(wrapper.emitted('submit')).toBeFalsy();
  });

  it('edit modal accepts zero stock and submits valid payload', async () => {
    const wrapper = mount(StockOpnameEditModal, {
      props: {
        isOpen: true,
        item: mockStocksList[0],
        isSubmitting: false
      },
      global: {
        stubs: {
          Teleport: true
        }
      }
    });

    await wrapper.find('#edit-stock').setValue(0);
    await wrapper.find('form').trigger('submit.prevent');

    expect(wrapper.emitted('submit')).toBeTruthy();
    expect(wrapper.emitted('submit')[0][0]).toEqual({
      expiration_date: '2022-10-20',
      selling_price: 1000,
      stock: 0
    });
  });

  it('modal disables submit button while submitting to prevent double submit', async () => {
    const wrapper = mount(StockOpnameEditModal, {
      props: {
        isOpen: true,
        item: mockStocksList[0],
        isSubmitting: true
      },
      global: {
        stubs: {
          Teleport: true
        }
      }
    });

    const submitBtn = wrapper.find('button[type="submit"]');
    expect(submitBtn.attributes('disabled')).toBeDefined();
    expect(submitBtn.text()).toContain('Menyimpan...');
  });

  it('updates stock and shows success toast when saving edit in StockOpnameView', async () => {
    stockOpnamesService.updateStockOpname.mockResolvedValueOnce({
      data: { ...mockStocksList[0], stock: 450 },
      message: 'Stok opname berhasil diperbarui.'
    });

    const wrapper = mount(StockOpnameView);
    await flushPromises();

    // Trigger edit on row 1
    const tableComp = wrapper.findComponent(StockOpnameTable);
    tableComp.vm.$emit('edit', mockStocksList[0]);
    await flushPromises();

    expect(wrapper.findComponent(StockOpnameEditModal).props('isOpen')).toBe(true);

    // Submit edit
    const editModal = wrapper.findComponent(StockOpnameEditModal);
    editModal.vm.$emit('submit', {
      expiration_date: '2027-10-20',
      selling_price: 1000,
      stock: 450
    });
    await flushPromises();

    expect(stockOpnamesService.updateStockOpname).toHaveBeenCalledWith(1, {
      expiration_date: '2027-10-20',
      selling_price: 1000,
      stock: 450
    });

    // Modal closed and success toast shown
    expect(wrapper.findComponent(StockOpnameEditModal).props('isOpen')).toBe(false);
    expect(wrapper.text()).toContain('Stok opname berhasil diperbarui.');
  });
});
