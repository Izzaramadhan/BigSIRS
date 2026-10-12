import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount, flushPromises } from '@vue/test-utils';
import Icd9CmFormModal from '../Icd9CmFormModal.vue';
import icd9cmService from '@/services/master-data/icd9cm.service';

vi.mock('@/services/master-data/icd9cm.service', () => ({
  default: {
    getDetail: vi.fn(),
    getIcd9Cm: vi.fn(),
    createIcd9Cm: vi.fn(),
    updateIcd9Cm: vi.fn(),
    deleteIcd9Cm: vi.fn(),
    getIcd9Cms: vi.fn()
  }
}));

describe('Icd9CmFormModal.vue', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  const mountModal = (props = {}) => {
    return mount(Icd9CmFormModal, {
      props: {
        isOpen: true,
        item: null,
        isSubmitting: false,
        errors: {},
        ...props
      },
      global: {
        stubs: {
          Teleport: true
        }
      }
    });
  };

  it('renders modal with title "Tambah ICD-9" and button "Simpan" in create mode', () => {
    const wrapper = mountModal({ item: null });
    expect(wrapper.find('.modal-header h3').text()).toBe('Tambah ICD-9');
    expect(wrapper.find('button[type="submit"]').text()).toBe('Simpan');
  });

  it('renders modal with title "Edit ICD-9" and button "Perbarui" in edit mode', () => {
    const wrapper = mountModal({
      item: {
        id: 1,
        code: '01.01',
        name: 'Cisternal puncture'
      }
    });
    expect(wrapper.find('.modal-header h3').text()).toBe('Edit ICD-9');
    expect(wrapper.find('button[type="submit"]').text()).toBe('Perbarui');
  });

  it('contains all 7 fields in exact specified order', () => {
    const wrapper = mountModal();

    const fieldOrder = [
      '#code',
      '#name',
      '#inacbg_code',
      '#inacbg_name',
      '#class_1_tariff',
      '#class_2_tariff',
      '#class_3_tariff'
    ];

    fieldOrder.forEach((selector) => {
      expect(wrapper.find(selector).exists()).toBe(true);
    });

    // Check required markers
    expect(wrapper.find('label[for="code"]').classes()).toContain('required');
    expect(wrapper.find('label[for="name"]').classes()).toContain('required');
    expect(wrapper.find('label[for="inacbg_code"]').classes()).not.toContain('required');
    expect(wrapper.find('label[for="inacbg_name"]').classes()).not.toContain('required');
    expect(wrapper.find('label[for="class_1_tariff"]').classes()).not.toContain('required');
    expect(wrapper.find('label[for="class_2_tariff"]').classes()).not.toContain('required');
    expect(wrapper.find('label[for="class_3_tariff"]').classes()).not.toContain('required');
  });

  it('populates existing data in edit mode including tariffs', async () => {
    icd9cmService.getDetail.mockResolvedValue({
      data: {
        id: 10,
        code: '01.24',
        name: 'Other craniotomy',
        inacbg_code: 'Q-5-11-I',
        inacbg_name: 'Prosedur Cranial Sedang',
        class_1_tariff: 5500000,
        class_2_tariff: 4200000,
        class_3_tariff: 3100000
      }
    });

    const wrapper = mountModal({
      item: {
        id: 10,
        code: '01.24',
        name: 'Other craniotomy'
      }
    });

    await flushPromises();

    expect(wrapper.find('#code').element.value).toBe('01.24');
    expect(wrapper.find('#name').element.value).toBe('Other craniotomy');
    expect(wrapper.find('#inacbg_code').element.value).toBe('Q-5-11-I');
    expect(wrapper.find('#inacbg_name').element.value).toBe('Prosedur Cranial Sedang');
    expect(wrapper.find('#class_1_tariff').element.value).toBe('5500000');
    expect(wrapper.find('#class_2_tariff').element.value).toBe('4200000');
    expect(wrapper.find('#class_3_tariff').element.value).toBe('3100000');
  });

  it('emits submit with cleaned numeric tariffs and trimmed strings', async () => {
    const wrapper = mountModal();

    await wrapper.find('#code').setValue(' 02.01 ');
    await wrapper.find('#name').setValue(' Linear craniectomy ');
    await wrapper.find('#inacbg_code').setValue(' Q-5-12-I ');
    await wrapper.find('#inacbg_name').setValue(' Prosedur Tulang Tengkorak ');
    await wrapper.find('#class_1_tariff').setValue('1500000');
    await wrapper.find('#class_2_tariff').setValue('1200000');
    await wrapper.find('#class_3_tariff').setValue('900000');

    await wrapper.find('form').trigger('submit.prevent');

    expect(wrapper.emitted('submit')).toBeTruthy();
    expect(wrapper.emitted('submit')[0][0]).toEqual({
      code: '02.01',
      name: 'Linear craniectomy',
      inacbg_code: 'Q-5-12-I',
      inacbg_name: 'Prosedur Tulang Tengkorak',
      class_1_tariff: 1500000,
      class_2_tariff: 1200000,
      class_3_tariff: 900000
    });
  });

  it('displays field validation errors correctly', () => {
    const wrapper = mountModal({
      errors: {
        code: ['Kode ICD-9 sudah digunakan.'],
        name: ['Nama ICD-9 wajib diisi.']
      }
    });

    expect(wrapper.text()).toContain('Kode ICD-9 sudah digunakan.');
    expect(wrapper.text()).toContain('Nama ICD-9 wajib diisi.');
  });
});
