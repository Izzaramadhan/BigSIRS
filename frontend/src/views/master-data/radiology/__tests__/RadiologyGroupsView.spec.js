import { flushPromises, mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import RadiologyGroupsView from '../RadiologyGroupsView.vue';
import { radiologyGroupsService } from '@/services/master-data/radiologyGroups.service';
import axios from '@/utils/axios';

vi.mock('@/services/master-data/radiologyGroups.service', () => ({
  radiologyGroupsService: {
    getAll: vi.fn(),
    getById: vi.fn(),
    create: vi.fn(),
    update: vi.fn(),
    delete: vi.fn(),
  },
}));

vi.mock('@/utils/axios', () => ({
  default: {
    get: vi.fn((url) => {
      if (url.includes('radiology-categories')) {
        return Promise.resolve({ data: [{ id: 1, name: 'Radiografi Konvensional' }] });
      }
      if (url.includes('radiology-types')) {
        return Promise.resolve({ data: [{ id: 2, name: 'Foto Polos' }] });
      }
      if (url.includes('activity-types')) {
        return Promise.resolve({ data: [{ id: 3, name: 'Kegiatan Radiologi' }] });
      }
      if (url.includes('radiology-item-groups')) {
        return Promise.resolve({ data: [{ id: 4, name: 'Thorax' }] });
      }
      return Promise.resolve({ data: [] });
    }),
  },
}));

const mockPaginatedResponse = {
  data: {
    data: [
      {
        id: 1,
        name: 'Rontgen Thorax Standar',
        radiology_type_id: 2,
        radiology_category_id: 1,
        activity_type_id: 3,
        price: 150000,
        interpretation_price: 50000,
        is_active: true,
        loinc_code: '24606-6',
        loinc_url: 'https://loinc.org/24606-6/',
        type: { id: 2, name: 'Foto Polos' },
        category: { id: 1, name: 'Radiografi Konvensional' },
        item_groups: [{ id: 4, name: 'Thorax' }]
      },
      {
        id: 2,
        name: 'USG Abdomen',
        radiology_type_id: null,
        radiology_category_id: null,
        activity_type_id: null,
        price: 0,
        interpretation_price: 0,
        is_active: false,
        loinc_code: null,
        loinc_url: null,
        type: null,
        category: null,
        item_groups: []
      }
    ],
    meta: { current_page: 1, last_page: 1, per_page: 10, total: 2 },
  }
};

describe('RadiologyGroupsView.vue', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    radiologyGroupsService.getAll.mockResolvedValue(mockPaginatedResponse);
  });

  const mountView = () =>
    mount(RadiologyGroupsView, {
      global: {
        stubs: {
          Teleport: true,
        },
      },
    });

  it('renders page header, breadcrumbs, filters, and table with exact 11 columns', async () => {
    const wrapper = mountView();
    await flushPromises();

    expect(wrapper.text()).toContain('Grup Rad');
    expect(wrapper.text()).toContain('Kelola master data grup radiologi.');
    expect(wrapper.text()).toContain('Rontgen Thorax Standar');
    expect(wrapper.text()).toContain('USG Abdomen');

    // Breadcrumbs
    expect(wrapper.text()).toContain('Dashboard');
    expect(wrapper.text()).toContain('Master Data');
    expect(wrapper.text()).toContain('Data Radiologi');

    // Search input has placeholder
    const searchInput = wrapper.find('input[placeholder="Cari grup radiologi..."]');
    expect(searchInput.exists()).toBe(true);

    // 11 Columns in exact order: No, Grup, Tipe, Kategori, Item Pemeriksaan, Harga, Harga Interpretasi, Status, Loinc Code, Loinc Url, Aksi
    const ths = wrapper.findAll('thead th');
    expect(ths.length).toBe(11);
    expect(ths[0].text()).toContain('No');
    expect(ths[1].text()).toContain('Grup');
    expect(ths[2].text()).toContain('Tipe');
    expect(ths[3].text()).toContain('Kategori');
    expect(ths[4].text()).toContain('Item Pemeriksaan');
    expect(ths[5].text()).toContain('Harga');
    expect(ths[6].text()).toContain('Harga Interpretasi');
    expect(ths[7].text()).toContain('Status');
    expect(ths[8].text()).toContain('Loinc Code');
    expect(ths[9].text()).toContain('Loinc Url');
    expect(ths[10].text()).toContain('Aksi');
  });

  it('shows empty state when no data exists', async () => {
    radiologyGroupsService.getAll.mockResolvedValue({
      data: {
        data: [],
        meta: { current_page: 1, last_page: 1, per_page: 10, total: 0 }
      }
    });

    const wrapper = mountView();
    await flushPromises();

    expect(wrapper.text()).toContain('Data tidak ditemukan');
    expect(wrapper.text()).toContain('Belum ada data yang ditambahkan.');
  });

  it('opens add modal, submits successfully, and displays success toast', async () => {
    radiologyGroupsService.create.mockResolvedValue({
      data: {
        data: { id: 3, name: 'CT Scan Kepala' }
      }
    });

    const wrapper = mountView();
    await flushPromises();

    // Click Tambah button
    const addBtn = wrapper.find('button.btn-primary');
    await addBtn.trigger('click');
    await flushPromises();

    expect(wrapper.text()).toContain('Tambah Grup Radiologi');

    // Fill valid form
    await wrapper.find('input#name').setValue('CT Scan Kepala');
    await wrapper.find('select#radiology_category_id').setValue('1');
    await wrapper.find('select#radiology_type_id').setValue('2');
    await wrapper.find('select#activity_type_id').setValue('3');
    await wrapper.find('input#price').setValue(500000);
    await wrapper.find('input#interpretation_price').setValue(150000);

    // Submit
    await wrapper.find('form.modal-form-wrapper').trigger('submit');
    await flushPromises();

    expect(radiologyGroupsService.create).toHaveBeenCalled();
    expect(wrapper.text()).toContain('Grup radiologi berhasil ditambahkan.');
  });

  it('opens edit modal, submits update, and displays success toast', async () => {
    radiologyGroupsService.update.mockResolvedValue({
      data: {
        data: { id: 1, name: 'Rontgen Thorax Updated' }
      }
    });

    const wrapper = mountView();
    await flushPromises();

    // Click edit on first row
    const editBtn = wrapper.find('.btn-action.edit');
    await editBtn.trigger('click');
    await flushPromises();

    expect(wrapper.text()).toContain('Edit Grup Radiologi');

    await wrapper.find('input#name').setValue('Rontgen Thorax Updated');
    await wrapper.find('form.modal-form-wrapper').trigger('submit');
    await flushPromises();

    expect(radiologyGroupsService.update).toHaveBeenCalled();
    expect(wrapper.text()).toContain('Grup radiologi berhasil diperbarui.');
  });

  it('opens delete dialog, confirms delete, and displays success toast', async () => {
    radiologyGroupsService.delete.mockResolvedValue({ success: true });

    const wrapper = mountView();
    await flushPromises();

    // Click delete on first row
    const deleteBtn = wrapper.find('.btn-action.delete');
    await deleteBtn.trigger('click');
    await flushPromises();

    expect(wrapper.text()).toContain('Hapus Grup Radiologi');
    expect(wrapper.text()).toContain('Rontgen Thorax Standar');

    // Confirm delete
    const confirmDeleteBtn = wrapper.find('.modal-footer .btn-danger');
    await confirmDeleteBtn.trigger('click');
    await flushPromises();

    expect(radiologyGroupsService.delete).toHaveBeenCalledWith(1);
    expect(wrapper.text()).toContain('Grup radiologi berhasil dihapus.');
  });
});
