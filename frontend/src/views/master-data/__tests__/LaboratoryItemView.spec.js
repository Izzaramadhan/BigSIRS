import { flushPromises, mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import LaboratoryItemView from '../LaboratoryItemView.vue';
import { laboratoryItemsService } from '@/services/master-data/laboratoryItems.service';

vi.mock('@/services/master-data/laboratoryItems.service', () => ({
  laboratoryItemsService: {
    list: vi.fn(),
    detail: vi.fn(),
    create: vi.fn(),
    update: vi.fn(),
    remove: vi.fn(),
  },
}));

const paginatedResponse = {
  data: [
    { id: 1, name: 'KOLESTEROL TOTAL', reference_value: '<200', unit: 'mg/dL', is_active: true },
    { id: 2, name: 'HBSAG', reference_value: 'Negatif', unit: null, is_active: false },
  ],
  meta: { current_page: 1, last_page: 2, per_page: 10, total: 12 },
  links: {},
};

describe('LaboratoryItemView.vue', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    laboratoryItemsService.list.mockResolvedValue(paginatedResponse);
  });

  const mountView = () => mount(LaboratoryItemView, {
    global: {
      stubs: {
        Teleport: true,
      },
    },
  });

  it('renders table data safely and does not contain status controls or badges', async () => {
    const wrapper = mountView();
    await flushPromises();

    expect(wrapper.text()).toContain('Item Lab');
    expect(wrapper.text()).toContain('KOLESTEROL TOTAL');
    expect(wrapper.text()).toContain('<200');
    expect(wrapper.text()).toContain('HBSAG');
    expect(wrapper.text()).toContain('Negatif');
    expect(wrapper.findAll('tbody tr')[1].text()).toContain('-');

    // Verify status filter dropdown is completely absent
    expect(wrapper.find('select.filter-select').exists()).toBe(false);
    expect(wrapper.text()).not.toContain('Semua Status');

    // Verify status table header and badges are absent
    expect(wrapper.find('th.col-status').exists()).toBe(false);
    expect(wrapper.find('.status-badge').exists()).toBe(false);
  });

  it('shows API error separately from empty data', async () => {
    laboratoryItemsService.list.mockRejectedValue({
      response: { data: { message: 'Server error' } },
    });
    const wrapper = mountView();
    await flushPromises();

    expect(wrapper.text()).toContain('Server error');
  });

  it('shows empty state when no data exists', async () => {
    laboratoryItemsService.list.mockResolvedValue({
      data: [],
      meta: { current_page: 1, last_page: 1, per_page: 10, total: 0 },
    });
    const wrapper = mountView();
    await flushPromises();

    expect(wrapper.text()).toContain('Belum ada data');
  });

  it('sends search and pagination parameters', async () => {
    vi.useFakeTimers();
    const wrapper = mountView();
    await flushPromises();

    const searchInput = wrapper.find('.search-input');
    expect(searchInput.exists()).toBe(true);
    await searchInput.setValue('HDL');
    await vi.advanceTimersByTimeAsync(450);

    expect(laboratoryItemsService.list).toHaveBeenLastCalledWith(
      expect.objectContaining({ search: 'HDL', page: 1 })
    );

    vi.useRealTimers();
  });

  it('opens add modal when clicking Tambah Item Lab without status fields', async () => {
    const wrapper = mountView();
    await flushPromises();

    const addBtn = wrapper.find('button.btn-primary');
    expect(addBtn.exists()).toBe(true);
    await addBtn.trigger('click');

    expect(wrapper.text()).toContain('Tambah Item Lab');
    expect(wrapper.find('#is_active').exists()).toBe(false);
  });

  it('opens delete confirmation dialog and calls delete service', async () => {
    laboratoryItemsService.remove.mockResolvedValue({ message: 'ok' });
    const wrapper = mountView();
    await flushPromises();

    const deleteBtn = wrapper.find('.btn-action.delete');
    expect(deleteBtn.exists()).toBe(true);
    await deleteBtn.trigger('click');

    expect(wrapper.text()).toContain('Hapus Item Lab');
    expect(wrapper.text()).toContain('KOLESTEROL TOTAL');

    laboratoryItemsService.list.mockClear();
    const confirmDeleteBtn = wrapper.find('.btn-danger');
    expect(confirmDeleteBtn.exists()).toBe(true);
    await confirmDeleteBtn.trigger('click');
    await flushPromises();

    expect(laboratoryItemsService.remove).toHaveBeenCalledWith(1);
    expect(laboratoryItemsService.list).toHaveBeenCalledTimes(1);
    expect(wrapper.text()).toContain('Item lab berhasil dihapus.');
  });

  it('allows creating Item Lab with Convert Pangkat and submits Unicode characters', async () => {
    laboratoryItemsService.create.mockResolvedValue({
      data: { id: 3, name: 'LEKOSIT', reference_value: '5 - 10', unit: 'x10³/uL', is_active: true }
    });
    const wrapper = mountView();
    await flushPromises();

    // Click Add
    await wrapper.find('button.btn-primary').trigger('click');
    await flushPromises();

    // Fill Form
    await wrapper.find('input#name').setValue('LEKOSIT');
    await wrapper.find('textarea#reference_value').setValue('5 - 10');
    await wrapper.find('input#unit').setValue('x10^3/uL');

    // Click Convert Pangkat on Unit
    const convertButtons = wrapper.findAll('.btn-convert-pangkat');
    expect(convertButtons.length).toBeGreaterThan(1);
    await convertButtons[1].trigger('click');
    await flushPromises();

    expect(wrapper.find('input#unit').element.value).toBe('x10³/uL');

    // Submit Modal
    await wrapper.find('form').trigger('submit.prevent');
    await flushPromises();

    expect(laboratoryItemsService.create).toHaveBeenCalledWith({
      name: 'LEKOSIT',
      reference_value: '5 - 10',
      unit: 'x10³/uL',
      is_active: true
    });
    expect(wrapper.text()).toContain('Item lab berhasil ditambahkan.');
  });
});
