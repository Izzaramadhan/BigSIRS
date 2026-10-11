import { flushPromises, mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import MedicineCategoriesView from '../MedicineCategoriesView.vue';
import { medicineCategoriesService } from '@/services/master-data/medicineCategories.service';

vi.mock('@/services/master-data/medicineCategories.service', () => ({
  medicineCategoriesService: {
    getMedicineCategories: vi.fn(),
    getMedicineCategory: vi.fn(),
    createMedicineCategory: vi.fn(),
    updateMedicineCategory: vi.fn(),
    deleteMedicineCategory: vi.fn()
  }
}));

const mockPaginatedResponse = {
  data: [
    {
      id: 1,
      name: 'Analgesik',
      description: 'Obat pereda nyeri'
    },
    {
      id: 2,
      name: 'Antibiotik',
      description: null
    }
  ],
  meta: { current_page: 1, last_page: 1, per_page: 10, total: 2 },
  links: {}
};

describe('MedicineCategoriesView.vue', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    medicineCategoriesService.getMedicineCategories.mockResolvedValue(mockPaginatedResponse);
  });

  const mountView = () =>
    mount(MedicineCategoriesView, {
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
    expect(wrapper.text()).toContain('Kategori Obat');
    expect(wrapper.text()).toContain('Kelola master data kategori obat.');

    // Breadcrumbs
    expect(wrapper.text()).toContain('Dashboard');
    expect(wrapper.text()).toContain('Master Data');
    expect(wrapper.text()).toContain('Logistik');
    expect(wrapper.text()).toContain('Kategori Obat');

    // Add button
    expect(wrapper.text()).toContain('Tambah Kategori Obat');

    // Table data
    expect(wrapper.text()).toContain('Analgesik');
    expect(wrapper.text()).toContain('Antibiotik');

    // Exact 4 table columns
    const ths = wrapper.findAll('thead th');
    expect(ths).toHaveLength(4);
    expect(ths[0].text()).toBe('No');
    expect(ths[1].text()).toContain('Nama');
    expect(ths[2].text()).toBe('Deskripsi');
    expect(ths[3].text()).toBe('Aksi');
  });

  it('shows empty state when data is empty', async () => {
    medicineCategoriesService.getMedicineCategories.mockResolvedValue({
      data: [],
      meta: { current_page: 1, last_page: 1, per_page: 10, total: 0 }
    });

    const wrapper = mountView();
    await flushPromises();

    expect(wrapper.text()).toContain('Belum ada data yang ditambahkan.');
  });

  it('handles add flow and shows exact success toast "Kategori obat berhasil ditambahkan."', async () => {
    medicineCategoriesService.createMedicineCategory.mockResolvedValue({
      data: { id: 3, name: 'Antasida', description: 'Obat lambung' }
    });

    const wrapper = mountView();
    await flushPromises();

    // Click "Tambah Kategori Obat"
    const addBtn = wrapper.find('.page-header button.btn-primary');
    await addBtn.trigger('click');

    // Check modal opened with Tambah title
    expect(wrapper.text()).toContain('Tambah Kategori Obat');

    // Fill form
    const formModal = wrapper.findComponent({ name: 'MedicineCategoryFormModal' });
    expect(formModal.exists()).toBe(true);

    const nameInput = formModal.find('input#name');
    const descInput = formModal.find('textarea#description');

    await nameInput.setValue('Antasida');
    await descInput.setValue('Obat lambung');

    // Submit form
    await formModal.find('form').trigger('submit');
    await flushPromises();

    expect(medicineCategoriesService.createMedicineCategory).toHaveBeenCalledWith({
      name: 'Antasida',
      description: 'Obat lambung'
    });

    // Check success toast
    expect(wrapper.text()).toContain('Kategori obat berhasil ditambahkan.');
  });

  it('handles edit flow and shows exact success toast "Kategori obat berhasil diperbarui."', async () => {
    medicineCategoriesService.updateMedicineCategory.mockResolvedValue({
      data: { id: 1, name: 'Analgesik Forte', description: 'Obat pereda nyeri kuat' }
    });

    const wrapper = mountView();
    await flushPromises();

    // Click edit on first row
    const editBtn = wrapper.findAll('tbody tr')[0].findAll('button')[0];
    await editBtn.trigger('click');

    expect(wrapper.text()).toContain('Edit Kategori Obat');

    const formModal = wrapper.findComponent({ name: 'MedicineCategoryFormModal' });
    const nameInput = formModal.find('input#name');
    await nameInput.setValue('Analgesik Forte');

    await formModal.find('form').trigger('submit');
    await flushPromises();

    expect(medicineCategoriesService.updateMedicineCategory).toHaveBeenCalledWith(1, {
      name: 'Analgesik Forte',
      description: 'Obat pereda nyeri'
    });

    expect(wrapper.text()).toContain('Kategori obat berhasil diperbarui.');
  });

  it('handles delete flow, shows confirmation with category name, and shows exact toast "Kategori obat berhasil dihapus."', async () => {
    medicineCategoriesService.deleteMedicineCategory.mockResolvedValue({ success: true });

    const wrapper = mountView();
    await flushPromises();

    // Click delete on first row (Analgesik)
    const deleteBtn = wrapper.findAll('tbody tr')[0].findAll('button')[1];
    await deleteBtn.trigger('click');

    // Delete dialog opens
    const deleteDialog = wrapper.findComponent({ name: 'MasterDataDeleteDialog' });
    expect(deleteDialog.exists()).toBe(true);
    expect(deleteDialog.props('isOpen')).toBe(true);
    expect(deleteDialog.props('itemName')).toBe('Analgesik');

    // Confirm delete
    deleteDialog.vm.$emit('confirm');
    await flushPromises();

    expect(medicineCategoriesService.deleteMedicineCategory).toHaveBeenCalledWith(1);
    expect(wrapper.text()).toContain('Kategori obat berhasil dihapus.');
  });

  it('adjusts pagination when deleting the only item on a subsequent page', async () => {
    medicineCategoriesService.getMedicineCategories.mockResolvedValue({
      data: [{ id: 5, name: 'Kategori Halaman 2', description: null }],
      meta: { current_page: 2, last_page: 2, per_page: 10, total: 11 }
    });
    medicineCategoriesService.deleteMedicineCategory.mockResolvedValue({ success: true });

    const wrapper = mountView();
    await flushPromises();

    const deleteBtn = wrapper.findAll('tbody tr')[0].findAll('button')[1];
    await deleteBtn.trigger('click');

    const deleteDialog = wrapper.findComponent({ name: 'MasterDataDeleteDialog' });
    deleteDialog.vm.$emit('confirm');
    await flushPromises();

    // Should fetch page 1 after deletion
    expect(medicineCategoriesService.getMedicineCategories).toHaveBeenLastCalledWith(
      expect.objectContaining({ page: 1 })
    );
  });
});
