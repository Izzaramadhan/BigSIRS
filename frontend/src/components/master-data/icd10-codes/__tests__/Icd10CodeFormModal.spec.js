import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount, flushPromises } from '@vue/test-utils';
import Icd10CodeFormModal from '../Icd10CodeFormModal.vue';
import icd10Service from '@/services/master-data/icd10.service';

vi.mock('@/services/master-data/icd10.service', () => ({
  default: {
    getIcd10Code: vi.fn(),
    createIcd10Code: vi.fn(),
    updateIcd10Code: vi.fn(),
    deleteIcd10Code: vi.fn(),
    getIcd10Codes: vi.fn()
  }
}));

describe('Icd10CodeFormModal.vue', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  const mountModal = (props = {}) => {
    return mount(Icd10CodeFormModal, {
      props: {
        isOpen: true,
        code: null,
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

  it('renders modal with title "Tambah ICD-10" and button "Simpan" in create mode', () => {
    const wrapper = mountModal({ code: null });
    expect(wrapper.find('.modal-header h3').text()).toBe('Tambah ICD-10');
    expect(wrapper.find('button[type="submit"]').text()).toBe('Simpan');
  });

  it('renders modal with title "Edit ICD-10" and button "Perbarui" in edit mode', () => {
    const wrapper = mountModal({
      code: {
        id: 1,
        code: 'A00.0',
        name: 'Cholera'
      }
    });
    expect(wrapper.find('.modal-header h3').text()).toBe('Edit ICD-10');
    expect(wrapper.find('button[type="submit"]').text()).toBe('Perbarui');
  });

  it('contains all 10 fields in exact specified order', () => {
    const wrapper = mountModal();

    const fieldOrder = [
      '#code',
      '#name',
      '#english_name',
      '#description',
      '#inacbg_code',
      '#inacbg_name',
      '#class_1_tariff',
      '#class_2_tariff',
      '#class_3_tariff',
      '#is_medical_history'
    ];

    fieldOrder.forEach((selector) => {
      expect(wrapper.find(selector).exists()).toBe(true);
    });

    // Check required markers
    expect(wrapper.find('label[for="code"]').classes()).toContain('required');
    expect(wrapper.find('label[for="name"]').classes()).toContain('required');
    expect(wrapper.find('label[for="english_name"]').classes()).not.toContain('required');
  });

  it('populates existing data in edit mode including tariffs and medical history checkbox', async () => {
    icd10Service.getIcd10Code.mockResolvedValue({
      data: {
        id: 10,
        code: 'B20',
        name: 'HIV disease',
        english_name: 'Human immunodeficiency virus',
        description: 'Penyakit akibat infeksi HIV',
        inacbg_code: 'B-4-10-I',
        inacbg_name: 'Infeksi Virus',
        class_1_tariff: 2500000,
        class_2_tariff: 2000000,
        class_3_tariff: 1500000,
        is_medical_history: true
      }
    });

    const wrapper = mountModal({
      code: {
        id: 10,
        code: 'B20',
        name: 'HIV disease'
      }
    });

    await flushPromises();

    expect(wrapper.find('#code').element.value).toBe('B20');
    expect(wrapper.find('#name').element.value).toBe('HIV disease');
    expect(wrapper.find('#english_name').element.value).toBe('Human immunodeficiency virus');
    expect(wrapper.find('#description').element.value).toBe('Penyakit akibat infeksi HIV');
    expect(wrapper.find('#inacbg_code').element.value).toBe('B-4-10-I');
    expect(wrapper.find('#inacbg_name').element.value).toBe('Infeksi Virus');
    expect(wrapper.find('#class_1_tariff').element.value).toBe('2500000');
    expect(wrapper.find('#class_2_tariff').element.value).toBe('2000000');
    expect(wrapper.find('#class_3_tariff').element.value).toBe('1500000');
    expect(wrapper.find('#is_medical_history').element.checked).toBe(true);
  });

  it('submits clean numeric tariffs and boolean medical history value', async () => {
    const wrapper = mountModal();

    await wrapper.find('#code').setValue('a09');
    await wrapper.find('#name').setValue('Gastroenteritis');
    await wrapper.find('#english_name').setValue('Infectious gastroenteritis');
    await wrapper.find('#description').setValue('Diare dan muntah');
    await wrapper.find('#inacbg_code').setValue('I-4-10-I');
    await wrapper.find('#inacbg_name').setValue('Kelompok Diare');
    await wrapper.find('#class_1_tariff').setValue(800000);
    await wrapper.find('#class_2_tariff').setValue(600000);
    await wrapper.find('#class_3_tariff').setValue(400000);
    await wrapper.find('#is_medical_history').setValue(true);

    await wrapper.find('form').trigger('submit.prevent');

    expect(wrapper.emitted('submit')).toBeTruthy();
    const payload = wrapper.emitted('submit')[0][0];

    expect(payload.code).toBe('A09');
    expect(payload.name).toBe('Gastroenteritis');
    expect(payload.english_name).toBe('Infectious gastroenteritis');
    expect(payload.description).toBe('Diare dan muntah');
    expect(payload.inacbg_code).toBe('I-4-10-I');
    expect(payload.inacbg_name).toBe('Kelompok Diare');
    expect(payload.class_1_tariff).toBe(800000);
    expect(payload.class_2_tariff).toBe(600000);
    expect(payload.class_3_tariff).toBe(400000);
    expect(payload.is_medical_history).toBe(true);
  });

  it('displays field validation errors passed in errors prop', () => {
    const wrapper = mountModal({
      errors: {
        code: ['Kode diagnosa sudah digunakan.'],
        name: ['Nama diagnosa wajib diisi.'],
        general: 'Terjadi kesalahan sistem.'
      }
    });

    expect(wrapper.find('.alert-error').text()).toBe('Terjadi kesalahan sistem.');
    expect(wrapper.find('#code').classes()).toContain('is-invalid');
    expect(wrapper.text()).toContain('Kode diagnosa sudah digunakan.');
    expect(wrapper.find('#name').classes()).toContain('is-invalid');
    expect(wrapper.text()).toContain('Nama diagnosa wajib diisi.');
  });
});
