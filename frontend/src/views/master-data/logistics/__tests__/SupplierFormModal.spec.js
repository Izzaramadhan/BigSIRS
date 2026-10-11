import { flushPromises, mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import SupplierFormModal from '../components/SupplierFormModal.vue';

describe('SupplierFormModal.vue', () => {
  const mountModal = (props = {}) =>
    mount(SupplierFormModal, {
      props: {
        isOpen: true,
        supplier: null,
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

  it('renders "Tambah Supplier" title and "Simpan" button when supplier is null', () => {
    const wrapper = mountModal({ supplier: null });
    expect(wrapper.text()).toContain('Tambah Supplier');
    expect(wrapper.find('button[type="submit"]').text()).toBe('Simpan');
  });

  it('renders "Edit Supplier" title and "Perbarui" button when editing', () => {
    const wrapper = mountModal({
      supplier: {
        id: 10,
        name: 'PT Kimia Farma',
        phone: '08123456789',
        address: 'Jl. Veteran No. 10'
      }
    });

    expect(wrapper.text()).toContain('Edit Supplier');
    expect(wrapper.find('button[type="submit"]').text()).toBe('Perbarui');

    expect(wrapper.find('input#name').element.value).toBe('PT Kimia Farma');
    expect(wrapper.find('input#phone').element.value).toBe('08123456789');
    expect(wrapper.find('textarea#address').element.value).toBe('Jl. Veteran No. 10');
  });

  it('contains strictly 3 form fields: Nama, Telp, Alamat', () => {
    const wrapper = mountModal();

    expect(wrapper.find('input#name').exists()).toBe(true);
    expect(wrapper.find('input#phone').exists()).toBe(true);
    expect(wrapper.find('textarea#address').exists()).toBe(true);

    // No extra fields like code, status, email
    expect(wrapper.find('input#code').exists()).toBe(false);
    expect(wrapper.find('input#email').exists()).toBe(false);
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
    expect(wrapper.text()).toContain('Nama supplier wajib diisi.');
  });

  it('emits submit with trimmed payload when form is valid, preserving phone string format', async () => {
    const wrapper = mountModal();

    await wrapper.find('input#name').setValue('  PT Indofarma  ');
    await wrapper.find('input#phone').setValue('02198765432');
    await wrapper.find('textarea#address').setValue('  Jl. Sudirman No. 5  ');

    await wrapper.find('form').trigger('submit');
    await flushPromises();

    expect(wrapper.emitted('submit')).toBeTruthy();
    expect(wrapper.emitted('submit')[0][0]).toEqual({
      name: 'PT Indofarma',
      phone: '02198765432',
      address: 'Jl. Sudirman No. 5'
    });
  });

  it('sends null for optional fields left empty', async () => {
    const wrapper = mountModal();

    await wrapper.find('input#name').setValue('PT Medika');
    await wrapper.find('input#phone').setValue('');
    await wrapper.find('textarea#address').setValue('');

    await wrapper.find('form').trigger('submit');
    await flushPromises();

    expect(wrapper.emitted('submit')).toBeTruthy();
    expect(wrapper.emitted('submit')[0][0]).toEqual({
      name: 'PT Medika',
      phone: null,
      address: null
    });
  });

  it('displays backend validation errors when provided in props', () => {
    const wrapper = mountModal({
      errors: {
        name: ['Nama supplier sudah digunakan.'],
        phone: ['Nomor telepon terlalu panjang.'],
        general: ['Terjadi kesalahan pada data.']
      }
    });

    expect(wrapper.text()).toContain('Nama supplier sudah digunakan.');
    expect(wrapper.text()).toContain('Nomor telepon terlalu panjang.');
    expect(wrapper.text()).toContain('Terjadi kesalahan pada data.');
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
