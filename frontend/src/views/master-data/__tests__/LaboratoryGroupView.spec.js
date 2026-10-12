import { flushPromises, mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import LaboratoryGroupView from '../LaboratoryGroupView.vue';
import { laboratoryGroupsService } from '@/services/master-data/laboratoryGroups.service';
import { laboratoryCategoriesService } from '@/services/master-data/laboratoryCategories.service';
import { laboratoryItemsService } from '@/services/master-data/laboratoryItems.service';

vi.mock('@/services/master-data/laboratoryGroups.service', () => ({
  laboratoryGroupsService: {
    list: vi.fn(),
    detail: vi.fn(),
    create: vi.fn(),
    update: vi.fn(),
    remove: vi.fn(),
  },
}));

vi.mock('@/services/master-data/laboratoryCategories.service', () => ({
  laboratoryCategoriesService: {
    all: vi.fn(),
    list: vi.fn(),
    getLaboratoryCategories: vi.fn(),
  },
}));

vi.mock('@/services/master-data/laboratoryItems.service', () => ({
  laboratoryItemsService: {
    all: vi.fn(),
    list: vi.fn(),
  },
}));

const paginatedGroupsResponse = {
  data: [
    {
      id: 1,
      name: 'Complete Blood Count + LED',
      price: 85000,
      harga_jaspel: 25000,
      is_active: true,
      loinc_code: '58410-2',
      loinc_url: 'https://loinc.org/58410-2/',
      snomed_code: '439958008',
      snomed_url: 'https://browser.ihtsdotools.org/?perspective=full&conceptId1=439958008',
      category: { id: 1, name: 'Hematologi Rutin' },
      items: [
        { id: 6, name: 'HEMOGLOBIN', unit: 'g/dL' },
        { id: 7, name: 'HITUNG LEKOSIT', unit: '10³/uL' }
      ]
    },
    {
      id: 2,
      name: 'Profil Lipid',
      price: 120000,
      harga_jaspel: null,
      is_active: false,
      loinc_code: null,
      loinc_url: null,
      snomed_code: null,
      snomed_url: null,
      category: { id: 2, name: 'Kimia Darah' },
      items: []
    }
  ],
  meta: { current_page: 1, last_page: 1, per_page: 10, total: 2 },
  links: {}
};

describe('LaboratoryGroupView.vue', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    laboratoryGroupsService.list.mockResolvedValue(paginatedGroupsResponse);
    laboratoryCategoriesService.getLaboratoryCategories.mockResolvedValue({
      data: [
        { id: 1, name: 'Hematologi Rutin' },
        { id: 2, name: 'Kimia Darah' }
      ]
    });
    laboratoryCategoriesService.all.mockResolvedValue({
      data: [
        { id: 1, name: 'Hematologi Rutin' },
        { id: 2, name: 'Kimia Darah' }
      ]
    });
    laboratoryItemsService.list.mockResolvedValue({
      data: [
        { id: 6, name: 'HEMOGLOBIN', unit: 'g/dL' },
        { id: 7, name: 'HITUNG LEKOSIT', unit: '10³/uL' }
      ]
    });
    laboratoryItemsService.all.mockResolvedValue({
      data: [
        { id: 6, name: 'HEMOGLOBIN', unit: 'g/dL' },
        { id: 7, name: 'HITUNG LEKOSIT', unit: '10³/uL' }
      ]
    });
  });

  const mountView = () => mount(LaboratoryGroupView, {
    global: {
      stubs: {
        Teleport: true,
      },
    },
  });

  it('renders page header, filters, and table with 12 columns', async () => {
    const wrapper = mountView();
    await flushPromises();

    expect(wrapper.text()).toContain('Grup Lab');
    expect(wrapper.text()).toContain('Kelola master data grup laboratorium');
    expect(wrapper.text()).toContain('Complete Blood Count + LED');
    expect(wrapper.text()).toContain('Hematologi Rutin');
    // Verify category filter dropdown is absent from the filters toolbar
    expect(wrapper.find('.filter-controls').text()).not.toContain('Semua Kategori');
    expect(wrapper.find('.filter-controls').text()).toContain('Semua Status');

    const ths = wrapper.findAll('thead th');
    expect(ths.length).toBe(12);
    expect(ths[0].text()).toBe('No');
    expect(ths[1].text()).toBe('Grup');
    expect(ths[2].text()).toBe('Kategori');
    expect(ths[3].text()).toBe('Item');
    expect(ths[4].text()).toBe('Harga');
    expect(ths[5].text()).toBe('Harga Jaspel');
    expect(ths[6].text()).toBe('Status');
    expect(ths[7].text()).toBe('LOINC Code');
    expect(ths[8].text()).toBe('LOINC URL');
    expect(ths[9].text()).toBe('SNOMED Code');
    expect(ths[10].text()).toBe('SNOMED URL');
    expect(ths[11].text()).toBe('Aksi');
  });

  it('shows empty state when no data exists', async () => {
    laboratoryGroupsService.list.mockResolvedValue({
      data: [],
      meta: { current_page: 1, last_page: 1, per_page: 10, total: 0 }
    });

    const wrapper = mountView();
    await flushPromises();

    expect(wrapper.text()).toContain('Belum ada data yang ditambahkan.');
  });

  it('handles search input and triggers search with debounce', async () => {
    vi.useFakeTimers();
    const wrapper = mountView();
    await flushPromises();

    const searchInput = wrapper.find('.search-input');
    expect(searchInput.exists()).toBe(true);
    await searchInput.setValue('Lipid');
    await vi.advanceTimersByTimeAsync(450);

    expect(laboratoryGroupsService.list).toHaveBeenCalledWith(
      expect.objectContaining({ search: 'Lipid', page: 1 })
    );

    vi.useRealTimers();
  });

  it('opens add modal on clicking Tambah Grup Lab button', async () => {
    const wrapper = mountView();
    await flushPromises();

    const addButton = wrapper.find('.header-actions .btn-primary');
    expect(addButton.exists()).toBe(true);
    await addButton.trigger('click');
    await flushPromises();

    expect(wrapper.text()).toContain('Tambah Grup Lab');
  });

  it('opens edit modal with item details on edit action', async () => {
    const wrapper = mountView();
    await flushPromises();

    const editButtons = wrapper.findAll('.btn-action.edit');
    expect(editButtons.length).toBeGreaterThan(0);
    await editButtons[0].trigger('click');
    await flushPromises();

    expect(wrapper.text()).toContain('Edit Grup Lab');
    const nameInput = wrapper.find('#name');
    expect(nameInput.element.value).toBe('Complete Blood Count + LED');
  });

  it('opens delete confirmation dialog and executes removal with success notification', async () => {
    laboratoryGroupsService.remove.mockResolvedValue({ message: 'Deleted' });
    const wrapper = mountView();
    await flushPromises();

    const deleteButtons = wrapper.findAll('.btn-action.delete');
    expect(deleteButtons.length).toBeGreaterThan(0);
    await deleteButtons[0].trigger('click');
    await flushPromises();

    // Confirm dialog opened
    expect(wrapper.text()).toContain('Hapus Grup Lab');
    expect(wrapper.text()).toContain('Complete Blood Count + LED');

    // Confirm removal
    const confirmDeleteBtn = wrapper.find('.modal-footer .btn-danger');
    expect(confirmDeleteBtn.exists()).toBe(true);
    await confirmDeleteBtn.trigger('click');
    await flushPromises();

    expect(laboratoryGroupsService.remove).toHaveBeenCalledWith(1);
    expect(wrapper.text()).toContain('Grup lab berhasil dihapus.');
  });
});
