import { flushPromises, mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import RadiologyCategoriesView from '../RadiologyCategoriesView.vue';
import { radiologyCategoriesService } from '@/services/master-data/radiologyCategories.service';

vi.mock('@/services/master-data/radiologyCategories.service', () => ({
  radiologyCategoriesService: {
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
        description: 'Pemeriksaan rontgen konvensional',
        is_active: true,
        loinc_code: '24606-6',
        loinc_url: 'https://loinc.org/24606-6/',
        snomed_code: '363680008',
        snomed_url: 'https://snomed.info/id/363680008'
      },
      {
        id: 2,
        name: 'Ultrasonografi',
        description: 'USG pemeriksaan organ dalam',
        is_active: false,
        loinc_code: null,
        loinc_url: null,
        snomed_code: null,
        snomed_url: null
      }
    ],
    meta: { current_page: 1, last_page: 1, per_page: 10, total: 2 },
    links: {}
  }
};

describe('RadiologyCategoriesView.vue', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    radiologyCategoriesService.getAll.mockResolvedValue(mockPaginatedResponse);
  });

  const mountView = () =>
    mount(RadiologyCategoriesView, {
      global: {
        stubs: {
          Teleport: true,
        },
      },
    });

  it('renders page header, breadcrumbs, filters, and table with exact 9 columns', async () => {
    const wrapper = mountView();
    await flushPromises();

    expect(wrapper.text()).toContain('Kategori Rad');
    expect(wrapper.text()).toContain('Kelola master data kategori radiologi.');
    expect(wrapper.text()).toContain('Radiografi Konvensional');
    expect(wrapper.text()).toContain('Ultrasonografi');

    // Breadcrumbs
    expect(wrapper.text()).toContain('Dashboard');
    expect(wrapper.text()).toContain('Master Data');
    expect(wrapper.text()).toContain('Data Radiologi');

    // 9 Columns in exact order
    const ths = wrapper.findAll('thead th');
    expect(ths.length).toBe(9);
    expect(ths[0].text()).toBe('No');
    expect(ths[1].text()).toBe('Kategori');
    expect(ths[2].text()).toBe('Deskripsi');
    expect(ths[3].text()).toBe('Status');
    expect(ths[4].text()).toBe('Loinc Code');
    expect(ths[5].text()).toBe('Loinc Url');
    expect(ths[6].text()).toBe('Snomed Code');
    expect(ths[7].text()).toBe('Snomed Url');
    expect(ths[8].text()).toBe('Aksi');
  });

  it('shows empty state when no data exists', async () => {
    radiologyCategoriesService.getAll.mockResolvedValue({
      data: {
        data: [],
        meta: { current_page: 1, last_page: 1, per_page: 10, total: 0 }
      }
    });

    const wrapper = mountView();
    await flushPromises();

    expect(wrapper.text()).toContain('Belum ada data yang ditambahkan.');
  });

  it('handles add workflow with single success toast notification', async () => {
    radiologyCategoriesService.create.mockResolvedValue({
      data: {
        data: { id: 3, name: 'CT Scan', is_active: true }
      }
    });

    const wrapper = mountView();
    await flushPromises();

    // Click "Tambah Kategori Rad"
    const addBtn = wrapper.find('button.btn-primary');
    await addBtn.trigger('click');
    await flushPromises();

    expect(wrapper.text()).toContain('Tambah Kategori Radiologi');

    // Fill form
    await wrapper.find('input#name').setValue('CT Scan Kepala');
    await wrapper.find('form.modal-form-wrapper').trigger('submit');
    await flushPromises();

    expect(radiologyCategoriesService.create).toHaveBeenCalledWith(
      expect.objectContaining({ name: 'CT Scan Kepala', is_active: true })
    );

    // Toast message exact match
    expect(wrapper.text()).toContain('Kategori radiologi berhasil ditambahkan.');
  });

  it('handles edit workflow with single success toast notification', async () => {
    radiologyCategoriesService.update.mockResolvedValue({
      data: {
        data: { id: 1, name: 'Radiografi Konvensional Updated', is_active: true }
      }
    });

    const wrapper = mountView();
    await flushPromises();

    // Click edit button in table
    const editBtn = wrapper.find('.btn-action.edit');
    await editBtn.trigger('click');
    await flushPromises();

    expect(wrapper.text()).toContain('Edit Kategori Radiologi');
    expect(wrapper.find('input#name').element.value).toBe('Radiografi Konvensional');

    // Update name and submit
    await wrapper.find('input#name').setValue('Radiografi Konvensional Updated');
    await wrapper.find('form.modal-form-wrapper').trigger('submit');
    await flushPromises();

    expect(radiologyCategoriesService.update).toHaveBeenCalledWith(
      1,
      expect.objectContaining({ name: 'Radiografi Konvensional Updated' })
    );

    // Toast message exact match
    expect(wrapper.text()).toContain('Kategori radiologi berhasil diperbarui.');
  });

  it('handles delete workflow with confirmation dialog and single success toast', async () => {
    radiologyCategoriesService.delete.mockResolvedValue({});

    const wrapper = mountView();
    await flushPromises();

    // Click delete button
    const deleteBtn = wrapper.find('.btn-action.delete');
    await deleteBtn.trigger('click');
    await flushPromises();

    // Confirm dialog is shown with item name
    expect(wrapper.text()).toContain('Hapus Kategori Radiologi');
    expect(wrapper.text()).toContain('Radiografi Konvensional');

    // Confirm delete
    const confirmBtn = wrapper.find('.btn-danger');
    await confirmBtn.trigger('click');
    await flushPromises();

    expect(radiologyCategoriesService.delete).toHaveBeenCalledWith(1);
    expect(wrapper.text()).toContain('Kategori radiologi berhasil dihapus.');
  });
});
