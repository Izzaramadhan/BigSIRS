import { flushPromises, mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import MedicationRouteFormModal from '../components/MedicationRouteFormModal.vue';

describe('MedicationRouteFormModal.vue', () => {
  const mountModal = (props = {}) =>
    mount(MedicationRouteFormModal, {
      props: {
        isOpen: true,
        route: null,
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

  it('renders "Tambah Jalur Masuk Obat" title and "Simpan" button when route is null', () => {
    const wrapper = mountModal({ route: null });
    expect(wrapper.text()).toContain('Tambah Jalur Masuk Obat');
    expect(wrapper.find('button[type="submit"]').text()).toBe('Simpan');
  });

  it('renders "Edit Jalur Masuk Obat" title and "Perbarui" button when editing', () => {
    const wrapper = mountModal({
      route: {
        id: 10,
        name: 'ORAL',
        object_code: 'MDCTx000000002'
      }
    });

    expect(wrapper.text()).toContain('Edit Jalur Masuk Obat');
    expect(wrapper.find('button[type="submit"]').text()).toBe('Perbarui');

    expect(wrapper.find('input#name').element.value).toBe('ORAL');
    expect(wrapper.find('input#object_code').element.value).toBe('MDCTx000000002');
  });

  it('contains strictly 2 form fields in exact order: Nama Jalur Masuk Obat and Objek', () => {
    const wrapper = mountModal();

    const inputs = wrapper.findAll('input');
    expect(inputs).toHaveLength(2);
    expect(inputs[0].attributes('id')).toBe('name');
    expect(inputs[1].attributes('id')).toBe('object_code');

    // No extra fields like status, code, description, toggle
    expect(wrapper.find('input#code').exists()).toBe(false);
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
    expect(wrapper.text()).toContain('Nama jalur masuk obat wajib diisi.');
  });

  it('emits submit with trimmed payload when form is valid', async () => {
    const wrapper = mountModal();

    await wrapper.find('input#name').setValue('  INTRAVENA  ');
    await wrapper.find('input#object_code').setValue('  MDCTx000000003  ');

    await wrapper.find('form').trigger('submit');
    await flushPromises();

    expect(wrapper.emitted('submit')).toBeTruthy();
    expect(wrapper.emitted('submit')[0][0]).toEqual({
      name: 'INTRAVENA',
      object_code: 'MDCTx000000003'
    });
  });

  it('sends null for optional Objek field left empty', async () => {
    const wrapper = mountModal();

    await wrapper.find('input#name').setValue('Jalur Mandiri');
    await wrapper.find('input#object_code').setValue('');

    await wrapper.find('form').trigger('submit');
    await flushPromises();

    expect(wrapper.emitted('submit')).toBeTruthy();
    expect(wrapper.emitted('submit')[0][0]).toEqual({
      name: 'Jalur Mandiri',
      object_code: null
    });
  });

  it('displays backend validation errors when provided in props', () => {
    const wrapper = mountModal({
      errors: {
        name: ['Nama jalur masuk obat sudah digunakan.'],
        object_code: ['Kode objek tidak valid.'],
        general: ['Terjadi kesalahan data.']
      }
    });

    expect(wrapper.text()).toContain('Nama jalur masuk obat sudah digunakan.');
    expect(wrapper.text()).toContain('Kode objek tidak valid.');
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
