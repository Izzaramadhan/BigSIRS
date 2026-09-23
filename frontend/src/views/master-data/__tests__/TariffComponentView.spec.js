import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { mount } from '@vue/test-utils';
import TariffComponentView from '../TariffComponentView.vue';
import { useTariffComponents } from '@/composables/useTariffComponents';

import { ref } from 'vue';

// Mock the composable
vi.mock('@/composables/useTariffComponents');

describe('TariffComponentView.vue', () => {
  let mockFetchTariffComponents;
  let mockCreateTariffComponent;
  let mockUpdateTariffComponent;
  let mockUpdateTariffComponentStatus;
  let mockDeleteTariffComponent;

  beforeEach(() => {
    mockFetchTariffComponents = vi.fn();
    mockCreateTariffComponent = vi.fn();
    mockUpdateTariffComponent = vi.fn();
    mockUpdateTariffComponentStatus = vi.fn();
    mockDeleteTariffComponent = vi.fn();

    useTariffComponents.mockReturnValue({
      tariffComponents: ref([{ id: 1, name: 'Jasa Medis', is_active: true }]),
      totalItems: ref(1),
      currentPage: ref(1),
      perPage: ref(10),
      loading: ref(false),
      error: ref(null),
      fetchTariffComponents: mockFetchTariffComponents,
      createTariffComponent: mockCreateTariffComponent,
      updateTariffComponent: mockUpdateTariffComponent,
      updateTariffComponentStatus: mockUpdateTariffComponentStatus,
      deleteTariffComponent: mockDeleteTariffComponent,
    });
  });

  afterEach(() => {
    vi.clearAllMocks();
  });

  it('renders table correctly', () => {
    const wrapper = mount(TariffComponentView, {
      global: {
        stubs: {
          TariffComponentFormModal: true,
          TariffComponentTable: true,
          RouterLink: true
        }
      }
    });

    expect(wrapper.text()).toContain('Komponen Tindakan');
    expect(mockFetchTariffComponents).toHaveBeenCalled();
  });

  it('opens add modal on button click', async () => {
    const wrapper = mount(TariffComponentView, {
      global: {
        stubs: {
          TariffComponentFormModal: true,
          TariffComponentTable: true,
          RouterLink: true
        }
      }
    });

    await wrapper.find('button').trigger('click');
    
    // Check if the add modal is opened by checking the props passed to it
    const modal = wrapper.findComponent({ name: 'TariffComponentFormModal' });
    expect(modal.props('isOpen')).toBe(true);
    expect(modal.props('editData')).toBeNull();
  });
});
