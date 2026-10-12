import { flushPromises, mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import MedicinesView from '../MedicinesView.vue';
import { medicinesService } from '@/services/master-data/medicines.service';

vi.mock('@/services/master-data/medicines.service', () => ({
  medicinesService: {
    getMedicines: vi.fn(),
    getMedicine: vi.fn(),
    createMedicine: vi.fn(),
    updateMedicine: vi.fn(),
    deleteMedicine: vi.fn()
  }
}));

vi.mock('@/services/lookup.service', () => ({
  default: {
    getMedicineUnits: vi.fn().mockResolvedValue([{ id: 1, name: 'Tablet' }]),
    getMedicineCategories: vi.fn().mockResolvedValue([{ id: 1, name: 'Analgesik' }]),
    getMedicineClassifications: vi.fn().mockResolvedValue([{ id: 1, name: 'Obat Bebas' }]),
    getMedicineRoutes: vi.fn().mockResolvedValue([{ id: 1, name: 'Oral' }]),
    getGenericMedicines: vi.fn().mockResolvedValue([{ id: 1, name: 'Paracetamol' }])
  }
}));

const mockPaginatedResponse = {
  data: [
    {
      id: 1,
      code: 'OBT001',
      name: 'Paracetamol 500mg',
      kfa_code: '930001',
      function: 'Pereda Demam',
      medicine_unit_id: 1,
      medicine_category_id: 1,
      medicine_classification_id: 1,
      medicine_route_id: 1,
      generic_medicine_id: 1,
      description: 'Obat penurun panas',
      unit: { id: 1, name: 'Tablet' },
      category: { id: 1, name: 'Analgesik' },
      classification: { id: 1, name: 'Obat Bebas' },
      route: { id: 1, name: 'Oral' },
      generic: { id: 1, name: 'Paracetamol' }
    },
    {
      id: 2,
      code: 'OBT002',
      name: 'Amoxicillin 500mg',
      kfa_code: null,
      function: null,
      medicine_unit_id: null,
      medicine_category_id: null,
      medicine_classification_id: null,
      medicine_route_id: null,
      generic_medicine_id: null,
      description: null,
      unit: null,
      category: null,
      classification: null,
      route: null,
      generic: null
    }
  ],
  meta: { current_page: 1, last_page: 1, per_page: 10, total: 2 },
  links: {}
};

describe('MedicinesView.vue', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    medicinesService.getMedicines.mockResolvedValue(mockPaginatedResponse);
  });

  const mountView = () =>
    mount(MedicinesView, {
      global: {
        stubs: {
          Teleport: true
        }
      }
    });

  it('renders page header, breadcrumbs, add button, and table with 11 columns', async () => {
    const wrapper = mountView();
    await flushPromises();

    // Title and description
    expect(wrapper.text()).toContain('Obat');
    expect(wrapper.text()).toContain('Kelola master data obat.');

    // Breadcrumbs
    expect(wrapper.text()).toContain('Dashboard');
    expect(wrapper.text()).toContain('Master Data');
    expect(wrapper.text()).toContain('Logistik');

    // Add button
    expect(wrapper.text()).toContain('Tambah Obat');

    // Table data
    expect(wrapper.text()).toContain('Paracetamol 500mg');
    expect(wrapper.text()).toContain('Amoxicillin 500mg');

    // Exact 11 table columns
    const ths = wrapper.findAll('thead th');
    expect(ths).toHaveLength(11);
    expect(ths[0].text()).toBe('No');
    expect(ths[1].text()).toContain('Kode');
    expect(ths[2].text()).toContain('Nama');
    expect(ths[3].text()).toBe('Satuan');
    expect(ths[4].text()).toBe('Kategori');
    expect(ths[5].text()).toBe('Golongan');
    expect(ths[6].text()).toBe('Rute');
    expect(ths[7].text()).toBe('Generik');
    expect(ths[8].text()).toBe('Kode KFA');
    expect(ths[9].text()).toBe('Deskripsi');
    expect(ths[10].text()).toBe('Aksi');
  });

  it('shows empty state when data is empty', async () => {
    medicinesService.getMedicines.mockResolvedValue({
      data: [],
      meta: { current_page: 1, last_page: 1, per_page: 10, total: 0 }
    });

    const wrapper = mountView();
    await flushPromises();

    expect(wrapper.text()).toContain('Belum ada data yang ditambahkan.');
  });

  it('handles add flow and shows exact success toast "Obat berhasil ditambahkan."', async () => {
    medicinesService.createMedicine.mockResolvedValue({
      data: { id: 3, name: 'Ibuprofen', code: 'OBT003' }
    });

    const wrapper = mountView();
    await flushPromises();

    // Click "Tambah Obat"
    const addBtn = wrapper.find('.page-header button.btn-primary');
    await addBtn.trigger('click');

    // Check modal opened with Tambah title
    expect(wrapper.text()).toContain('Tambah Obat');

    // Fill form
    const formModal = wrapper.findComponent({ name: 'MedicineFormModal' });
    expect(formModal.exists()).toBe(true);

    const nameInput = formModal.find('input#name');
    await nameInput.setValue('Ibuprofen');

    // Submit form
    await formModal.find('form').trigger('submit');
    await flushPromises();

    expect(medicinesService.createMedicine).toHaveBeenCalledWith(
      expect.objectContaining({
        name: 'Ibuprofen'
      })
    );

    // Check success toast
    expect(wrapper.text()).toContain('Obat berhasil ditambahkan.');
  });

  it('handles edit flow and shows exact success toast "Obat berhasil diperbarui."', async () => {
    medicinesService.updateMedicine.mockResolvedValue({
      data: { id: 1, name: 'Paracetamol Forte' }
    });

    const wrapper = mountView();
    await flushPromises();

    // Click edit on first row
    const editBtn = wrapper.findAll('tbody tr')[0].findAll('button')[0];
    await editBtn.trigger('click');

    expect(wrapper.text()).toContain('Edit Obat');

    const formModal = wrapper.findComponent({ name: 'MedicineFormModal' });
    const nameInput = formModal.find('input#name');
    await nameInput.setValue('Paracetamol Forte');

    await formModal.find('form').trigger('submit');
    await flushPromises();

    expect(medicinesService.updateMedicine).toHaveBeenCalledWith(
      1,
      expect.objectContaining({
        name: 'Paracetamol Forte'
      })
    );

    expect(wrapper.text()).toContain('Obat berhasil diperbarui.');
  });

  it('handles delete flow, shows confirmation with medicine name, and shows exact toast "Obat berhasil dihapus."', async () => {
    medicinesService.deleteMedicine.mockResolvedValue({ success: true });

    const wrapper = mountView();
    await flushPromises();

    // Click delete on first row (Paracetamol 500mg)
    const deleteBtn = wrapper.findAll('tbody tr')[0].findAll('button')[1];
    await deleteBtn.trigger('click');

    // Delete dialog opens
    const deleteDialog = wrapper.findComponent({ name: 'MasterDataDeleteDialog' });
    expect(deleteDialog.exists()).toBe(true);
    expect(deleteDialog.props('isOpen')).toBe(true);
    expect(deleteDialog.props('itemName')).toBe('Paracetamol 500mg');

    // Confirm delete
    deleteDialog.vm.$emit('confirm');
    await flushPromises();

    expect(medicinesService.deleteMedicine).toHaveBeenCalledWith(1);
    expect(wrapper.text()).toContain('Obat berhasil dihapus.');
  });

  it('adjusts pagination when deleting the only item on a subsequent page', async () => {
    medicinesService.getMedicines.mockResolvedValue({
      data: [{ id: 5, name: 'Obat Halaman 2', code: 'OBT005' }],
      meta: { current_page: 2, last_page: 2, per_page: 10, total: 11 }
    });
    medicinesService.deleteMedicine.mockResolvedValue({ success: true });

    const wrapper = mountView();
    await flushPromises();

    const deleteBtn = wrapper.findAll('tbody tr')[0].findAll('button')[1];
    await deleteBtn.trigger('click');

    const deleteDialog = wrapper.findComponent({ name: 'MasterDataDeleteDialog' });
    deleteDialog.vm.$emit('confirm');
    await flushPromises();

    // Should fetch page 1 after deletion
    expect(medicinesService.getMedicines).toHaveBeenLastCalledWith(
      expect.objectContaining({ page: 1 })
    );
  });
});
