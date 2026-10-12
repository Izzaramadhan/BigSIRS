import { flushPromises, mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import RadiologyItemGroupsView from '../RadiologyItemGroupsView.vue';
import { radiologyItemGroupsService } from '@/services/master-data/radiologyItemGroups.service';

vi.mock('@/services/master-data/radiologyItemGroups.service', () => ({
  radiologyItemGroupsService: {
    getGroups: vi.fn(),
    getAll: vi.fn(),
    getById: vi.fn(),
    createGroup: vi.fn(),
    create: vi.fn(),
    updateGroup: vi.fn(),
    update: vi.fn(),
    deleteGroup: vi.fn(),
    delete: vi.fn(),
  },
}));

const mockPaginatedResponse = {
  data: [
    {
      id: 1,
      name: 'Foto Polos',
      description: 'Pemeriksaan foto rontgen polos',
      is_active: true
    },
    {
      id: 2,
      name: 'Pemeriksaan Kontras',
      description: null,
      is_active: false
    }
  ],
  meta: { current_page: 1, last_page: 1, per_page: 10, total: 2 },
  links: {}
};

describe('RadiologyItemGroupsView.vue', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    radiologyItemGroupsService.getGroups.mockResolvedValue(mockPaginatedResponse);
  });

  const mountView = () =>
    mount(RadiologyItemGroupsView, {
      global: {
        stubs: {
          Teleport: true,
        },
      },
    });

  it('renders page header, breadcrumbs, filters, and table with exact 4 columns: No, Kelompok Item, Deskripsi, Aksi', async () => {
    const wrapper = mountView();
    await flushPromises();

    expect(wrapper.text()).toContain('Kelompok Item Rad');
    expect(wrapper.text()).toContain('Kelola master data kelompok item radiologi.');
    expect(wrapper.text()).toContain('Foto Polos');
    expect(wrapper.text()).toContain('Pemeriksaan Kontras');

    // Breadcrumbs
    expect(wrapper.text()).toContain('Dashboard');
    expect(wrapper.text()).toContain('Master Data');
    expect(wrapper.text()).toContain('Data Radiologi');

    // Search input has placeholder with kelompok item radiologi
    const searchInput = wrapper.find('input[placeholder="Cari kelompok item radiologi..."]');
    expect(searchInput.exists()).toBe(true);

    // Verify status filter is NOT rendered
    expect(wrapper.find('.filter-controls').text()).not.toContain('Semua Status');

    // 4 Columns in exact order: No, Kelompok Item, Deskripsi, Aksi
    const ths = wrapper.findAll('thead th');
    expect(ths.length).toBe(4);
    expect(ths[0].text()).toBe('No');
    expect(ths[1].text()).toBe('Kelompok Item');
    expect(ths[2].text()).toBe('Deskripsi');
    expect(ths[3].text()).toBe('Aksi');

    // Verify null description renders '-'
    const rows = wrapper.findAll('tbody tr');
    expect(rows.length).toBe(2);
    expect(rows[1].find('.col-desc').text()).toBe('-');
  });

  it('shows empty state when no data exists', async () => {
    radiologyItemGroupsService.getGroups.mockResolvedValue({
      data: [],
      meta: { current_page: 1, last_page: 1, per_page: 10, total: 0 }
    });

    const wrapper = mountView();
    await flushPromises();

    expect(wrapper.text()).toContain('Belum ada data yang ditambahkan.');
    expect(wrapper.text()).toContain('Tambah Kelompok Item Rad');
  });

  it('validates client side required field for Kelompok Item', async () => {
    const wrapper = mountView();
    await flushPromises();

    // Click "Tambah Kelompok Item Rad"
    const addBtn = wrapper.find('button.btn-primary');
    await addBtn.trigger('click');
    await flushPromises();

    expect(wrapper.text()).toContain('Tambah Kelompok Item Radiologi');

    // Submit empty
    await wrapper.find('form.modal-form-wrapper').trigger('submit');
    await flushPromises();

    expect(wrapper.text()).toContain('Kelompok item radiologi wajib diisi.');
    expect(radiologyItemGroupsService.createGroup).not.toHaveBeenCalled();
  });

  it('handles add workflow with status Aktif and single success toast notification', async () => {
    radiologyItemGroupsService.createGroup.mockResolvedValue({
      data: { id: 3, name: 'CT Scan', description: 'CT Scan', is_active: true }
    });

    const wrapper = mountView();
    await flushPromises();

    // Click "Tambah Kelompok Item Rad"
    const addBtn = wrapper.find('button.btn-primary');
    await addBtn.trigger('click');
    await flushPromises();

    expect(wrapper.text()).toContain('Tambah Kelompok Item Radiologi');

    // Fill form
    await wrapper.find('input#name').setValue('CT Scan Kepala');
    await wrapper.find('textarea#description').setValue('Pemeriksaan CT scan kepala');
    await wrapper.find('form.modal-form-wrapper').trigger('submit');
    await flushPromises();

    expect(radiologyItemGroupsService.createGroup).toHaveBeenCalledWith(
      expect.objectContaining({
        name: 'CT Scan Kepala',
        description: 'Pemeriksaan CT scan kepala',
        is_active: true
      })
    );

    // Toast message exact match
    expect(wrapper.text()).toContain('Kelompok item radiologi berhasil ditambahkan.');
  });

  it('handles add workflow with status Nonaktif', async () => {
    radiologyItemGroupsService.createGroup.mockResolvedValue({
      data: { id: 4, name: 'Kedokteran Nuklir', description: null, is_active: false }
    });

    const wrapper = mountView();
    await flushPromises();

    // Click "Tambah Kelompok Item Rad"
    await wrapper.find('button.btn-primary').trigger('click');
    await flushPromises();

    // Fill form and select Nonaktif
    await wrapper.find('input#name').setValue('Kedokteran Nuklir');
    await wrapper.find('input#status_inactive').setValue(true);
    await wrapper.find('form.modal-form-wrapper').trigger('submit');
    await flushPromises();

    expect(radiologyItemGroupsService.createGroup).toHaveBeenCalledWith(
      expect.objectContaining({ name: 'Kedokteran Nuklir', is_active: false })
    );

    expect(wrapper.text()).toContain('Kelompok item radiologi berhasil ditambahkan.');
  });

  it('handles edit workflow with status change from Aktif to Nonaktif', async () => {
    radiologyItemGroupsService.updateGroup.mockResolvedValue({
      data: { id: 1, name: 'Foto Polos Updated', description: 'Updated', is_active: false }
    });

    const wrapper = mountView();
    await flushPromises();

    // Click edit button in table for row 1
    const editBtn = wrapper.findAll('.btn-action.edit')[0];
    await editBtn.trigger('click');
    await flushPromises();

    expect(wrapper.text()).toContain('Edit Kelompok Item Radiologi');
    expect(wrapper.find('input#name').element.value).toBe('Foto Polos');
    expect(wrapper.find('input#status_active').element.checked).toBe(true);

    // Update name and change status to Nonaktif
    await wrapper.find('input#name').setValue('Foto Polos Updated');
    await wrapper.find('input#status_inactive').setValue(true);
    await wrapper.find('form.modal-form-wrapper').trigger('submit');
    await flushPromises();

    expect(radiologyItemGroupsService.updateGroup).toHaveBeenCalledWith(
      1,
      expect.objectContaining({ name: 'Foto Polos Updated', is_active: false })
    );

    // Toast message exact match
    expect(wrapper.text()).toContain('Kelompok item radiologi berhasil diperbarui.');
  });

  it('handles edit workflow reading initial Nonaktif status correctly', async () => {
    const wrapper = mountView();
    await flushPromises();

    // Click edit button in table for row 2 (Pemeriksaan Kontras which has is_active: false)
    const editBtns = wrapper.findAll('.btn-action.edit');
    await editBtns[1].trigger('click');
    await flushPromises();

    expect(wrapper.text()).toContain('Edit Kelompok Item Radiologi');
    expect(wrapper.find('input#name').element.value).toBe('Pemeriksaan Kontras');
    expect(wrapper.find('input#status_inactive').element.checked).toBe(true);
    expect(wrapper.find('input#status_active').element.checked).toBe(false);
  });

  it('keeps modal open and displays error when submission fails with 422', async () => {
    radiologyItemGroupsService.createGroup.mockRejectedValue({
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

    // Click "Tambah Kelompok Item Rad"
    await wrapper.find('button.btn-primary').trigger('click');
    await flushPromises();

    await wrapper.find('input#name').setValue('Foto Polos');
    await wrapper.find('form.modal-form-wrapper').trigger('submit');
    await flushPromises();

    // Modal stays open
    expect(wrapper.text()).toContain('Tambah Kelompok Item Radiologi');
    expect(wrapper.text()).toContain('Kelompok item radiologi sudah digunakan.');
    // No success notification
    expect(wrapper.text()).not.toContain('Kelompok item radiologi berhasil ditambahkan.');
  });

  it('handles delete workflow with confirmation dialog and single success toast', async () => {
    radiologyItemGroupsService.deleteGroup.mockResolvedValue({});

    const wrapper = mountView();
    await flushPromises();

    // Click delete button
    const deleteBtn = wrapper.find('.btn-action.delete');
    await deleteBtn.trigger('click');
    await flushPromises();

    // Confirm dialog is shown with item name
    expect(wrapper.text()).toContain('Hapus Kelompok Item Radiologi');
    expect(wrapper.text()).toContain('Foto Polos');

    // Confirm delete
    const confirmBtn = wrapper.find('.btn-danger');
    await confirmBtn.trigger('click');
    await flushPromises();

    expect(radiologyItemGroupsService.deleteGroup).toHaveBeenCalledWith(1);
    expect(wrapper.text()).toContain('Kelompok item radiologi berhasil dihapus.');
  });
});
