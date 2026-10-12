import { mount } from '@vue/test-utils';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import { ref } from 'vue';
import LetterTypeView from '../LetterTypeView.vue';
import { useLetterTypes } from '@/composables/useLetterTypes';

// Mock the composable
vi.mock('@/composables/useLetterTypes');

describe('LetterTypeView.vue', () => {
  let mockUseLetterTypes;

  beforeEach(() => {
    // Reset all mocks before each test
    vi.clearAllMocks();

    mockUseLetterTypes = {
      letterTypes: ref([]),
      loading: ref(false),
      error: ref(null),
      meta: ref({ current_page: 1, last_page: 1, total: 0 }),
      fetchLetterTypes: vi.fn().mockResolvedValue(true),
      createLetterType: vi.fn(),
      updateLetterType: vi.fn(),
      deleteLetterType: vi.fn(),
      updateStatus: vi.fn(),
    };

    useLetterTypes.mockReturnValue(mockUseLetterTypes);
  });

  it('renders page title and empty state when no data', async () => {
    const wrapper = mount(LetterTypeView);
    await wrapper.vm.$nextTick();
    
    // Check title
    expect(wrapper.find('h1.page-title').text()).toBe('Surat-Surat');
    
    // Check empty state
    expect(wrapper.find('.empty-state').exists()).toBe(true);
    expect(wrapper.find('.empty-state h3').text()).toBe('Data tidak ditemukan');
  });

  it('renders data table when there is data', async () => {
    mockUseLetterTypes.letterTypes.value = [
      { id: 1, name: 'Surat Keterangan Sehat', description: 'Deskripsi Sehat', legacy_resource: 'surat_sehat.php', is_active: 1 },
      { id: 2, name: 'Surat Rujukan', description: 'Deskripsi Rujukan', legacy_resource: 'surat_rujukan.php', is_active: 0 }
    ];
    mockUseLetterTypes.meta.value = { current_page: 1, last_page: 1, total: 2 };

    const wrapper = mount(LetterTypeView);
    await wrapper.vm.$nextTick();
    
    expect(wrapper.find('.empty-state').exists()).toBe(false);
    
    const rows = wrapper.findAll('tbody tr');
    expect(rows.length).toBe(2);
    
    // Check first row data
    expect(rows[0].text()).toContain('Surat Keterangan Sehat');
    expect(rows[0].text()).toContain('Deskripsi Sehat');
    expect(rows[0].text()).toContain('surat_sehat.php');
    
    // Check second row data
    expect(rows[1].text()).toContain('Surat Rujukan');
  });

  it('calls fetchLetterTypes on mount', () => {
    mount(LetterTypeView);
    expect(mockUseLetterTypes.fetchLetterTypes).toHaveBeenCalled();
  });

  it('opens add modal when add button is clicked', async () => {
    const wrapper = mount(LetterTypeView);
    
    const addBtn = wrapper.find('button.btn-primary');
    await addBtn.trigger('click');
    
    expect(wrapper.vm.isModalOpen).toBe(true);
    expect(wrapper.vm.selectedItem).toBeNull();
  });

  it('renders search input and refresh button in filters toolbar', async () => {
    const wrapper = mount(LetterTypeView);

    const searchInput = wrapper.find('.search-input');
    expect(searchInput.exists()).toBe(true);

    const refreshBtn = wrapper.find('.btn-icon');
    expect(refreshBtn.exists()).toBe(true);

    mockUseLetterTypes.fetchLetterTypes.mockClear();
    await refreshBtn.trigger('click');
    expect(mockUseLetterTypes.fetchLetterTypes).toHaveBeenCalled();
  });

  it('renders pagination at the bottom and handles page change', async () => {
    mockUseLetterTypes.letterTypes.value = Array.from({ length: 10 }, (_, i) => ({
      id: i + 1,
      name: `Surat ${i + 1}`,
      description: `Deskripsi ${i + 1}`,
      legacy_resource: null,
      is_active: 1
    }));
    mockUseLetterTypes.meta.value = { current_page: 1, last_page: 2, total: 15, per_page: 10, from: 1, to: 10 };

    const wrapper = mount(LetterTypeView);
    await wrapper.vm.$nextTick();

    const pagination = wrapper.findComponent({ name: 'MasterDataPagination' });
    expect(pagination.exists()).toBe(true);
    expect(pagination.text()).toContain('Menampilkan 1');
    expect(pagination.text()).toContain('sampai 10');
    expect(pagination.text()).toContain('dari 15 entri');
    expect(pagination.text()).toContain('Sebelumnya');
    expect(pagination.text()).toContain('1');
    expect(pagination.text()).toContain('2');
    expect(pagination.text()).toContain('Selanjutnya');

    mockUseLetterTypes.fetchLetterTypes.mockClear();
    await pagination.vm.$emit('page-change', 2);
    expect(mockUseLetterTypes.fetchLetterTypes).toHaveBeenCalledWith(
      expect.objectContaining({
        page: 2,
        per_page: 10
      })
    );
  });
});
