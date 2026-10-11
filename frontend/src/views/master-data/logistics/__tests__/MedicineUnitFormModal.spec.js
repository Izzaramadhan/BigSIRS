import { flushPromises, mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import MedicineUnitFormModal from '../components/MedicineUnitFormModal.vue';

describe('MedicineUnitFormModal.vue', () => {
  const mountModal = (props = {}) =>
    mount(MedicineUnitFormModal, {
      props: {
        isOpen: true,
        unit: null,
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

  it('renders "Tambah Satuan Obat" title and "Simpan" button when unit is null', () => {
    const wrapper = mountModal({ unit: null });
    expect(wrapper.text()).toContain('Tambah Satuan Obat');
    expect(wrapper.find('button[type="submit"]').text()).toBe('Simpan');
  });

  it('renders "Edit Satuan Obat" title and "Perbarui" button when editing', () => {
    const wrapper = mountModal({
      unit: {
        id: 10,
        name: 'Tablet',
        description: 'Bentuk sediaan tablet oral'
      }
    });

    expect(wrapper.text()).toContain('Edit Satuan Obat');
    expect(wrapper.find('button[type="submit"]').text()).toBe('Perbarui');

    const nameInput = wrapper.find('input#name');
    const descTextarea = wrapper.find('textarea#description');

    expect(nameInput.element.value).toBe('Tablet');
    expect(descTextarea.element.value).toBe('Bentuk sediaan tablet oral');
  });

  it('contains strictly 2 form fields: Nama and Deskripsi', () => {
    const wrapper = mountModal();

    expect(wrapper.find('input#name').exists()).toBe(true);
    expect(wrapper.find('textarea#description').exists()).toBe(true);

    // No extra fields like status toggle or code input
    expect(wrapper.find('input#code').exists()).toBe(false);
    expect(wrapper.find('input#is_active').exists()).toBe(false);
    expect(wrapper.find('.toggle-input').exists()).toBe(false);
  });

  it('validates required name field before submitting', async () => {
    const wrapper = mountModal();

    const nameInput = wrapper.find('input#name');
    await nameInput.setValue('');

    await wrapper.find('form').trigger('submit');
    await flushPromises();

    expect(wrapper.emitted('submit')).toBeFalsy();
    expect(wrapper.text()).toContain('Nama satuan obat wajib diisi.');
  });

  it('emits submit with trimmed payload when form is valid', async () => {
    const wrapper = mountModal();

    await wrapper.find('input#name').setValue('  Kapsul  ');
    await wrapper.find('textarea#description').setValue('  Sediaan kapsul  ');

    await wrapper.find('form').trigger('submit');
    await flushPromises();

    expect(wrapper.emitted('submit')).toBeTruthy();
    expect(wrapper.emitted('submit')[0][0]).toEqual({
      name: 'Kapsul',
      description: 'Sediaan kapsul'
    });
  });

  it('sends null description when description is left empty', async () => {
    const wrapper = mountModal();

    await wrapper.find('input#name').setValue('Kapsul');
    await wrapper.find('textarea#description').setValue('');

    await wrapper.find('form').trigger('submit');
    await flushPromises();

    expect(wrapper.emitted('submit')).toBeTruthy();
    expect(wrapper.emitted('submit')[0][0]).toEqual({
      name: 'Kapsul',
      description: null
    });
  });

  it('displays backend validation errors when provided in props', () => {
    const wrapper = mountModal({
      errors: {
        name: ['Nama satuan obat sudah digunakan.'],
        description: ['Deskripsi terlalu panjang.']
      }
    });

    expect(wrapper.text()).toContain('Nama satuan obat sudah digunakan.');
    expect(wrapper.text()).toContain('Deskripsi terlalu panjang.');
  });

  it('disables submit button and shows loading state when isSubmitting is true', () => {
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
