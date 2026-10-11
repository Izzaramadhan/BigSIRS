import { flushPromises, mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import MedicationRoutesView from '../MedicationRoutesView.vue';
import { medicineRoutesService } from '@/services/master-data/medicineRoutes.service';

vi.mock('@/services/master-data/medicineRoutes.service', () => ({
  medicineRoutesService: {
    getMedicineRoutes: vi.fn(),
    getMedicineRoute: vi.fn(),
    createMedicineRoute: vi.fn(),
    updateMedicineRoute: vi.fn(),
    deleteMedicineRoute: vi.fn()
  }
}));

const mockPaginatedResponse = {
  data: [
    {
      id: 1,
      name: 'ORAL',
      object_code: 'MDCTx000000002'
    },
    {
      id: 2,
      name: 'INTRAVENOUS',
      object_code: 'MDCTx000000003'
    }
  ],
  meta: { current_page: 1, last_page: 1, per_page: 10, total: 2 },
  links: {}
};

describe('MedicationRoutesView.vue', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    medicineRoutesService.getMedicineRoutes.mockResolvedValue(mockPaginatedResponse);
  });

  const mountView = () =>
    mount(MedicationRoutesView, {
      global: {
        stubs: {
          Teleport: true
        }
      }
    });

  it('renders page header, breadcrumbs, add button, and table with 4 columns', async () => {
    const wrapper = mountView();
    await flushPromises();

    // Title and description
    expect(wrapper.text()).toContain('Jalur Masuk Obat');
    expect(wrapper.text()).toContain('Kelola master data jalur masuk obat.');

    // Breadcrumbs
    expect(wrapper.text()).toContain('Dashboard');
    expect(wrapper.text()).toContain('Master Data');
    expect(wrapper.text()).toContain('Logistik');

    // Add button
    expect(wrapper.text()).toContain('Tambah Jalur Masuk Obat');

    // Table data
    expect(wrapper.text()).toContain('ORAL');
    expect(wrapper.text()).toContain('MDCTx000000002');
    expect(wrapper.text()).toContain('INTRAVENOUS');
    expect(wrapper.text()).toContain('MDCTx000000003');

    // Exact 4 table columns
    const ths = wrapper.findAll('thead th');
    expect(ths).toHaveLength(4);
    expect(ths[0].text()).toBe('No');
    expect(ths[1].text()).toContain('Nama Jalur Masuk');
    expect(ths[2].text()).toContain('Objek');
    expect(ths[3].text()).toBe('Aksi');
  });

  it('shows empty state when data is empty', async () => {
    medicineRoutesService.getMedicineRoutes.mockResolvedValue({
      data: [],
      meta: { current_page: 1, last_page: 1, per_page: 10, total: 0 }
    });

    const wrapper = mountView();
    await flushPromises();

    expect(wrapper.text()).toContain('Belum ada data yang ditambahkan.');
  });

  it('handles add flow and shows exact success toast "Jalur masuk obat berhasil ditambahkan."', async () => {
    medicineRoutesService.createMedicineRoute.mockResolvedValue({
      data: { id: 3, name: 'SUBCUTANEOUS', object_code: 'MDCTx000000004' }
    });

    const wrapper = mountView();
    await flushPromises();

    // Click "Tambah Jalur Masuk Obat"
    const addBtn = wrapper.find('.page-header button.btn-primary');
    await addBtn.trigger('click');

    // Check modal opened with Tambah title
    expect(wrapper.text()).toContain('Tambah Jalur Masuk Obat');

    // Fill form
    const formModal = wrapper.findComponent({ name: 'MedicationRouteFormModal' });
    expect(formModal.exists()).toBe(true);

    const nameInput = formModal.find('input#name');
    const objInput = formModal.find('input#object_code');

    await nameInput.setValue('SUBCUTANEOUS');
    await objInput.setValue('MDCTx000000004');

    // Submit form
    await formModal.find('form').trigger('submit');
    await flushPromises();

    expect(medicineRoutesService.createMedicineRoute).toHaveBeenCalledWith({
      name: 'SUBCUTANEOUS',
      object_code: 'MDCTx000000004'
    });

    // Check success toast
    expect(wrapper.text()).toContain('Jalur masuk obat berhasil ditambahkan.');
  });

  it('handles edit flow and shows exact success toast "Jalur masuk obat berhasil diperbarui."', async () => {
    medicineRoutesService.updateMedicineRoute.mockResolvedValue({
      data: { id: 1, name: 'ORAL MODIFIED', object_code: 'MDCTx000000002' }
    });

    const wrapper = mountView();
    await flushPromises();

    // Click edit on first row
    const editBtn = wrapper.findAll('tbody tr')[0].findAll('button')[0];
    await editBtn.trigger('click');

    expect(wrapper.text()).toContain('Edit Jalur Masuk Obat');

    const formModal = wrapper.findComponent({ name: 'MedicationRouteFormModal' });
    const nameInput = formModal.find('input#name');
    await nameInput.setValue('ORAL MODIFIED');

    await formModal.find('form').trigger('submit');
    await flushPromises();

    expect(medicineRoutesService.updateMedicineRoute).toHaveBeenCalledWith(
      1,
      expect.objectContaining({
        name: 'ORAL MODIFIED'
      })
    );

    expect(wrapper.text()).toContain('Jalur masuk obat berhasil diperbarui.');
  });

  it('handles delete flow, shows confirmation with route name, and shows exact toast "Jalur masuk obat berhasil dihapus."', async () => {
    medicineRoutesService.deleteMedicineRoute.mockResolvedValue({ success: true });

    const wrapper = mountView();
    await flushPromises();

    // Click delete on first row (ORAL)
    const deleteBtn = wrapper.findAll('tbody tr')[0].findAll('button')[1];
    await deleteBtn.trigger('click');

    // Delete dialog opens
    const deleteDialog = wrapper.findComponent({ name: 'MasterDataDeleteDialog' });
    expect(deleteDialog.exists()).toBe(true);
    expect(deleteDialog.props('isOpen')).toBe(true);
    expect(deleteDialog.props('itemName')).toBe('ORAL');

    // Confirm delete
    deleteDialog.vm.$emit('confirm');
    await flushPromises();

    expect(medicineRoutesService.deleteMedicineRoute).toHaveBeenCalledWith(1);
    expect(wrapper.text()).toContain('Jalur masuk obat berhasil dihapus.');
  });

  it('handles delete error (e.g. when used by medicines) and shows error toast', async () => {
    const errorResponse = {
      response: {
        status: 422,
        data: {
          message: 'Jalur masuk obat tidak dapat dihapus karena sudah digunakan pada data obat.'
        }
      }
    };
    medicineRoutesService.deleteMedicineRoute.mockRejectedValue(errorResponse);

    const wrapper = mountView();
    await flushPromises();

    // Click delete on first row
    const deleteBtn = wrapper.findAll('tbody tr')[0].findAll('button')[1];
    await deleteBtn.trigger('click');

    const deleteDialog = wrapper.findComponent({ name: 'MasterDataDeleteDialog' });
    deleteDialog.vm.$emit('confirm');
    await flushPromises();

    expect(wrapper.text()).toContain('Jalur masuk obat tidak dapat dihapus karena sudah digunakan pada data obat.');
  });

  it('adjusts pagination when deleting the only item on a subsequent page', async () => {
    medicineRoutesService.getMedicineRoutes.mockResolvedValue({
      data: [{ id: 5, name: 'Rute Halaman 2', object_code: null }],
      meta: { current_page: 2, last_page: 2, per_page: 10, total: 11 }
    });
    medicineRoutesService.deleteMedicineRoute.mockResolvedValue({ success: true });

    const wrapper = mountView();
    await flushPromises();

    const deleteBtn = wrapper.findAll('tbody tr')[0].findAll('button')[1];
    await deleteBtn.trigger('click');

    const deleteDialog = wrapper.findComponent({ name: 'MasterDataDeleteDialog' });
    deleteDialog.vm.$emit('confirm');
    await flushPromises();

    // Should fetch page 1 after deletion
    expect(medicineRoutesService.getMedicineRoutes).toHaveBeenLastCalledWith(
      expect.objectContaining({ page: 1 })
    );
  });
});
