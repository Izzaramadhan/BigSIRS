import { flushPromises, mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import WarehousesView from '../WarehousesView.vue';
import { warehouseService } from '@/services/master-data/warehouses.service';

vi.mock('@/services/master-data/warehouses.service', () => ({
  warehouseService: {
    getWarehouses: vi.fn(),
    getWarehouse: vi.fn(),
    createWarehouse: vi.fn(),
    updateWarehouse: vi.fn(),
    deleteWarehouse: vi.fn()
  }
}));

const mockPaginatedResponse = {
  data: [
    {
      id: 1,
      code: 'GDG-01',
      name: 'Gudang Utama Farmasi',
      description: 'Pusat penyimpanan farmasi'
    },
    {
      id: 2,
      code: '002',
      name: 'Gudang Rawat Inap',
      description: null
    }
  ],
  meta: { current_page: 1, last_page: 1, per_page: 10, total: 2 },
  links: {}
};

describe('WarehousesView.vue', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    warehouseService.getWarehouses.mockResolvedValue(mockPaginatedResponse);
  });

  const mountView = () =>
    mount(WarehousesView, {
      global: {
        stubs: {
          Teleport: true
        }
      }
    });

  it('renders page header, breadcrumbs, add button, and table with 5 columns', async () => {
    const wrapper = mountView();
    await flushPromises();

    // Title and description
    expect(wrapper.text()).toContain('Gudang');
    expect(wrapper.text()).toContain('Kelola master data gudang.');

    // Breadcrumbs
    expect(wrapper.text()).toContain('Dashboard');
    expect(wrapper.text()).toContain('Master Data');
    expect(wrapper.text()).toContain('Logistik');

    // Add button
    expect(wrapper.text()).toContain('Tambah Gudang');

    // Table data
    expect(wrapper.text()).toContain('Gudang Utama Farmasi');
    expect(wrapper.text()).toContain('GDG-01');
    expect(wrapper.text()).toContain('Gudang Rawat Inap');
    expect(wrapper.text()).toContain('002');

    // Exact 5 table columns with Kode before Nama
    const ths = wrapper.findAll('thead th');
    expect(ths).toHaveLength(5);
    expect(ths[0].text()).toBe('No');
    expect(ths[1].text()).toContain('Kode');
    expect(ths[2].text()).toContain('Nama');
    expect(ths[3].text()).toBe('Deskripsi');
    expect(ths[4].text()).toBe('Aksi');
  });

  it('shows empty state when data is empty', async () => {
    warehouseService.getWarehouses.mockResolvedValue({
      data: [],
      meta: { current_page: 1, last_page: 1, per_page: 10, total: 0 }
    });

    const wrapper = mountView();
    await flushPromises();

    expect(wrapper.text()).toContain('Belum ada data yang ditambahkan.');
  });

  it('handles add flow and shows exact success toast "Gudang berhasil ditambahkan."', async () => {
    warehouseService.createWarehouse.mockResolvedValue({
      data: { id: 3, code: 'GDG-03', name: 'Gudang IGD', description: 'Gudang gawat darurat' }
    });

    const wrapper = mountView();
    await flushPromises();

    // Click "Tambah Gudang"
    const addBtn = wrapper.find('.page-header button.btn-primary');
    await addBtn.trigger('click');

    // Check modal opened with Tambah title
    expect(wrapper.text()).toContain('Tambah Gudang');

    // Fill form
    const formModal = wrapper.findComponent({ name: 'WarehouseFormModal' });
    expect(formModal.exists()).toBe(true);

    const nameInput = formModal.find('input#name');
    const codeInput = formModal.find('input#code');
    const descInput = formModal.find('textarea#description');

    await nameInput.setValue('Gudang IGD');
    await codeInput.setValue('GDG-03');
    await descInput.setValue('Gudang gawat darurat');

    // Submit form
    await formModal.find('form').trigger('submit');
    await flushPromises();

    expect(warehouseService.createWarehouse).toHaveBeenCalledWith({
      name: 'Gudang IGD',
      code: 'GDG-03',
      description: 'Gudang gawat darurat'
    });

    // Check success toast
    expect(wrapper.text()).toContain('Gudang berhasil ditambahkan.');
  });

  it('handles edit flow and shows exact success toast "Gudang berhasil diperbarui."', async () => {
    warehouseService.updateWarehouse.mockResolvedValue({
      data: { id: 1, code: 'GDG-01-REV', name: 'Gudang Utama Farmasi Revisi', description: 'Deskripsi baru' }
    });

    const wrapper = mountView();
    await flushPromises();

    // Click edit on first row
    const editBtn = wrapper.findAll('tbody tr')[0].findAll('button')[0];
    await editBtn.trigger('click');

    expect(wrapper.text()).toContain('Edit Gudang');

    const formModal = wrapper.findComponent({ name: 'WarehouseFormModal' });
    const nameInput = formModal.find('input#name');
    await nameInput.setValue('Gudang Utama Farmasi Revisi');

    await formModal.find('form').trigger('submit');
    await flushPromises();

    expect(warehouseService.updateWarehouse).toHaveBeenCalledWith(
      1,
      expect.objectContaining({
        name: 'Gudang Utama Farmasi Revisi'
      })
    );

    expect(wrapper.text()).toContain('Gudang berhasil diperbarui.');
  });

  it('handles delete flow, shows confirmation with warehouse code and name, and shows exact toast "Gudang berhasil dihapus."', async () => {
    warehouseService.deleteWarehouse.mockResolvedValue({ success: true });

    const wrapper = mountView();
    await flushPromises();

    // Click delete on first row (GDG-01 - Gudang Utama Farmasi)
    const deleteBtn = wrapper.findAll('tbody tr')[0].findAll('button')[1];
    await deleteBtn.trigger('click');

    // Delete dialog opens
    const deleteDialog = wrapper.findComponent({ name: 'MasterDataDeleteDialog' });
    expect(deleteDialog.exists()).toBe(true);
    expect(deleteDialog.props('isOpen')).toBe(true);
    expect(deleteDialog.props('itemName')).toBe('GDG-01 - Gudang Utama Farmasi');

    // Confirm delete
    deleteDialog.vm.$emit('confirm');
    await flushPromises();

    expect(warehouseService.deleteWarehouse).toHaveBeenCalledWith(1);
    expect(wrapper.text()).toContain('Gudang berhasil dihapus.');
  });

  it('handles delete error (e.g. 409 conflict when warehouse used by polyclinic) and shows error toast', async () => {
    const errorResponse = {
      response: {
        status: 409,
        data: {
          message: 'Gudang tidak dapat dihapus karena masih digunakan sebagai gudang default pada poliklinik.'
        }
      }
    };
    warehouseService.deleteWarehouse.mockRejectedValue(errorResponse);

    const wrapper = mountView();
    await flushPromises();

    // Click delete on first row
    const deleteBtn = wrapper.findAll('tbody tr')[0].findAll('button')[1];
    await deleteBtn.trigger('click');

    const deleteDialog = wrapper.findComponent({ name: 'MasterDataDeleteDialog' });
    deleteDialog.vm.$emit('confirm');
    await flushPromises();

    expect(wrapper.text()).toContain('Gudang tidak dapat dihapus karena masih digunakan sebagai gudang default pada poliklinik.');
  });

  it('adjusts pagination when deleting the only item on a subsequent page', async () => {
    warehouseService.getWarehouses.mockResolvedValue({
      data: [{ id: 5, code: 'GDG-P2', name: 'Gudang Halaman 2', description: null }],
      meta: { current_page: 2, last_page: 2, per_page: 10, total: 11 }
    });
    warehouseService.deleteWarehouse.mockResolvedValue({ success: true });

    const wrapper = mountView();
    await flushPromises();

    const deleteBtn = wrapper.findAll('tbody tr')[0].findAll('button')[1];
    await deleteBtn.trigger('click');

    const deleteDialog = wrapper.findComponent({ name: 'MasterDataDeleteDialog' });
    deleteDialog.vm.$emit('confirm');
    await flushPromises();

    // Should fetch page 1 after deletion
    expect(warehouseService.getWarehouses).toHaveBeenLastCalledWith(
      expect.objectContaining({ page: 1 })
    );
  });
});
