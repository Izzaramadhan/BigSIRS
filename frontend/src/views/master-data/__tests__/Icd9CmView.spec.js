import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount, flushPromises } from '@vue/test-utils';
import { createRouter, createWebHistory } from 'vue-router';
import Icd9CmView from '../Icd9CmView.vue';
import icd9cmService from '@/services/master-data/icd9cm.service';

vi.mock('@/services/master-data/icd9cm.service', () => ({
  default: {
    getList: vi.fn(),
    getIcd9Cms: vi.fn(),
    getDetail: vi.fn(),
    getIcd9Cm: vi.fn(),
    create: vi.fn(),
    createIcd9Cm: vi.fn(),
    update: vi.fn(),
    updateIcd9Cm: vi.fn(),
    delete: vi.fn(),
    deleteIcd9Cm: vi.fn()
  }
}));

const mockCodes = {
  data: [
    {
      id: 1,
      code: '01.01',
      name: 'Cisternal puncture',
      inacbg_code: 'Q-5-10-I',
      inacbg_name: 'Prosedur Cranial Ringan',
      class_1_tariff: 1500000,
      class_2_tariff: 1200000,
      class_3_tariff: 900000
    },
    {
      id: 2,
      code: '01.02',
      name: 'Ventriculopuncture',
      inacbg_code: null,
      inacbg_name: null,
      class_1_tariff: null,
      class_2_tariff: null,
      class_3_tariff: null
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

const mockEmptyCodes = {
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
      redirect: '/master-data/diagnoses/icd9'
    },
    {
      path: '/master-data/diagnoses/icd9',
      name: 'master-data.icd9',
      component: Icd9CmView
    }
  ]
});

describe('Icd9CmView.vue', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  const mountView = async (responseMock = mockCodes) => {
    icd9cmService.getList.mockResolvedValue(responseMock);
    icd9cmService.getIcd9Cms.mockResolvedValue(responseMock);
    const wrapper = mount(Icd9CmView, {
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
    expect(wrapper.find('h1.page-title').text()).toBe('ICD-9');
    expect(wrapper.text()).toContain('Kelola referensi kode prosedur medis dan tindakan ICD-9.');
    expect(wrapper.text()).toContain('Data Diagnosa');
  });

  it('fetches and displays ICD-9 data on mount', async () => {
    const wrapper = await mountView();
    expect(icd9cmService.getList).toHaveBeenCalled();
    expect(wrapper.text()).toContain('01.01');
    expect(wrapper.text()).toContain('Cisternal puncture');
    expect(wrapper.text()).toContain('01.02');
  });

  it('displays empty state when there is no data', async () => {
    const wrapper = await mountView(mockEmptyCodes);
    expect(wrapper.text()).toContain('Data tidak ditemukan');
  });

  it('opens add modal when "Tambah ICD-9" button is clicked', async () => {
    const wrapper = await mountView();
    const addBtn = wrapper.find('button.btn-primary');
    await addBtn.trigger('click');
    await flushPromises();

    expect(wrapper.find('.modal-header h3').text()).toBe('Tambah ICD-9');
  });

  it('shows success toast on successful creation', async () => {
    const wrapper = await mountView();
    icd9cmService.create.mockResolvedValue({ success: true, data: {} });
    icd9cmService.createIcd9Cm.mockResolvedValue({ success: true, data: {} });

    // Open Add Modal
    await wrapper.find('button.btn-primary').trigger('click');
    await flushPromises();

    // Fill form
    await wrapper.find('#code').setValue('01.09');
    await wrapper.find('#name').setValue('Other cranial puncture');
    await wrapper.find('form').trigger('submit.prevent');
    await flushPromises();

    const toast = wrapper.find('.app-toast');
    expect(toast.exists()).toBe(true);
    expect(toast.classes()).toContain('toast-success');
    expect(toast.find('.toast-message').text()).toBe('Data ICD-9 berhasil ditambahkan.');
  });

  it('shows success toast on successful update', async () => {
    const wrapper = await mountView();
    icd9cmService.getDetail.mockResolvedValue({ data: mockCodes.data[0] });
    icd9cmService.getIcd9Cm.mockResolvedValue({ data: mockCodes.data[0] });
    icd9cmService.update.mockResolvedValue({ success: true, data: {} });
    icd9cmService.updateIcd9Cm.mockResolvedValue({ success: true, data: {} });

    // Click Edit on first item
    await wrapper.find('.actions-cell button.edit').trigger('click');
    await flushPromises();

    expect(wrapper.find('.modal-header h3').text()).toBe('Edit ICD-9');

    await wrapper.find('form').trigger('submit.prevent');
    await flushPromises();

    const toast = wrapper.find('.app-toast');
    expect(toast.exists()).toBe(true);
    expect(toast.classes()).toContain('toast-success');
    expect(toast.find('.toast-message').text()).toBe('Data ICD-9 berhasil diperbarui.');
  });

  it('handles delete flow with confirmation dialog and toast', async () => {
    const wrapper = await mountView();
    icd9cmService.delete.mockResolvedValue({ message: 'Deleted' });
    icd9cmService.deleteIcd9Cm.mockResolvedValue({ message: 'Deleted' });

    // Click Delete on first item
    await wrapper.find('.actions-cell button.delete').trigger('click');
    await flushPromises();

    // Check delete confirmation dialog
    expect(wrapper.find('.modal-content h3.text-danger').text()).toBe('Hapus ICD-9');
    expect(wrapper.text()).toContain('01.01 - Cisternal puncture');

    // Confirm delete
    await wrapper.find('.modal-footer .btn-danger').trigger('click');
    await flushPromises();

    expect(icd9cmService.delete).toHaveBeenCalledWith(1);
    const toast = wrapper.find('.app-toast');
    expect(toast.exists()).toBe(true);
    expect(toast.classes()).toContain('toast-success');
    expect(toast.find('.toast-message').text()).toBe('Data ICD-9 berhasil dihapus.');
  });

  it('displays validation error toast on 422 failure', async () => {
    const wrapper = await mountView();
    icd9cmService.create.mockRejectedValue({
      response: {
        status: 422,
        data: {
          message: 'Kode ICD-9 sudah ada.',
          errors: {
            code: ['Kode ICD-9 sudah digunakan.']
          }
        }
      }
    });
    icd9cmService.createIcd9Cm.mockRejectedValue({
      response: {
        status: 422,
        data: {
          message: 'Kode ICD-9 sudah ada.',
          errors: {
            code: ['Kode ICD-9 sudah digunakan.']
          }
        }
      }
    });

    await wrapper.find('button.btn-primary').trigger('click');
    await flushPromises();

    await wrapper.find('#code').setValue('01.01');
    await wrapper.find('#name').setValue('Duplicate');
    await wrapper.find('form').trigger('submit.prevent');
    await flushPromises();

    const toast = wrapper.find('.app-toast');
    expect(toast.exists()).toBe(true);
    expect(toast.classes()).toContain('toast-error');
    expect(toast.find('.toast-message').text()).toBe('Kode ICD-9 sudah ada.');
    expect(wrapper.text()).toContain('Kode ICD-9 sudah digunakan.');
  });
});
