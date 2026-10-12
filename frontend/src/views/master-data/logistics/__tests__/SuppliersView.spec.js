import { flushPromises, mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import SuppliersView from '../SuppliersView.vue';
import { suppliersService } from '@/services/master-data/suppliers.service';

vi.mock('@/services/master-data/suppliers.service', () => ({
  suppliersService: {
    getSuppliers: vi.fn(),
    getSupplier: vi.fn(),
    createSupplier: vi.fn(),
    updateSupplier: vi.fn(),
    deleteSupplier: vi.fn()
  }
}));

const mockPaginatedResponse = {
  data: [
    {
      id: 1,
      name: 'PT Kimia Farma',
      phone: '08123456789',
      address: 'Jl. Veteran No. 10'
    },
    {
      id: 2,
      name: 'PT Kalbe Farma',
      phone: null,
      address: null
    }
  ],
  meta: { current_page: 1, last_page: 1, per_page: 10, total: 2 },
  links: {}
};

describe('SuppliersView.vue', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    suppliersService.getSuppliers.mockResolvedValue(mockPaginatedResponse);
  });

  const mountView = () =>
    mount(SuppliersView, {
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
    expect(wrapper.text()).toContain('Supplier');
    expect(wrapper.text()).toContain('Kelola master data supplier.');

    // Breadcrumbs
    expect(wrapper.text()).toContain('Dashboard');
    expect(wrapper.text()).toContain('Master Data');
    expect(wrapper.text()).toContain('Logistik');

    // Add button
    expect(wrapper.text()).toContain('Tambah Supplier');

    // Table data
    expect(wrapper.text()).toContain('PT Kimia Farma');
    expect(wrapper.text()).toContain('PT Kalbe Farma');

    // Exact 5 table columns
    const ths = wrapper.findAll('thead th');
    expect(ths).toHaveLength(5);
    expect(ths[0].text()).toBe('No');
    expect(ths[1].text()).toContain('Nama');
    expect(ths[2].text()).toBe('Telp');
    expect(ths[3].text()).toBe('Alamat');
    expect(ths[4].text()).toBe('Aksi');
  });

  it('shows empty state when data is empty', async () => {
    suppliersService.getSuppliers.mockResolvedValue({
      data: [],
      meta: { current_page: 1, last_page: 1, per_page: 10, total: 0 }
    });

    const wrapper = mountView();
    await flushPromises();

    expect(wrapper.text()).toContain('Belum ada data yang ditambahkan.');
  });

  it('handles add flow and shows exact success toast "Supplier berhasil ditambahkan."', async () => {
    suppliersService.createSupplier.mockResolvedValue({
      data: { id: 3, name: 'PT Indofarma', phone: '08111222333', address: 'Jl. Pemuda' }
    });

    const wrapper = mountView();
    await flushPromises();

    // Click "Tambah Supplier"
    const addBtn = wrapper.find('.page-header button.btn-primary');
    await addBtn.trigger('click');

    // Check modal opened with Tambah title
    expect(wrapper.text()).toContain('Tambah Supplier');

    // Fill form
    const formModal = wrapper.findComponent({ name: 'SupplierFormModal' });
    expect(formModal.exists()).toBe(true);

    const nameInput = formModal.find('input#name');
    const phoneInput = formModal.find('input#phone');
    const addressInput = formModal.find('textarea#address');

    await nameInput.setValue('PT Indofarma');
    await phoneInput.setValue('08111222333');
    await addressInput.setValue('Jl. Pemuda');

    // Submit form
    await formModal.find('form').trigger('submit');
    await flushPromises();

    expect(suppliersService.createSupplier).toHaveBeenCalledWith({
      name: 'PT Indofarma',
      phone: '08111222333',
      address: 'Jl. Pemuda'
    });

    // Check success toast
    expect(wrapper.text()).toContain('Supplier berhasil ditambahkan.');
  });

  it('handles edit flow and shows exact success toast "Supplier berhasil diperbarui."', async () => {
    suppliersService.updateSupplier.mockResolvedValue({
      data: { id: 1, name: 'PT Kimia Farma Tbk', phone: '08123456789', address: 'Jl. Veteran No. 10' }
    });

    const wrapper = mountView();
    await flushPromises();

    // Click edit on first row
    const editBtn = wrapper.findAll('tbody tr')[0].findAll('button')[0];
    await editBtn.trigger('click');

    expect(wrapper.text()).toContain('Edit Supplier');

    const formModal = wrapper.findComponent({ name: 'SupplierFormModal' });
    const nameInput = formModal.find('input#name');
    await nameInput.setValue('PT Kimia Farma Tbk');

    await formModal.find('form').trigger('submit');
    await flushPromises();

    expect(suppliersService.updateSupplier).toHaveBeenCalledWith(
      1,
      expect.objectContaining({
        name: 'PT Kimia Farma Tbk'
      })
    );

    expect(wrapper.text()).toContain('Supplier berhasil diperbarui.');
  });

  it('handles delete flow, shows confirmation with supplier name, and shows exact toast "Supplier berhasil dihapus."', async () => {
    suppliersService.deleteSupplier.mockResolvedValue({ success: true });

    const wrapper = mountView();
    await flushPromises();

    // Click delete on first row (PT Kimia Farma)
    const deleteBtn = wrapper.findAll('tbody tr')[0].findAll('button')[1];
    await deleteBtn.trigger('click');

    // Delete dialog opens
    const deleteDialog = wrapper.findComponent({ name: 'MasterDataDeleteDialog' });
    expect(deleteDialog.exists()).toBe(true);
    expect(deleteDialog.props('isOpen')).toBe(true);
    expect(deleteDialog.props('itemName')).toBe('PT Kimia Farma');

    // Confirm delete
    deleteDialog.vm.$emit('confirm');
    await flushPromises();

    expect(suppliersService.deleteSupplier).toHaveBeenCalledWith(1);
    expect(wrapper.text()).toContain('Supplier berhasil dihapus.');
  });

  it('adjusts pagination when deleting the only item on a subsequent page', async () => {
    suppliersService.getSuppliers.mockResolvedValue({
      data: [{ id: 5, name: 'Supplier Halaman 2', phone: null, address: null }],
      meta: { current_page: 2, last_page: 2, per_page: 10, total: 11 }
    });
    suppliersService.deleteSupplier.mockResolvedValue({ success: true });

    const wrapper = mountView();
    await flushPromises();

    const deleteBtn = wrapper.findAll('tbody tr')[0].findAll('button')[1];
    await deleteBtn.trigger('click');

    const deleteDialog = wrapper.findComponent({ name: 'MasterDataDeleteDialog' });
    deleteDialog.vm.$emit('confirm');
    await flushPromises();

    // Should fetch page 1 after deletion
    expect(suppliersService.getSuppliers).toHaveBeenLastCalledWith(
      expect.objectContaining({ page: 1 })
    );
  });
});
