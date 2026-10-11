import { flushPromises, mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import RadiologyItemsView from '../RadiologyItemsView.vue';
import { radiologyItemsService } from '@/services/master-data/radiologyItems.service';
import axios from '@/utils/axios';

vi.mock('@/services/master-data/radiologyItems.service', () => ({
  radiologyItemsService: {
    getItems: vi.fn(),
    getItem: vi.fn(),
    createItem: vi.fn(),
    updateItem: vi.fn(),
    deleteItem: vi.fn(),
    getAll: vi.fn(),
    getById: vi.fn(),
    create: vi.fn(),
    update: vi.fn(),
    delete: vi.fn(),
  },
}));

vi.mock('@/utils/axios', () => ({
  default: {
    get: vi.fn().mockResolvedValue({
      data: {
        data: [
          { id: 10, name: 'Foto Rontgen Tanpa Bahan Kontras', is_active: true },
          { id: 20, name: 'Foto Rontgen Dengan Bahan Kontras', is_active: true }
        ]
      }
    }),
  },
}));

const mockPaginatedResponse = {
  data: [
    {
      id: 1,
      name: 'Rontgen Thorax PA',
      radiology_item_group_id: 10,
      is_active: true,
      group: { id: 10, name: 'Foto Rontgen Tanpa Bahan Kontras' }
    },
    {
      id: 2,
      name: 'Rontgen BNO-IVP',
      radiology_item_group_id: 20,
      is_active: false,
      group: { id: 20, name: 'Foto Rontgen Dengan Bahan Kontras' }
    }
  ],
  meta: { current_page: 1, last_page: 1, per_page: 10, total: 2 },
  links: {}
};

describe('RadiologyItemsView.vue', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    radiologyItemsService.getItems.mockResolvedValue(mockPaginatedResponse);
  });

  const mountView = () =>
    mount(RadiologyItemsView, {
      global: {
        stubs: {
          Teleport: true,
        },
      },
    });

  it('renders page header, breadcrumbs, filters, and table with exact 4 columns: No, Item, Kelompok Item, Aksi', async () => {
    const wrapper = mountView();
    await flushPromises();

    expect(wrapper.text()).toContain('Item Rad');
    expect(wrapper.text()).toContain('Kelola master data item radiologi.');
    expect(wrapper.text()).toContain('Rontgen Thorax PA');
    expect(wrapper.text()).toContain('Foto Rontgen Tanpa Bahan Kontras');
    expect(wrapper.text()).toContain('Rontgen BNO-IVP');

    // Breadcrumbs
    expect(wrapper.text()).toContain('Dashboard');
    expect(wrapper.text()).toContain('Master Data');
    expect(wrapper.text()).toContain('Data Radiologi');

    // Search input has placeholder with item radiologi
    const searchInput = wrapper.find('input[placeholder="Cari item radiologi..."]');
    expect(searchInput.exists()).toBe(true);

    // 4 Columns in exact order: No, Item, Kelompok Item, Aksi
    const ths = wrapper.findAll('thead th');
    expect(ths.length).toBe(4);
    expect(ths[0].text()).toBe('No');
    expect(ths[1].text()).toBe('Item');
    expect(ths[2].text()).toBe('Kelompok Item');
    expect(ths[3].text()).toBe('Aksi');
  });

  it('shows empty state when no data exists', async () => {
    radiologyItemsService.getItems.mockResolvedValue({
      data: [],
      meta: { current_page: 1, last_page: 1, per_page: 10, total: 0 }
    });

    const wrapper = mountView();
    await flushPromises();

    expect(wrapper.text()).toContain('Belum ada data yang ditambahkan.');
    expect(wrapper.text()).toContain('Tambah Item Rad');
  });

  it('validates client side required fields on create', async () => {
    const wrapper = mountView();
    await flushPromises();

    // Click "Tambah Item Rad"
    const addBtn = wrapper.find('button.btn-primary');
    await addBtn.trigger('click');
    await flushPromises();

    expect(wrapper.text()).toContain('Tambah Item Radiologi');

    // Submit empty form
    await wrapper.find('form.modal-form-wrapper').trigger('submit');
    await flushPromises();

    expect(wrapper.text()).toContain('Nama item radiologi wajib diisi.');
    expect(wrapper.text()).toContain('Kelompok item radiologi wajib dipilih.');
    expect(radiologyItemsService.createItem).not.toHaveBeenCalled();
  });

  it('handles add workflow with status Aktif and single success toast notification', async () => {
    radiologyItemsService.createItem.mockResolvedValue({
      data: {
        data: { id: 3, name: 'Rontgen Pelvis', radiology_item_group_id: 10, is_active: true }
      }
    });

    const wrapper = mountView();
    await flushPromises();

    // Click "Tambah Item Rad"
    const addBtn = wrapper.find('button.btn-primary');
    await addBtn.trigger('click');
    await flushPromises();

    expect(wrapper.text()).toContain('Tambah Item Radiologi');

    // Fill form
    await wrapper.find('input#name').setValue('Rontgen Pelvis');
    await wrapper.find('select#radiology_item_group_id').setValue('10');
    await wrapper.find('form.modal-form-wrapper').trigger('submit');
    await flushPromises();

    expect(radiologyItemsService.createItem).toHaveBeenCalledWith(
      expect.objectContaining({
        name: 'Rontgen Pelvis',
        radiology_item_group_id: 10,
        is_active: true
      })
    );

    // Toast message exact match
    expect(wrapper.text()).toContain('Item radiologi berhasil ditambahkan.');
  });

  it('handles add workflow with status Nonaktif', async () => {
    radiologyItemsService.createItem.mockResolvedValue({
      data: {
        data: { id: 4, name: 'Item Inaktif', radiology_item_group_id: 20, is_active: false }
      }
    });

    const wrapper = mountView();
    await flushPromises();

    // Click "Tambah Item Rad"
    await wrapper.find('button.btn-primary').trigger('click');
    await flushPromises();

    // Fill form and select Nonaktif
    await wrapper.find('input#name').setValue('Item Inaktif');
    await wrapper.find('select#radiology_item_group_id').setValue('20');
    await wrapper.find('input#status_inactive').setValue(true);
    await wrapper.find('form.modal-form-wrapper').trigger('submit');
    await flushPromises();

    expect(radiologyItemsService.createItem).toHaveBeenCalledWith(
      expect.objectContaining({
        name: 'Item Inaktif',
        radiology_item_group_id: 20,
        is_active: false
      })
    );

    expect(wrapper.text()).toContain('Item radiologi berhasil ditambahkan.');
  });

  it('handles edit workflow with status change from Aktif to Nonaktif', async () => {
    radiologyItemsService.updateItem.mockResolvedValue({
      data: {
        data: { id: 1, name: 'Rontgen Thorax PA Updated', radiology_item_group_id: 20, is_active: false }
      }
    });

    const wrapper = mountView();
    await flushPromises();

    // Click edit button in table for row 1
    const editBtn = wrapper.findAll('.btn-action.edit')[0];
    await editBtn.trigger('click');
    await flushPromises();

    expect(wrapper.text()).toContain('Edit Item Radiologi');
    expect(wrapper.find('input#name').element.value).toBe('Rontgen Thorax PA');
    expect(wrapper.find('select#radiology_item_group_id').element.value).toBe('10');
    expect(wrapper.find('input#status_active').element.checked).toBe(true);

    // Update name and change status to Nonaktif and group to 20
    await wrapper.find('input#name').setValue('Rontgen Thorax PA Updated');
    await wrapper.find('select#radiology_item_group_id').setValue('20');
    await wrapper.find('input#status_inactive').setValue(true);
    await wrapper.find('form.modal-form-wrapper').trigger('submit');
    await flushPromises();

    expect(radiologyItemsService.updateItem).toHaveBeenCalledWith(
      1,
      expect.objectContaining({
        name: 'Rontgen Thorax PA Updated',
        radiology_item_group_id: 20,
        is_active: false
      })
    );

    // Toast message exact match
    expect(wrapper.text()).toContain('Item radiologi berhasil diperbarui.');
  });

  it('handles edit workflow reading initial Nonaktif status correctly', async () => {
    const wrapper = mountView();
    await flushPromises();

    // Click edit button in table for row 2 (Rontgen BNO-IVP which has is_active: false)
    const editBtns = wrapper.findAll('.btn-action.edit');
    await editBtns[1].trigger('click');
    await flushPromises();

    expect(wrapper.text()).toContain('Edit Item Radiologi');
    expect(wrapper.find('input#name').element.value).toBe('Rontgen BNO-IVP');
    expect(wrapper.find('input#status_inactive').element.checked).toBe(true);
    expect(wrapper.find('input#status_active').element.checked).toBe(false);
  });

  it('keeps modal open and displays error when submission fails with 422', async () => {
    radiologyItemsService.createItem.mockRejectedValue({
      response: {
        status: 422,
        data: {
          message: 'The name has already been taken.',
          errors: {
            name: ['Nama item radiologi sudah digunakan.']
          }
        }
      }
    });

    const wrapper = mountView();
    await flushPromises();

    // Click "Tambah Item Rad"
    await wrapper.find('button.btn-primary').trigger('click');
    await flushPromises();

    await wrapper.find('input#name').setValue('Rontgen Thorax PA');
    await wrapper.find('select#radiology_item_group_id').setValue('10');
    await wrapper.find('form.modal-form-wrapper').trigger('submit');
    await flushPromises();

    // Modal stays open
    expect(wrapper.text()).toContain('Tambah Item Radiologi');
    expect(wrapper.text()).toContain('Nama item radiologi sudah digunakan.');
    // No success notification
    expect(wrapper.text()).not.toContain('Item radiologi berhasil ditambahkan.');
  });

  it('handles delete workflow with confirmation dialog and single success toast', async () => {
    radiologyItemsService.deleteItem.mockResolvedValue({});

    const wrapper = mountView();
    await flushPromises();

    // Click delete button
    const deleteBtn = wrapper.find('.btn-action.delete');
    await deleteBtn.trigger('click');
    await flushPromises();

    // Confirm dialog is shown with item name
    expect(wrapper.text()).toContain('Hapus Item Radiologi');
    expect(wrapper.text()).toContain('Rontgen Thorax PA');

    // Confirm delete
    const confirmBtn = wrapper.find('.btn-danger');
    await confirmBtn.trigger('click');
    await flushPromises();

    expect(radiologyItemsService.deleteItem).toHaveBeenCalledWith(1);
    expect(wrapper.text()).toContain('Item radiologi berhasil dihapus.');
  });
});
