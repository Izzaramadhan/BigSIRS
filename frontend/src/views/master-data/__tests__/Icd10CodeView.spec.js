import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount, flushPromises } from '@vue/test-utils';
import { createRouter, createWebHistory } from 'vue-router';
import Icd10CodeView from '../Icd10CodeView.vue';
import icd10Service from '@/services/master-data/icd10.service';

vi.mock('@/services/master-data/icd10.service', () => ({
  default: {
    getIcd10Codes: vi.fn(),
    getIcd10Code: vi.fn(),
    createIcd10Code: vi.fn(),
    updateIcd10Code: vi.fn(),
    deleteIcd10Code: vi.fn()
  }
}));

const mockCodes = {
  data: [
    {
      id: 1,
      code: 'A00.0',
      name: 'Cholera classical',
      english_name: 'Cholera classical',
      description: 'Deskripsi kolera',
      inacbg_code: 'I-4-10-I',
      inacbg_name: 'Diare Akut',
      class_1_tariff: 1200000,
      class_2_tariff: 900000,
      class_3_tariff: 600000,
      is_medical_history: true
    },
    {
      id: 2,
      code: 'A01.0',
      name: 'Typhoid fever',
      english_name: null,
      description: null,
      inacbg_code: null,
      inacbg_name: null,
      class_1_tariff: null,
      class_2_tariff: null,
      class_3_tariff: null,
      is_medical_history: false
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
      redirect: '/master-data/diagnoses/icd10'
    },
    {
      path: '/master-data/diagnoses/icd10',
      name: 'master-data.icd10',
      component: Icd10CodeView
    }
  ]
});

describe('Icd10CodeView.vue', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  const mountView = async (responseMock = mockCodes) => {
    icd10Service.getIcd10Codes.mockResolvedValue(responseMock);
    const wrapper = mount(Icd10CodeView, {
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
    expect(wrapper.find('h1.page-title').text()).toBe('ICD-10');
    expect(wrapper.text()).toContain('Kelola data klasifikasi penyakit dan diagnosis ICD-10.');
    expect(wrapper.text()).toContain('Data Diagnosa');
  });

  it('fetches and displays ICD-10 data on mount', async () => {
    const wrapper = await mountView();
    expect(icd10Service.getIcd10Codes).toHaveBeenCalled();
    expect(wrapper.text()).toContain('A00.0');
    expect(wrapper.text()).toContain('Cholera classical');
    expect(wrapper.text()).toContain('A01.0');
  });

  it('displays empty state when there is no data', async () => {
    const wrapper = await mountView(mockEmptyCodes);
    expect(wrapper.text()).toContain('Data tidak ditemukan');
  });

  it('opens add modal when "Tambah ICD-10" button is clicked', async () => {
    const wrapper = await mountView();
    const addBtn = wrapper.find('button.btn-primary');
    await addBtn.trigger('click');
    await flushPromises();

    expect(wrapper.find('.modal-header h3').text()).toBe('Tambah ICD-10');
  });

  it('shows success toast on successful creation', async () => {
    const wrapper = await mountView();
    icd10Service.createIcd10Code.mockResolvedValue({ success: true, data: {} });

    // Open Add Modal
    await wrapper.find('button.btn-primary').trigger('click');
    await flushPromises();

    // Fill form
    await wrapper.find('#code').setValue('A09.0');
    await wrapper.find('#name').setValue('Gastroenteritis');
    await wrapper.find('form').trigger('submit.prevent');
    await flushPromises();

    const toast = wrapper.find('.app-toast');
    expect(toast.exists()).toBe(true);
    expect(toast.classes()).toContain('toast-success');
    expect(toast.find('.toast-message').text()).toBe('Data ICD-10 berhasil ditambahkan.');
  });

  it('shows success toast on successful update', async () => {
    const wrapper = await mountView();
    icd10Service.getIcd10Code.mockResolvedValue({ data: mockCodes.data[0] });
    icd10Service.updateIcd10Code.mockResolvedValue({ success: true, data: {} });

    // Click Edit on first item
    await wrapper.find('.actions-cell button.edit').trigger('click');
    await flushPromises();

    expect(wrapper.find('.modal-header h3').text()).toBe('Edit ICD-10');

    await wrapper.find('form').trigger('submit.prevent');
    await flushPromises();

    const toast = wrapper.find('.app-toast');
    expect(toast.exists()).toBe(true);
    expect(toast.classes()).toContain('toast-success');
    expect(toast.find('.toast-message').text()).toBe('Data ICD-10 berhasil diperbarui.');
  });

  it('handles delete flow with confirmation dialog and toast', async () => {
    const wrapper = await mountView();
    icd10Service.deleteIcd10Code.mockResolvedValue({ message: 'Deleted' });

    // Click Delete on first item
    await wrapper.find('.actions-cell button.delete').trigger('click');
    await flushPromises();

    // Check delete confirmation dialog
    expect(wrapper.find('.modal-content h3.text-danger').text()).toBe('Hapus ICD-10');
    expect(wrapper.text()).toContain('A00.0 - Cholera classical');

    // Confirm delete
    await wrapper.find('.modal-footer .btn-danger').trigger('click');
    await flushPromises();

    expect(icd10Service.deleteIcd10Code).toHaveBeenCalledWith(1);
    const toast = wrapper.find('.app-toast');
    expect(toast.exists()).toBe(true);
    expect(toast.classes()).toContain('toast-success');
    expect(toast.find('.toast-message').text()).toBe('Data ICD-10 berhasil dihapus.');
  });

  it('displays validation error toast on 422 failure', async () => {
    const wrapper = await mountView();
    icd10Service.createIcd10Code.mockRejectedValue({
      response: {
        status: 422,
        data: {
          message: 'Kode diagnosa sudah ada.',
          errors: {
            code: ['Kode diagnosa sudah digunakan.']
          }
        }
      }
    });

    await wrapper.find('button.btn-primary').trigger('click');
    await flushPromises();

    await wrapper.find('#code').setValue('A00.0');
    await wrapper.find('#name').setValue('Duplicate');
    await wrapper.find('form').trigger('submit.prevent');
    await flushPromises();

    const toast = wrapper.find('.app-toast');
    expect(toast.exists()).toBe(true);
    expect(toast.classes()).toContain('toast-error');
    expect(toast.find('.toast-message').text()).toBe('Kode diagnosa sudah ada.');
    expect(wrapper.text()).toContain('Kode diagnosa sudah digunakan.');
  });
});
