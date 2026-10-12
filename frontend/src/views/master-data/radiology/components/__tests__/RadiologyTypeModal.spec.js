import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import RadiologyTypeModal from '../RadiologyTypeModal.vue';

describe('RadiologyTypeModal.vue', () => {
  const mountModal = (propsData = {}) => {
    return mount(RadiologyTypeModal, {
      props: {
        isOpen: true,
        type: null,
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

  it('renders correctly with default title for new type, placeholder, and default Status Aktif', () => {
    const wrapper = mountModal();

    expect(wrapper.text()).toContain('Tambah Item Radiologi');
    const nameInput = wrapper.find('input#name');
    expect(nameInput.element.value).toBe('');
    expect(nameInput.attributes('placeholder')).toBe('Masukkan tipe radiologi');

    const descInput = wrapper.find('textarea#description');
    expect(descInput.element.value).toBe('');
    expect(descInput.attributes('placeholder')).toBe('Masukkan deskripsi tipe radiologi');

    const activeRadio = wrapper.find('input#status_active');
    expect(activeRadio.element.checked).toBe(true);

    const inactiveRadio = wrapper.find('input#status_inactive');
    expect(inactiveRadio.element.checked).toBe(false);

    expect(wrapper.find('button[type="submit"]').text()).toContain('Simpan');
  });

  it('populates fields correctly when type prop is provided (Status Aktif)', () => {
    const type = {
      id: 1,
      name: 'Radiografi Konvensional',
      description: 'Foto rontgen umum',
      is_active: true
    };

    const wrapper = mountModal({ type });

    expect(wrapper.text()).toContain('Edit Item Radiologi');
    expect(wrapper.find('input#name').element.value).toBe('Radiografi Konvensional');
    expect(wrapper.find('textarea#description').element.value).toBe('Foto rontgen umum');
    expect(wrapper.find('input#status_active').element.checked).toBe(true);
    expect(wrapper.find('input#status_inactive').element.checked).toBe(false);
    expect(wrapper.find('button[type="submit"]').text()).toContain('Simpan Perubahan');
  });

  it('populates fields correctly when type prop is provided with Status Nonaktif', () => {
    const type = {
      id: 2,
      name: 'USG Khusus',
      description: null,
      is_active: false
    };

    const wrapper = mountModal({ type });

    expect(wrapper.text()).toContain('Edit Item Radiologi');
    expect(wrapper.find('input#name').element.value).toBe('USG Khusus');
    expect(wrapper.find('textarea#description').element.value).toBe('');
    expect(wrapper.find('input#status_active').element.checked).toBe(false);
    expect(wrapper.find('input#status_inactive').element.checked).toBe(true);
  });

  it('handles null description by displaying empty string (not "null")', () => {
    const type = {
      id: 3,
      name: 'MRI',
      description: null,
      is_active: true
    };

    const wrapper = mountModal({ type });
    expect(wrapper.find('textarea#description').element.value).toBe('');
    expect(wrapper.find('textarea#description').element.value).not.toBe('null');
  });

  it('emits submit event with trimmed payload and status Aktif', async () => {
    const wrapper = mountModal();

    await wrapper.find('input#name').setValue('  CT Scan  ');
    await wrapper.find('textarea#description').setValue('  Computed Tomography Scan  ');

    await wrapper.find('form').trigger('submit.prevent');

    expect(wrapper.emitted('submit')).toBeTruthy();
    const payload = wrapper.emitted('submit')[0][0];
    expect(payload.name).toBe('CT Scan');
    expect(payload.description).toBe('Computed Tomography Scan');
    expect(payload.is_active).toBe(true);
  });

  it('emits submit event with status Nonaktif when Nonaktif radio is selected', async () => {
    const wrapper = mountModal();

    await wrapper.find('input#name').setValue('Radiologi Nuklir');
    await wrapper.find('input#status_inactive').setValue(true);

    await wrapper.find('form').trigger('submit.prevent');

    expect(wrapper.emitted('submit')).toBeTruthy();
    const payload = wrapper.emitted('submit')[0][0];
    expect(payload.name).toBe('Radiologi Nuklir');
    expect(payload.is_active).toBe(false);
  });

  it('validates required name field on client side before emit', async () => {
    const wrapper = mountModal();

    await wrapper.find('input#name').setValue('   ');
    await wrapper.find('form').trigger('submit.prevent');

    expect(wrapper.emitted('submit')).toBeFalsy();
    expect(wrapper.text()).toContain('Tipe radiologi wajib diisi.');
  });

  it('renders field validation errors when passed in props', () => {
    const wrapper = mountModal({
      errors: {
        name: ['Kelompok item radiologi sudah digunakan.'],
        description: ['Deskripsi terlalu panjang.'],
        general: 'Terjadi kesalahan sistem.'
      }
    });

    expect(wrapper.text()).toContain('Kelompok item radiologi sudah digunakan.');
    expect(wrapper.text()).toContain('Deskripsi terlalu panjang.');
    expect(wrapper.text()).toContain('Terjadi kesalahan sistem.');
    expect(wrapper.find('input#name').classes()).toContain('is-invalid');
    expect(wrapper.find('textarea#description').classes()).toContain('is-invalid');
  });

  it('disables submit button and inputs when isSubmitting is true', () => {
    const wrapper = mountModal({
      isSubmitting: true
    });

    const submitBtn = wrapper.find('button[type="submit"]');
    expect(submitBtn.attributes('disabled')).toBeDefined();
    expect(submitBtn.text()).toContain('Menyimpan...');
  });

  it('emits close event when Batal button is clicked', async () => {
    const wrapper = mountModal();

    const cancelBtn = wrapper.find('.btn-cancel');
    expect(cancelBtn.exists()).toBe(true);
    await cancelBtn.trigger('click');

    expect(wrapper.emitted('close')).toBeTruthy();
  });
});
