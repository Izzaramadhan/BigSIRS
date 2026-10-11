import { flushPromises, mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import MedicationSignaFormModal from '../components/MedicationSignaFormModal.vue';

describe('MedicationSignaFormModal.vue', () => {
  const mountModal = (props = {}) =>
    mount(MedicationSignaFormModal, {
      props: {
        isOpen: true,
        signa: null,
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

  it('renders "Tambah Signa Obat" title and "Simpan" button when signa is null', () => {
    const wrapper = mountModal({ signa: null });
    expect(wrapper.text()).toContain('Tambah Signa Obat');
    expect(wrapper.find('button[type="submit"]').text()).toBe('Simpan');
  });

  it('renders "Edit Signa Obat" title and "Perbarui" button when editing', () => {
    const wrapper = mountModal({
      signa: {
        id: 10,
        name: '3 x 1 sehari'
      }
    });

    expect(wrapper.text()).toContain('Edit Signa Obat');
    expect(wrapper.find('button[type="submit"]').text()).toBe('Perbarui');

    expect(wrapper.find('input#name').element.value).toBe('3 x 1 sehari');
  });

  it('contains strictly 1 form field: Nama Signa Obat', () => {
    const wrapper = mountModal();

    const inputs = wrapper.findAll('input');
    expect(inputs).toHaveLength(1);
    expect(inputs[0].attributes('id')).toBe('name');

    // No extra fields like status, code, description, toggle, object
    expect(wrapper.find('input#code').exists()).toBe(false);
    expect(wrapper.find('input#object_code').exists()).toBe(false);
    expect(wrapper.find('textarea#description').exists()).toBe(false);
    expect(wrapper.find('input#status').exists()).toBe(false);
    expect(wrapper.find('.toggle-input').exists()).toBe(false);
  });

  it('validates required name field before submitting', async () => {
    const wrapper = mountModal();

    const nameInput = wrapper.find('input#name');
    await nameInput.setValue('');

    await wrapper.find('form').trigger('submit');
    await flushPromises();

    expect(wrapper.emitted('submit')).toBeFalsy();
    expect(wrapper.text()).toContain('Nama signa obat wajib diisi.');
  });

  it('emits submit with trimmed payload when form is valid', async () => {
    const wrapper = mountModal();

    await wrapper.find('input#name').setValue('  3 x 1 sehari sesudah makan  ');

    await wrapper.find('form').trigger('submit');
    await flushPromises();

    expect(wrapper.emitted('submit')).toBeTruthy();
    expect(wrapper.emitted('submit')[0][0]).toEqual({
      name: '3 x 1 sehari sesudah makan'
    });
  });

  it('displays backend validation errors when provided in props', () => {
    const wrapper = mountModal({
      errors: {
        name: ['Nama signa obat sudah digunakan.'],
        general: ['Terjadi kesalahan data.']
      }
    });

    expect(wrapper.text()).toContain('Nama signa obat sudah digunakan.');
    expect(wrapper.text()).toContain('Terjadi kesalahan data.');
  });

  it('disables submit button when isSubmitting is true', () => {
    const wrapper = mountModal({
      isSubmitting: true
    });

    const submitBtn = wrapper.find('button[type="submit"]');
    expect(submitBtn.attributes('disabled')).toBeDefined();
  });

  it('emits close event when cancel button is clicked', async () => {
    const wrapper = mountModal();

    const cancelBtn = wrapper.find('button.btn-cancel');
    await cancelBtn.trigger('click');

    expect(wrapper.emitted('close')).toBeTruthy();
  });
});
