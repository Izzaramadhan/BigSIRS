import { flushPromises, mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import MedicineUnitsView from '../MedicineUnitsView.vue';
import { medicineUnitsService } from '@/services/master-data/medicineUnits.service';

vi.mock('@/services/master-data/medicineUnits.service', () => ({
  medicineUnitsService: {
    getMedicineUnits: vi.fn(),
    getMedicineUnit: vi.fn(),
    createMedicineUnit: vi.fn(),
    updateMedicineUnit: vi.fn(),
    deleteMedicineUnit: vi.fn()
  }
}));

const mockPaginatedResponse = {
  data: [
    {
      id: 1,
      name: 'Tablet',
      description: 'Bentuk sediaan tablet'
    },
    {
      id: 2,
      name: 'Kapsul',
      description: null
    }
  ],
  meta: { current_page: 1, last_page: 1, per_page: 10, total: 2 },
  links: {}
};

describe('MedicineUnitsView.vue', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    medicineUnitsService.getMedicineUnits.mockResolvedValue(mockPaginatedResponse);
  });

  const mountView = () =>
    mount(MedicineUnitsView, {
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
    expect(wrapper.text()).toContain('Satuan Obat');
    expect(wrapper.text()).toContain('Kelola master data satuan obat.');

    // Breadcrumbs
    expect(wrapper.text()).toContain('Dashboard');
    expect(wrapper.text()).toContain('Master Data');
    expect(wrapper.text()).toContain('Logistik');
    expect(wrapper.text()).toContain('Satuan Obat');

    // Add button
    expect(wrapper.text()).toContain('Tambah Satuan Obat');

    // Table data
    expect(wrapper.text()).toContain('Tablet');
    expect(wrapper.text()).toContain('Kapsul');

    // Exact 4 table columns
    const ths = wrapper.findAll('thead th');
    expect(ths).toHaveLength(4);
    expect(ths[0].text()).toBe('No');
    expect(ths[1].text()).toContain('Nama');
    expect(ths[2].text()).toBe('Deskripsi');
    expect(ths[3].text()).toBe('Aksi');
  });

  it('shows empty state when data is empty', async () => {
    medicineUnitsService.getMedicineUnits.mockResolvedValue({
      data: [],
      meta: { current_page: 1, last_page: 1, per_page: 10, total: 0 }
    });

    const wrapper = mountView();
    await flushPromises();

    expect(wrapper.text()).toContain('Belum ada data yang ditambahkan.');
  });

  it('handles add flow and shows exact success toast "Satuan obat berhasil ditambahkan."', async () => {
    medicineUnitsService.createMedicineUnit.mockResolvedValue({
      data: { id: 3, name: 'Sirup', description: 'Cairan oral' }
    });

    const wrapper = mountView();
    await flushPromises();

    // Click "Tambah Satuan Obat"
    const addBtn = wrapper.find('.page-header button.btn-primary');
    await addBtn.trigger('click');

    // Check modal opened with Tambah title
    expect(wrapper.text()).toContain('Tambah Satuan Obat');

    // Fill form
    const formModal = wrapper.findComponent({ name: 'MedicineUnitFormModal' });
    expect(formModal.exists()).toBe(true);

    const nameInput = formModal.find('input#name');
    const descInput = formModal.find('textarea#description');

    await nameInput.setValue('Sirup');
    await descInput.setValue('Cairan oral');

    // Submit form
    await formModal.find('form').trigger('submit');
    await flushPromises();

    expect(medicineUnitsService.createMedicineUnit).toHaveBeenCalledWith({
      name: 'Sirup',
      description: 'Cairan oral'
    });

    // Check success toast
    expect(wrapper.text()).toContain('Satuan obat berhasil ditambahkan.');
  });

  it('handles edit flow and shows exact success toast "Satuan obat berhasil diperbarui."', async () => {
    medicineUnitsService.updateMedicineUnit.mockResolvedValue({
      data: { id: 1, name: 'Tablet Salut', description: 'Tablet salut selaput' }
    });

    const wrapper = mountView();
    await flushPromises();

    // Click edit on first row
    const editBtn = wrapper.findAll('tbody tr')[0].findAll('button')[0];
    await editBtn.trigger('click');

    expect(wrapper.text()).toContain('Edit Satuan Obat');

    const formModal = wrapper.findComponent({ name: 'MedicineUnitFormModal' });
    const nameInput = formModal.find('input#name');
    await nameInput.setValue('Tablet Salut');

    await formModal.find('form').trigger('submit');
    await flushPromises();

    expect(medicineUnitsService.updateMedicineUnit).toHaveBeenCalledWith(1, {
      name: 'Tablet Salut',
      description: 'Bentuk sediaan tablet'
    });

    expect(wrapper.text()).toContain('Satuan obat berhasil diperbarui.');
  });

  it('handles delete flow, shows confirmation with unit name, and shows exact toast "Satuan obat berhasil dihapus."', async () => {
    medicineUnitsService.deleteMedicineUnit.mockResolvedValue({ success: true });

    const wrapper = mountView();
    await flushPromises();

    // Click delete on first row (Tablet)
    const deleteBtn = wrapper.findAll('tbody tr')[0].findAll('button')[1];
    await deleteBtn.trigger('click');

    // Delete dialog opens
    const deleteDialog = wrapper.findComponent({ name: 'MasterDataDeleteDialog' });
    expect(deleteDialog.exists()).toBe(true);
    expect(deleteDialog.props('isOpen')).toBe(true);
    expect(deleteDialog.props('itemName')).toBe('Tablet');

    // Confirm delete
    deleteDialog.vm.$emit('confirm');
    await flushPromises();

    expect(medicineUnitsService.deleteMedicineUnit).toHaveBeenCalledWith(1);
    expect(wrapper.text()).toContain('Satuan obat berhasil dihapus.');
  });

  it('adjusts pagination when deleting the only item on a subsequent page', async () => {
    medicineUnitsService.getMedicineUnits.mockResolvedValue({
      data: [{ id: 5, name: 'Unit Halaman 2', description: null }],
      meta: { current_page: 2, last_page: 2, per_page: 10, total: 11 }
    });
    medicineUnitsService.deleteMedicineUnit.mockResolvedValue({ success: true });

    const wrapper = mountView();
    await flushPromises();

    const deleteBtn = wrapper.findAll('tbody tr')[0].findAll('button')[1];
    await deleteBtn.trigger('click');

    const deleteDialog = wrapper.findComponent({ name: 'MasterDataDeleteDialog' });
    deleteDialog.vm.$emit('confirm');
    await flushPromises();

    // Should fetch page 1 after deletion
    expect(medicineUnitsService.getMedicineUnits).toHaveBeenLastCalledWith(
      expect.objectContaining({ page: 1 })
    );
  });
});
