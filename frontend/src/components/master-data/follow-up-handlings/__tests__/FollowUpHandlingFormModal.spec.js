import { describe, it, expect } from 'vitest';
import { mount, flushPromises } from '@vue/test-utils';
import FollowUpHandlingFormModal from '../FollowUpHandlingFormModal.vue';

describe('FollowUpHandlingFormModal.vue', () => {
  const mountModal = (props = {}) => {
    return mount(FollowUpHandlingFormModal, {
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

  it('renders modal with title "Tambah Penanganan Lanjutan" and button "Simpan" in create mode', () => {
    const wrapper = mountModal({ item: null });
    expect(wrapper.find('.modal-header h3').text()).toBe('Tambah Penanganan Lanjutan');
    expect(wrapper.find('button[type="submit"]').text()).toBe('Simpan');
  });

  it('renders modal with title "Edit Penanganan Lanjutan" and button "Simpan Perubahan" in edit mode', () => {
    const wrapper = mountModal({
      item: {
        id: 1,
        name: 'Sembuh'
      }
    });
    expect(wrapper.find('.modal-header h3').text()).toBe('Edit Penanganan Lanjutan');
    expect(wrapper.find('button[type="submit"]').text()).toBe('Simpan Perubahan');
  });

  it('contains field Nama with required marker and placeholder', () => {
    const wrapper = mountModal();
    const input = wrapper.find('#name');
    expect(input.exists()).toBe(true);
    expect(input.attributes('placeholder')).toBe('Masukkan nama penanganan lanjutan');
    expect(wrapper.find('label[for="name"]').classes()).toContain('required');
  });

  it('populates existing data in edit mode', async () => {
    const wrapper = mountModal({
      item: {
        id: 5,
        name: 'Meninggal'
      }
    });
    await flushPromises();

    expect(wrapper.find('#name').element.value).toBe('Meninggal');
  });

  it('emits submit with trimmed string', async () => {
    const wrapper = mountModal();

    await wrapper.find('#name').setValue('  Rujuk Internal  ');
    await wrapper.find('form').trigger('submit.prevent');

    expect(wrapper.emitted('submit')).toBeTruthy();
    expect(wrapper.emitted('submit')[0][0]).toEqual({
      name: 'Rujuk Internal'
    });
  });

  it('displays client validation error when field is empty', async () => {
    const wrapper = mountModal();

    await wrapper.find('#name').setValue('   ');
    await wrapper.find('form').trigger('submit.prevent');

    expect(wrapper.emitted('submit')).toBeFalsy();
    expect(wrapper.text()).toContain('Nama penanganan lanjutan wajib diisi.');
  });

  it('displays field validation errors from backend', () => {
    const wrapper = mountModal({
      errors: {
        name: ['Nama penanganan lanjutan sudah digunakan.']
      }
    });

    expect(wrapper.text()).toContain('Nama penanganan lanjutan sudah digunakan.');
  });
});
