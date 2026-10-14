import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount, flushPromises } from '@vue/test-utils';
import { createRouter, createWebHistory } from 'vue-router';
import FollowUpHandlingsView from '../FollowUpHandlingsView.vue';
import { followUpHandlingsService } from '@/services/master-data/followUpHandlings.service';

vi.mock('@/services/master-data/followUpHandlings.service', () => {
  const service = {
    getFollowUpHandlings: vi.fn(),
    getHandlings: vi.fn(),
    getFollowUpHandling: vi.fn(),
    getHandling: vi.fn(),
    createFollowUpHandling: vi.fn(),
    createHandling: vi.fn(),
    updateFollowUpHandling: vi.fn(),
    updateHandling: vi.fn(),
    deleteFollowUpHandling: vi.fn(),
    deleteHandling: vi.fn()
  };
  return {
    default: service,
    followUpHandlingsService: service
  };
});

const mockData = {
  data: [
    {
      id: 1,
      name: 'Sembuh'
    },
    {
      id: 2,
      name: 'Rujuk Eksternal'
    }
  ],
  meta: {
    current_page: 1,
    last_page: 1,
    per_page: 10,
    total: 2,
    from: 1,
    to: 2
  }
};

const mockEmptyData = {
  data: [],
  meta: {
    current_page: 1,
    last_page: 1,
    per_page: 10,
    total: 0,
    from: 0,
    to: 0
  }
};

const router = createRouter({
  history: createWebHistory(),
  routes: [
    {
      path: '/',
      redirect: '/master-data/follow-up-handlings'
    },
    {
      path: '/master-data/follow-up-handlings',
      name: 'master-data.follow-up-handlings',
      component: FollowUpHandlingsView
    }
  ]
});

describe('FollowUpHandlingsView.vue', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  const mountView = async (responseMock = mockData) => {
    followUpHandlingsService.getFollowUpHandlings.mockResolvedValue(responseMock);
    const wrapper = mount(FollowUpHandlingsView, {
      global: {
        plugins: [router],
        stubs: {
          Teleport: true
        }
      }
    });
    await flushPromises();
    return wrapper;
  };

  it('renders page header and breadcrumbs correctly', async () => {
    const wrapper = await mountView();
    expect(wrapper.find('h1.page-title').text()).toBe('Penanganan Lanjutan');
    expect(wrapper.text()).toContain('Kelola data referensi penanganan lanjutan pasien.');
    expect(wrapper.text()).toContain('Master Data');
  });

  it('fetches and displays data on mount', async () => {
    const wrapper = await mountView();
    expect(followUpHandlingsService.getFollowUpHandlings).toHaveBeenCalled();
    expect(wrapper.text()).toContain('Sembuh');
    expect(wrapper.text()).toContain('Rujuk Eksternal');
  });

  it('displays empty state when there is no data', async () => {
    const wrapper = await mountView(mockEmptyData);
    expect(wrapper.text()).toContain('Data tidak ditemukan');
  });

  it('opens add modal when "Tambah Penanganan Lanjutan" button is clicked', async () => {
    const wrapper = await mountView();
    const addBtn = wrapper.find('button.btn-primary');
    await addBtn.trigger('click');
    await flushPromises();

    expect(wrapper.find('.modal-header h3').text()).toBe('Tambah Penanganan Lanjutan');
  });

  it('shows success toast on successful creation', async () => {
    const wrapper = await mountView();
    followUpHandlingsService.createFollowUpHandling.mockResolvedValue({ success: true, data: {} });

    // Open Add Modal
    await wrapper.find('button.btn-primary').trigger('click');
    await flushPromises();

    // Fill form
    await wrapper.find('#name').setValue('Kontrol Rutin');
    await wrapper.find('form').trigger('submit.prevent');
    await flushPromises();

    const toast = wrapper.find('.app-toast');
    expect(toast.exists()).toBe(true);
    expect(toast.classes()).toContain('toast-success');
    expect(toast.find('.toast-message').text()).toBe('Penanganan lanjutan berhasil ditambahkan.');
  });

  it('shows success toast on successful update', async () => {
    const wrapper = await mountView();
    followUpHandlingsService.updateFollowUpHandling.mockResolvedValue({ success: true, data: {} });

    // Click Edit on first item
    await wrapper.find('.actions-cell button.edit').trigger('click');
    await flushPromises();

    expect(wrapper.find('.modal-header h3').text()).toBe('Edit Penanganan Lanjutan');

    await wrapper.find('form').trigger('submit.prevent');
    await flushPromises();

    const toast = wrapper.find('.app-toast');
    expect(toast.exists()).toBe(true);
    expect(toast.classes()).toContain('toast-success');
    expect(toast.find('.toast-message').text()).toBe('Penanganan lanjutan berhasil diperbarui.');
  });

  it('handles delete flow with confirmation dialog and toast', async () => {
    const wrapper = await mountView();
    followUpHandlingsService.deleteFollowUpHandling.mockResolvedValue({ message: 'Deleted' });

    // Click Delete on first item
    await wrapper.find('.actions-cell button.delete').trigger('click');
    await flushPromises();

    // Check delete confirmation dialog
    expect(wrapper.find('.modal-content h3.text-danger').text()).toBe('Hapus Penanganan Lanjutan');
    expect(wrapper.text()).toContain('Sembuh');

    // Confirm delete
    await wrapper.find('.modal-footer .btn-danger').trigger('click');
    await flushPromises();

    expect(followUpHandlingsService.deleteFollowUpHandling).toHaveBeenCalledWith(1);
    const toast = wrapper.find('.app-toast');
    expect(toast.exists()).toBe(true);
    expect(toast.classes()).toContain('toast-success');
    expect(toast.find('.toast-message').text()).toBe('Penanganan lanjutan berhasil dihapus.');
  });

  it('displays validation error toast on 422 failure', async () => {
    const wrapper = await mountView();
    followUpHandlingsService.createFollowUpHandling.mockRejectedValue({
      response: {
        status: 422,
        data: {
          message: 'Nama penanganan lanjutan sudah ada.',
          errors: {
            name: ['Nama penanganan lanjutan sudah digunakan.']
          }
        }
      }
    });

    await wrapper.find('button.btn-primary').trigger('click');
    await flushPromises();

    await wrapper.find('#name').setValue('Sembuh');
    await wrapper.find('form').trigger('submit.prevent');
    await flushPromises();

    const toast = wrapper.find('.app-toast');
    expect(toast.exists()).toBe(true);
    expect(toast.classes()).toContain('toast-error');
    expect(toast.find('.toast-message').text()).toBe('Nama penanganan lanjutan sudah ada.');
    expect(wrapper.text()).toContain('Nama penanganan lanjutan sudah digunakan.');
  });
});
