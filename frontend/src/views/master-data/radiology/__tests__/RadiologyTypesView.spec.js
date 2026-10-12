import { flushPromises, mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import RadiologyTypesView from '../RadiologyTypesView.vue';
import { radiologyTypesService } from '@/services/master-data/radiologyTypes.service';

vi.mock('@/services/master-data/radiologyTypes.service', () => ({
  radiologyTypesService: {
    getAll: vi.fn(),
    getById: vi.fn(),
    create: vi.fn(),
    update: vi.fn(),
    delete: vi.fn(),
  },
}));

const mockPaginatedResponse = {
  data: {
    data: [
      {
        id: 1,
        name: 'Radiografi Konvensional',
        description: 'Pemeriksaan foto rontgen umum',
        is_active: true
      },
      {
        id: 2,
        name: 'Ultrasonografi',
        description: null,
        is_active: false
      }
    ],
    meta: { current_page: 1, last_page: 1, per_page: 10, total: 2 },
    links: {}
  }
};

describe('RadiologyTypesView.vue', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    radiologyTypesService.getAll.mockResolvedValue(mockPaginatedResponse);
  });

  const mountView = () =>
    mount(RadiologyTypesView, {
      global: {
        stubs: {
          Teleport: true,
        },
      },
    });

  it('renders page header, breadcrumbs, filters, and table with exact 4 columns: No, Tipe, Deskripsi, Aksi', async () => {
    const wrapper = mountView();
    await flushPromises();

    expect(wrapper.text()).toContain('Tipe Rad');
    expect(wrapper.text()).toContain('Kelola master data tipe radiologi.');
    expect(wrapper.text()).toContain('Radiografi Konvensional');
    expect(wrapper.text()).toContain('Ultrasonografi');

    // Breadcrumbs
    expect(wrapper.text()).toContain('Dashboard');
    expect(wrapper.text()).toContain('Master Data');
    expect(wrapper.text()).toContain('Data Radiologi');

    // Search input has placeholder with tipe radiologi
    const searchInput = wrapper.find('input[placeholder="Cari tipe radiologi..."]');
    expect(searchInput.exists()).toBe(true);

    // Verify status filter is NOT rendered
    expect(wrapper.find('.filter-controls').text()).not.toContain('Semua Status');

    // 4 Columns in exact order: No, Tipe, Deskripsi, Aksi
    const ths = wrapper.findAll('thead th');
    expect(ths.length).toBe(4);
    expect(ths[0].text()).toBe('No');
    expect(ths[1].text()).toBe('Tipe');
    expect(ths[2].text()).toBe('Deskripsi');
    expect(ths[3].text()).toBe('Aksi');

    // Verify null description renders '-'
    const rows = wrapper.findAll('tbody tr');
    expect(rows.length).toBe(2);
    expect(rows[1].find('.col-desc').text()).toBe('-');
  });

  it('shows empty state when no data exists', async () => {
    radiologyTypesService.getAll.mockResolvedValue({
      data: {
        data: [],
        meta: { current_page: 1, last_page: 1, per_page: 10, total: 0 }
      }
    });

    const wrapper = mountView();
    await flushPromises();

    expect(wrapper.text()).toContain('Belum ada data yang ditambahkan.');
    expect(wrapper.text()).toContain('Tambah Item Rad');
  });

  it('validates client side required field for Tipe', async () => {
    const wrapper = mountView();
    await flushPromises();

    // Click "Tambah Item Rad"
    const addBtn = wrapper.find('button.btn-primary');
    await addBtn.trigger('click');
    await flushPromises();

    expect(wrapper.text()).toContain('Tambah Item Radiologi');

    // Submit empty
    await wrapper.find('form.modal-form-wrapper').trigger('submit');
    await flushPromises();

    expect(wrapper.text()).toContain('Tipe radiologi wajib diisi.');
    expect(radiologyTypesService.create).not.toHaveBeenCalled();
  });

  it('handles add workflow with status Aktif and single success toast notification', async () => {
    radiologyTypesService.create.mockResolvedValue({
      data: {
        data: { id: 3, name: 'CT Scan', description: 'CT Scan', is_active: true }
      }
    });

    const wrapper = mountView();
    await flushPromises();

    // Click "Tambah Kelompok Item"
    const addBtn = wrapper.find('button.btn-primary');
    await addBtn.trigger('click');
    await flushPromises();

    expect(wrapper.text()).toContain('Tambah Item Radiologi');

    // Fill form
    await wrapper.find('input#name').setValue('CT Scan Kepala');
    await wrapper.find('textarea#description').setValue('Pemeriksaan CT scan kepala');
    await wrapper.find('form.modal-form-wrapper').trigger('submit');
    await flushPromises();

    expect(radiologyTypesService.create).toHaveBeenCalledWith(
      expect.objectContaining({
        name: 'CT Scan Kepala',
        description: 'Pemeriksaan CT scan kepala',
        is_active: true
      })
    );

    // Toast message exact match
    expect(wrapper.text()).toContain('Item radiologi berhasil ditambahkan.');
  });

  it('handles add workflow with status Nonaktif', async () => {
    radiologyTypesService.create.mockResolvedValue({
      data: {
        data: { id: 4, name: 'Kedokteran Nuklir', description: null, is_active: false }
      }
    });

    const wrapper = mountView();
    await flushPromises();

    // Click "Tambah Kelompok Item"
    await wrapper.find('button.btn-primary').trigger('click');
    await flushPromises();

    // Fill form and select Nonaktif
    await wrapper.find('input#name').setValue('Kedokteran Nuklir');
    await wrapper.find('input#status_inactive').setValue(true);
    await wrapper.find('form.modal-form-wrapper').trigger('submit');
    await flushPromises();

    expect(radiologyTypesService.create).toHaveBeenCalledWith(
      expect.objectContaining({ name: 'Kedokteran Nuklir', is_active: false })
    );

    expect(wrapper.text()).toContain('Item radiologi berhasil ditambahkan.');
  });

  it('handles edit workflow with status change from Aktif to Nonaktif', async () => {
    radiologyTypesService.update.mockResolvedValue({
      data: {
        data: { id: 1, name: 'Radiografi Konvensional Updated', description: 'Updated', is_active: false }
      }
    });

    const wrapper = mountView();
    await flushPromises();

    // Click edit button in table for row 1
    const editBtn = wrapper.findAll('.btn-action.edit')[0];
    await editBtn.trigger('click');
    await flushPromises();

    expect(wrapper.text()).toContain('Edit Item Radiologi');
    expect(wrapper.find('input#name').element.value).toBe('Radiografi Konvensional');
    expect(wrapper.find('input#status_active').element.checked).toBe(true);

    // Update name and change status to Nonaktif
    await wrapper.find('input#name').setValue('Radiografi Konvensional Updated');
    await wrapper.find('input#status_inactive').setValue(true);
    await wrapper.find('form.modal-form-wrapper').trigger('submit');
    await flushPromises();

    expect(radiologyTypesService.update).toHaveBeenCalledWith(
      1,
      expect.objectContaining({ name: 'Radiografi Konvensional Updated', is_active: false })
    );

    // Toast message exact match
    expect(wrapper.text()).toContain('Item radiologi berhasil diperbarui.');
  });

  it('handles edit workflow reading initial Nonaktif status correctly', async () => {
    const wrapper = mountView();
    await flushPromises();

    // Click edit button in table for row 2 (Ultrasonografi which has is_active: false)
    const editBtns = wrapper.findAll('.btn-action.edit');
    await editBtns[1].trigger('click');
    await flushPromises();

    expect(wrapper.text()).toContain('Edit Item Radiologi');
    expect(wrapper.find('input#name').element.value).toBe('Ultrasonografi');
    expect(wrapper.find('input#status_inactive').element.checked).toBe(true);
    expect(wrapper.find('input#status_active').element.checked).toBe(false);
  });

  it('keeps modal open and displays error when submission fails with 422', async () => {
    radiologyTypesService.create.mockRejectedValue({
      response: {
        status: 422,
        data: {
          message: 'The name has already been taken.',
          errors: {
            name: ['Kelompok item radiologi sudah digunakan.']
          }
        }
      }
    });

    const wrapper = mountView();
    await flushPromises();

    // Click "Tambah Kelompok Item"
    await wrapper.find('button.btn-primary').trigger('click');
    await flushPromises();

    await wrapper.find('input#name').setValue('Radiografi Konvensional');
    await wrapper.find('form.modal-form-wrapper').trigger('submit');
    await flushPromises();

    // Modal stays open
    expect(wrapper.text()).toContain('Tambah Item Radiologi');
    expect(wrapper.text()).toContain('Kelompok item radiologi sudah digunakan.');
    // No success notification
    expect(wrapper.text()).not.toContain('Item radiologi berhasil ditambahkan.');
  });

  it('handles delete workflow with confirmation dialog and single success toast', async () => {
    radiologyTypesService.delete.mockResolvedValue({});

    const wrapper = mountView();
    await flushPromises();

    // Click delete button
    const deleteBtn = wrapper.find('.btn-action.delete');
    await deleteBtn.trigger('click');
    await flushPromises();

    // Confirm dialog is shown with item name
    expect(wrapper.text()).toContain('Hapus Item Radiologi');
    expect(wrapper.text()).toContain('Radiografi Konvensional');

    // Confirm delete
    const confirmBtn = wrapper.find('.btn-danger');
    await confirmBtn.trigger('click');
    await flushPromises();

    expect(radiologyTypesService.delete).toHaveBeenCalledWith(1);
    expect(wrapper.text()).toContain('Item radiologi berhasil dihapus.');
  });
});
