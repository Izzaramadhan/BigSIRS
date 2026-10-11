import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import RadiologyCategoryModal from '../RadiologyCategoryModal.vue';

describe('RadiologyCategoryModal.vue', () => {
  const mountModal = (propsData = {}) => {
    return mount(RadiologyCategoryModal, {
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

    expect(wrapper.text()).toContain('Tambah Kategori Radiologi');
    expect(wrapper.find('input#name').element.value).toBe('');
    expect(wrapper.find('textarea#description').element.value).toBe('');
    expect(wrapper.find('input#status_active').element.checked).toBe(true);
    expect(wrapper.find('button[type="submit"]').text()).toContain('Simpan');
  });

  it('populates fields when category prop is provided', () => {
    const category = {
      id: 1,
      name: 'Radiografi Konvensional',
      description: 'Foto rontgen umum',
      is_active: false,
      loinc_code: '24606-6',
      loinc_url: 'https://loinc.org/24606-6/',
      snomed_code: '363680008',
      snomed_url: 'https://snomed.info/id/363680008'
    };

    const wrapper = mountModal({
      category
    });

    expect(wrapper.text()).toContain('Edit Kategori Radiologi');
    expect(wrapper.find('input#name').element.value).toBe('Radiografi Konvensional');
    expect(wrapper.find('textarea#description').element.value).toBe('Foto rontgen umum');
    expect(wrapper.find('input#status_inactive').element.checked).toBe(true);
    expect(wrapper.find('input#loinc_code').element.value).toBe('24606-6');
    expect(wrapper.find('input#loinc_url').element.value).toBe('https://loinc.org/24606-6/');
    expect(wrapper.find('input#snomed_code').element.value).toBe('363680008');
    expect(wrapper.find('input#snomed_url').element.value).toBe('https://snomed.info/id/363680008');
    expect(wrapper.find('button[type="submit"]').text()).toContain('Simpan Perubahan');
  });

  it('emits submit event with trimmed payload when form is submitted', async () => {
    const wrapper = mountModal();

    await wrapper.find('input#name').setValue('  USG Kandungan  ');
    await wrapper.find('textarea#description').setValue('  Pemeriksaan ultrasonografi  ');
    await wrapper.find('input#loinc_code').setValue('  12345-6  ');
    await wrapper.find('input#loinc_url').setValue('  https://loinc.org/12345-6  ');
    await wrapper.find('input#snomed_code').setValue('  987654321  ');
    await wrapper.find('input#snomed_url').setValue('  https://snomed.info/id/987654321  ');

    await wrapper.find('form').trigger('submit.prevent');

    expect(wrapper.emitted('submit')).toBeTruthy();
    const payload = wrapper.emitted('submit')[0][0];
    expect(payload.name).toBe('USG Kandungan');
    expect(payload.description).toBe('Pemeriksaan ultrasonografi');
    expect(payload.is_active).toBe(true);
    expect(payload.loinc_code).toBe('12345-6');
    expect(payload.loinc_url).toBe('https://loinc.org/12345-6');
    expect(payload.snomed_code).toBe('987654321');
    expect(payload.snomed_url).toBe('https://snomed.info/id/987654321');
  });

  it('validates required name field on client side before emit', async () => {
    const wrapper = mountModal();

    await wrapper.find('input#name').setValue('   ');
    await wrapper.find('form').trigger('submit.prevent');

    expect(wrapper.emitted('submit')).toBeFalsy();
    expect(wrapper.text()).toContain('Kategori radiologi wajib diisi.');
  });

  it('validates invalid URL format on client side before emit', async () => {
    const wrapper = mountModal();

    await wrapper.find('input#name').setValue('CT Scan');
    await wrapper.find('input#loinc_url').setValue('invalid-url-string');
    await wrapper.find('form').trigger('submit.prevent');

    expect(wrapper.emitted('submit')).toBeFalsy();
    expect(wrapper.text()).toContain('Format URL LOINC tidak valid');
  });

  it('renders field validation errors when passed in props', () => {
    const wrapper = mountModal({
      errors: {
        name: ['Kategori radiologi sudah digunakan.'],
        general: 'Terjadi kesalahan sistem.'
      }
    });

    expect(wrapper.text()).toContain('Kategori radiologi sudah digunakan.');
    expect(wrapper.text()).toContain('Terjadi kesalahan sistem.');
    expect(wrapper.find('input#name').classes()).toContain('is-invalid');
  });

  it('emits close event when Batal button is clicked', async () => {
    const wrapper = mountModal();

    const cancelBtn = wrapper.find('.btn-cancel');
    expect(cancelBtn.exists()).toBe(true);
    await cancelBtn.trigger('click');

    expect(wrapper.emitted('close')).toBeTruthy();
  });
});
