import { flushPromises, mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import MedicinePackagesView from '../MedicinePackagesView.vue';
import { medicinePackagesService } from '@/services/master-data/medicinePackages.service';

vi.mock('@/services/master-data/medicinePackages.service', () => ({
  medicinePackagesService: {
    getMedicinePackages: vi.fn(),
    getMedicinePackage: vi.fn(),
    createMedicinePackage: vi.fn(),
    updateMedicinePackage: vi.fn(),
    deleteMedicinePackage: vi.fn()
  }
}));

const mockPaginatedResponse = {
  data: [
    {
      id: 1,
      name: 'Paket Flu Dewasa',
      price: 35000,
      quantity: 10,
      description: 'Paket obat flu',
      items: [
        { id: 10, medicine_id: 1, medicine: { id: 1, name: 'Paracetamol' }, quantity: 1, unit_price: 5000, subtotal: 5000 },
        { id: 11, medicine_id: 2, medicine: { id: 2, name: 'Pseudoephedrine' }, quantity: 1, unit_price: 30000, subtotal: 30000 }
      ]
    },
    {
      id: 2,
      name: 'Paket Batuk Kering',
      price: 20000,
      quantity: 5,
      description: 'Paket obat batuk',
      items: [
        { id: 12, medicine_id: 3, medicine: { id: 3, name: 'Dextromethorphan' }, quantity: 1, unit_price: 20000, subtotal: 20000 }
      ]
    }
  ],
  meta: { current_page: 1, last_page: 1, per_page: 10, total: 2 },
  links: {}
};

describe('MedicinePackagesView.vue', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    medicinePackagesService.getMedicinePackages.mockResolvedValue(mockPaginatedResponse);
  });

  const mountView = () =>
    mount(MedicinePackagesView, {
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
    expect(wrapper.text()).toContain('Master Paket Obat');
    expect(wrapper.text()).toContain('Kelola komposisi dan master data paket obat.');

    // Breadcrumbs
    expect(wrapper.text()).toContain('Dashboard');
    expect(wrapper.text()).toContain('Master Data');
    expect(wrapper.text()).toContain('Logistik');

    // Add button
    expect(wrapper.text()).toContain('Tambah Paket Obat');

    // Table data
    expect(wrapper.text()).toContain('Paket Flu Dewasa');
    expect(wrapper.text()).toContain('Paket Batuk Kering');
    expect(wrapper.text()).toContain('35.000');
    expect(wrapper.text()).toContain('20.000');
    expect(wrapper.text()).toContain('Paracetamol');

    // Exact 5 table columns
    const ths = wrapper.findAll('thead th');
    expect(ths).toHaveLength(5);
    expect(ths[0].text()).toBe('No');
    expect(ths[1].text()).toContain('Nama');
    expect(ths[2].text()).toContain('Harga');
    expect(ths[3].text()).toContain('Obat');
    expect(ths[4].text()).toBe('Aksi');
  });

  it('shows empty state when data is empty', async () => {
    medicinePackagesService.getMedicinePackages.mockResolvedValue({
      data: [],
      meta: { current_page: 1, last_page: 1, per_page: 10, total: 0 }
    });

    const wrapper = mountView();
    await flushPromises();

    expect(wrapper.text()).toContain('Belum ada data yang ditambahkan.');
  });

  it('handles add flow and shows exact success toast "Paket obat berhasil ditambahkan."', async () => {
    medicinePackagesService.createMedicinePackage.mockResolvedValue({
      data: { id: 3, name: 'Paket Asma', price: 50000, items: [] }
    });

    const wrapper = mountView();
    await flushPromises();

    // Click "Tambah Paket Obat"
    const addBtn = wrapper.find('.page-header button.btn-primary');
    await addBtn.trigger('click');

    // Check modal opened with Tambah title
    expect(wrapper.text()).toContain('Tambah Paket Obat');

    const formModal = wrapper.findComponent({ name: 'MedicinePackageFormModal' });
    expect(formModal.exists()).toBe(true);

    const nameInput = formModal.find('input#name');
    await nameInput.setValue('Paket Asma');

    // Mock an item added to items array
    formModal.vm.form.items.push({
      medicine_id: 10,
      medicine_name: 'Salbutamol Inhaler',
      quantity: 1,
      unit_price: 50000,
      subtotal: 50000
    });
    formModal.vm.form.price = 50000;

    // Submit form
    await formModal.find('form').trigger('submit');
    await flushPromises();

    expect(medicinePackagesService.createMedicinePackage).toHaveBeenCalledWith(
      expect.objectContaining({
        name: 'Paket Asma',
        price: 50000
      })
    );

    expect(wrapper.text()).toContain('Paket obat berhasil ditambahkan.');
  });

  it('handles edit flow and shows exact success toast "Paket obat berhasil diperbarui."', async () => {
    medicinePackagesService.updateMedicinePackage.mockResolvedValue({
      data: { id: 1, name: 'Paket Flu Dewasa Ekstra', price: 40000 }
    });

    const wrapper = mountView();
    await flushPromises();

    // Click edit on first row
    const editBtn = wrapper.findAll('tbody tr')[0].findAll('button')[0];
    await editBtn.trigger('click');

    expect(wrapper.text()).toContain('Edit Paket Obat');

    const formModal = wrapper.findComponent({ name: 'MedicinePackageFormModal' });
    const nameInput = formModal.find('input#name');
    await nameInput.setValue('Paket Flu Dewasa Ekstra');

    await formModal.find('form').trigger('submit');
    await flushPromises();

    expect(medicinePackagesService.updateMedicinePackage).toHaveBeenCalledWith(
      1,
      expect.objectContaining({
        name: 'Paket Flu Dewasa Ekstra'
      })
    );

    expect(wrapper.text()).toContain('Paket obat berhasil diperbarui.');
  });

  it('handles delete flow, shows confirmation with package name, and shows exact toast "Paket obat berhasil dihapus."', async () => {
    medicinePackagesService.deleteMedicinePackage.mockResolvedValue({ success: true });

    const wrapper = mountView();
    await flushPromises();

    // Click delete on first row (Paket Flu Dewasa)
    const deleteBtn = wrapper.findAll('tbody tr')[0].findAll('button')[1];
    await deleteBtn.trigger('click');

    // Delete dialog opens
    const deleteDialog = wrapper.findComponent({ name: 'MasterDataDeleteDialog' });
    expect(deleteDialog.exists()).toBe(true);
    expect(deleteDialog.props('isOpen')).toBe(true);
    expect(deleteDialog.props('itemName')).toBe('Paket Flu Dewasa');

    // Confirm delete
    deleteDialog.vm.$emit('confirm');
    await flushPromises();

    expect(medicinePackagesService.deleteMedicinePackage).toHaveBeenCalledWith(1);
    expect(wrapper.text()).toContain('Paket obat berhasil dihapus.');
  });

  it('handles delete error and shows error toast', async () => {
    const errorResponse = {
      response: {
        status: 422,
        data: {
          message: 'Paket obat tidak dapat dihapus karena sedang aktif digunakan pada transaksi resep.'
        }
      }
    };
    medicinePackagesService.deleteMedicinePackage.mockRejectedValue(errorResponse);

    const wrapper = mountView();
    await flushPromises();

    const deleteBtn = wrapper.findAll('tbody tr')[0].findAll('button')[1];
    await deleteBtn.trigger('click');

    const deleteDialog = wrapper.findComponent({ name: 'MasterDataDeleteDialog' });
    deleteDialog.vm.$emit('confirm');
    await flushPromises();

    expect(wrapper.text()).toContain('Paket obat tidak dapat dihapus karena sedang aktif digunakan pada transaksi resep.');
  });

  it('adjusts pagination when deleting the only item on a subsequent page', async () => {
    medicinePackagesService.getMedicinePackages.mockResolvedValue({
      data: [{ id: 5, name: 'Paket Halaman 2', price: 10000, items: [] }],
      meta: { current_page: 2, last_page: 2, per_page: 10, total: 11 }
    });
    medicinePackagesService.deleteMedicinePackage.mockResolvedValue({ success: true });

    const wrapper = mountView();
    await flushPromises();

    const deleteBtn = wrapper.findAll('tbody tr')[0].findAll('button')[1];
    await deleteBtn.trigger('click');

    const deleteDialog = wrapper.findComponent({ name: 'MasterDataDeleteDialog' });
    deleteDialog.vm.$emit('confirm');
    await flushPromises();

    // Should fetch page 1 after deletion
    expect(medicinePackagesService.getMedicinePackages).toHaveBeenLastCalledWith(
      expect.objectContaining({ page: 1 })
    );
  });
});
