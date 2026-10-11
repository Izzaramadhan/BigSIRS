import { flushPromises, mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import MedicationSignasView from '../MedicationSignasView.vue';
import { medicationSignasService } from '@/services/master-data/medicationSignas.service';

vi.mock('@/services/master-data/medicationSignas.service', () => ({
  medicationSignasService: {
    getMedicationSignas: vi.fn(),
    getMedicationSigna: vi.fn(),
    createMedicationSigna: vi.fn(),
    updateMedicationSigna: vi.fn(),
    deleteMedicationSigna: vi.fn()
  }
}));

const mockPaginatedResponse = {
  data: [
    {
      id: 1,
      name: '3 x 1 sehari'
    },
    {
      id: 2,
      name: '1 x 1 sehari sesudah makan'
    }
  ],
  meta: { current_page: 1, last_page: 1, per_page: 10, total: 2 },
  links: {}
};

describe('MedicationSignasView.vue', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    medicationSignasService.getMedicationSignas.mockResolvedValue(mockPaginatedResponse);
  });

  const mountView = () =>
    mount(MedicationSignasView, {
      global: {
        stubs: {
          Teleport: true
        }
      }
    });

  it('renders page header, breadcrumbs, add button, and table with 3 columns', async () => {
    const wrapper = mountView();
    await flushPromises();

    // Title and description
    expect(wrapper.text()).toContain('Signa Obat');
    expect(wrapper.text()).toContain('Kelola master data signa obat.');

    // Breadcrumbs
    expect(wrapper.text()).toContain('Dashboard');
    expect(wrapper.text()).toContain('Master Data');
    expect(wrapper.text()).toContain('Logistik');

    // Add button
    expect(wrapper.text()).toContain('Tambah Signa Obat');

    // Table data
    expect(wrapper.text()).toContain('3 x 1 sehari');
    expect(wrapper.text()).toContain('1 x 1 sehari sesudah makan');

    // Exact 3 table columns
    const ths = wrapper.findAll('thead th');
    expect(ths).toHaveLength(3);
    expect(ths[0].text()).toBe('No');
    expect(ths[1].text()).toContain('Nama');
    expect(ths[2].text()).toBe('Aksi');
  });

  it('shows empty state when data is empty', async () => {
    medicationSignasService.getMedicationSignas.mockResolvedValue({
      data: [],
      meta: { current_page: 1, last_page: 1, per_page: 10, total: 0 }
    });

    const wrapper = mountView();
    await flushPromises();

    expect(wrapper.text()).toContain('Belum ada data yang ditambahkan.');
  });

  it('handles add flow and shows exact success toast "Signa obat berhasil ditambahkan."', async () => {
    medicationSignasService.createMedicationSigna.mockResolvedValue({
      data: { id: 3, name: '2 x 1 sendok teh' }
    });

    const wrapper = mountView();
    await flushPromises();

    // Click "Tambah Signa Obat"
    const addBtn = wrapper.find('.page-header button.btn-primary');
    await addBtn.trigger('click');

    // Check modal opened with Tambah title
    expect(wrapper.text()).toContain('Tambah Signa Obat');

    // Fill form
    const formModal = wrapper.findComponent({ name: 'MedicationSignaFormModal' });
    expect(formModal.exists()).toBe(true);

    const nameInput = formModal.find('input#name');
    await nameInput.setValue('2 x 1 sendok teh');

    // Submit form
    await formModal.find('form').trigger('submit');
    await flushPromises();

    expect(medicationSignasService.createMedicationSigna).toHaveBeenCalledWith({
      name: '2 x 1 sendok teh'
    });

    // Check success toast
    expect(wrapper.text()).toContain('Signa obat berhasil ditambahkan.');
  });

  it('handles edit flow and shows exact success toast "Signa obat berhasil diperbarui."', async () => {
    medicationSignasService.updateMedicationSigna.mockResolvedValue({
      data: { id: 1, name: '3 x 1 sehari sebelum makan' }
    });

    const wrapper = mountView();
    await flushPromises();

    // Click edit on first row
    const editBtn = wrapper.findAll('tbody tr')[0].findAll('button')[0];
    await editBtn.trigger('click');

    expect(wrapper.text()).toContain('Edit Signa Obat');

    const formModal = wrapper.findComponent({ name: 'MedicationSignaFormModal' });
    const nameInput = formModal.find('input#name');
    await nameInput.setValue('3 x 1 sehari sebelum makan');

    await formModal.find('form').trigger('submit');
    await flushPromises();

    expect(medicationSignasService.updateMedicationSigna).toHaveBeenCalledWith(
      1,
      expect.objectContaining({
        name: '3 x 1 sehari sebelum makan'
      })
    );

    expect(wrapper.text()).toContain('Signa obat berhasil diperbarui.');
  });

  it('handles delete flow, shows confirmation with signa name, and shows exact toast "Signa obat berhasil dihapus."', async () => {
    medicationSignasService.deleteMedicationSigna.mockResolvedValue({ success: true });

    const wrapper = mountView();
    await flushPromises();

    // Click delete on first row (3 x 1 sehari)
    const deleteBtn = wrapper.findAll('tbody tr')[0].findAll('button')[1];
    await deleteBtn.trigger('click');

    // Delete dialog opens
    const deleteDialog = wrapper.findComponent({ name: 'MasterDataDeleteDialog' });
    expect(deleteDialog.exists()).toBe(true);
    expect(deleteDialog.props('isOpen')).toBe(true);
    expect(deleteDialog.props('itemName')).toBe('3 x 1 sehari');

    // Confirm delete
    deleteDialog.vm.$emit('confirm');
    await flushPromises();

    expect(medicationSignasService.deleteMedicationSigna).toHaveBeenCalledWith(1);
    expect(wrapper.text()).toContain('Signa obat berhasil dihapus.');
  });

  it('handles delete error and shows error toast', async () => {
    const errorResponse = {
      response: {
        status: 422,
        data: {
          message: 'Signa obat tidak dapat dihapus karena sedang digunakan dalam resep aktif.'
        }
      }
    };
    medicationSignasService.deleteMedicationSigna.mockRejectedValue(errorResponse);

    const wrapper = mountView();
    await flushPromises();

    // Click delete on first row
    const deleteBtn = wrapper.findAll('tbody tr')[0].findAll('button')[1];
    await deleteBtn.trigger('click');

    const deleteDialog = wrapper.findComponent({ name: 'MasterDataDeleteDialog' });
    deleteDialog.vm.$emit('confirm');
    await flushPromises();

    expect(wrapper.text()).toContain('Signa obat tidak dapat dihapus karena sedang digunakan dalam resep aktif.');
  });

  it('adjusts pagination when deleting the only item on a subsequent page', async () => {
    medicationSignasService.getMedicationSignas.mockResolvedValue({
      data: [{ id: 5, name: 'Signa Halaman 2' }],
      meta: { current_page: 2, last_page: 2, per_page: 10, total: 11 }
    });
    medicationSignasService.deleteMedicationSigna.mockResolvedValue({ success: true });

    const wrapper = mountView();
    await flushPromises();

    const deleteBtn = wrapper.findAll('tbody tr')[0].findAll('button')[1];
    await deleteBtn.trigger('click');

    const deleteDialog = wrapper.findComponent({ name: 'MasterDataDeleteDialog' });
    deleteDialog.vm.$emit('confirm');
    await flushPromises();

    // Should fetch page 1 after deletion
    expect(medicationSignasService.getMedicationSignas).toHaveBeenLastCalledWith(
      expect.objectContaining({ page: 1 })
    );
  });
});
