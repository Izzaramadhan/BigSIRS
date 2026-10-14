import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import LaboratoryCategoryFormModal from '../LaboratoryCategoryFormModal.vue';

describe('LaboratoryCategoryFormModal.vue', () => {
  const mountModal = (propsData = {}) => {
    return mount(LaboratoryCategoryFormModal, {
      props: {
        isOpen: true,
        category: null,
        isSubmitting: false,
        errors: {},
        ...propsData
      },
      global: {
        stubs: {
          teleport: true
        }
      }
    });
  };

  it('renders correctly with default title for new category', () => {
    const wrapper = mountModal();

    expect(wrapper.text()).toContain('Tambah Kategori Lab');
    expect(wrapper.find('input#name').element.value).toBe('');
    expect(wrapper.find('select#type').element.value).toBe('');
    expect(wrapper.find('input#snomed_url').element.value).toBe('');
    expect(wrapper.find('input#snomed_url').attributes('placeholder')).toBe('https://...');
  });

  it('populates fields when category prop is provided', () => {
    const category = {
      id: 1,
      name: 'Hematologi Rutin',
      description: 'Darah lengkap',
      type: 'lab klinik',
      loinc_code: '58410-2',
      loinc_url: 'https://loinc.org/58410-2/',
      snomed_code: '252275004',
      snomed_url: 'https://snomed.info/id/252275004',
      is_active: true
    };

    const wrapper = mountModal({
      category
    });

    expect(wrapper.text()).toContain('Edit Kategori Lab');
    expect(wrapper.find('input#name').element.value).toBe('Hematologi Rutin');
    expect(wrapper.find('select#type').element.value).toBe('lab klinik');
    expect(wrapper.find('textarea#description').element.value).toBe('Darah lengkap');
    expect(wrapper.find('input#loinc_code').element.value).toBe('58410-2');
    expect(wrapper.find('input#loinc_url').element.value).toBe('https://loinc.org/58410-2/');
    expect(wrapper.find('input#snomed_code').element.value).toBe('252275004');
    expect(wrapper.find('input#snomed_url').element.value).toBe('https://snomed.info/id/252275004');
  });

  it('emits submit event with trimmed payload when form is submitted', async () => {
    const wrapper = mountModal();

    await wrapper.find('input#name').setValue('  Kimia Klinik  ');
    await wrapper.find('select#type').setValue('lab klinik');
    await wrapper.find('textarea#description').setValue('  Pemeriksaan biokimia  ');
    await wrapper.find('input#loinc_code').setValue('  12345-6  ');
    await wrapper.find('input#snomed_code').setValue('  252275004  ');
    await wrapper.find('input#snomed_url').setValue('  https://snomed.info/id/252275004  ');

    await wrapper.find('form').trigger('submit.prevent');

    expect(wrapper.emitted('submit')).toBeTruthy();
    const payload = wrapper.emitted('submit')[0][0];
    expect(payload.name).toBe('Kimia Klinik');
    expect(payload.type).toBe('lab klinik');
    expect(payload.description).toBe('Pemeriksaan biokimia');
    expect(payload.loinc_code).toBe('12345-6');
    expect(payload.snomed_code).toBe('252275004');
    expect(payload.snomed_url).toBe('https://snomed.info/id/252275004');
    expect(payload.is_active).toBe(true);
  });

  it('validates invalid SNOMED URL and prevents submission', async () => {
    const wrapper = mountModal();

    await wrapper.find('input#name').setValue('Kimia Klinik');
    await wrapper.find('input#snomed_url').setValue('invalid-url-string');

    await wrapper.find('form').trigger('submit.prevent');

    expect(wrapper.emitted('submit')).toBeFalsy();
    expect(wrapper.find('input#snomed_url').classes()).toContain('is-invalid');
    expect(wrapper.text()).toContain('Format SNOMED URL tidak valid.');
  });

  it('renders field validation errors when passed in props', () => {
    const wrapper = mountModal({
      errors: {
        name: ['Nama kategori wajib diisi.'],
        loinc_url: ['URL LOINC harus berupa tautan yang valid.'],
        snomed_url: ['Format SNOMED URL tidak valid.']
      }
    });

    expect(wrapper.text()).toContain('Nama kategori wajib diisi.');
    expect(wrapper.text()).toContain('URL LOINC harus berupa tautan yang valid.');
    expect(wrapper.text()).toContain('Format SNOMED URL tidak valid.');
    expect(wrapper.find('input#name').classes()).toContain('is-invalid');
    expect(wrapper.find('input#loinc_url').classes()).toContain('is-invalid');
    expect(wrapper.find('input#snomed_url').classes()).toContain('is-invalid');
  });

  it('emits close event when Batal button is clicked', async () => {
    const wrapper = mountModal();

    const cancelBtn = wrapper.find('.btn-cancel');
    expect(cancelBtn.exists()).toBe(true);
    await cancelBtn.trigger('click');

    expect(wrapper.emitted('close')).toBeTruthy();
  });
});
