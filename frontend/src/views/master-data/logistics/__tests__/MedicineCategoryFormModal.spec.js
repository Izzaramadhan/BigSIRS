import { flushPromises, mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import MedicineCategoryFormModal from '../components/MedicineCategoryFormModal.vue';

describe('MedicineCategoryFormModal.vue', () => {
  const mountModal = (props = {}) =>
    mount(MedicineCategoryFormModal, {
      props: {
        isOpen: true,
        category: null,
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

  it('renders "Tambah Kategori Obat" title when category is null', () => {
    const wrapper = mountModal({ category: null });
    expect(wrapper.text()).toContain('Tambah Kategori Obat');
    expect(wrapper.find('button[type="submit"]').text()).toBe('Simpan');
  });

  it('renders "Edit Kategori Obat" title and populates form when editing', () => {
    const wrapper = mountModal({
      category: {
        id: 10,
        name: 'Analgesik',
        description: 'Obat penahan rasa sakit'
      }
    });

    expect(wrapper.text()).toContain('Edit Kategori Obat');
    expect(wrapper.find('button[type="submit"]').text()).toBe('Simpan Perubahan');

    const nameInput = wrapper.find('input#name');
    const descTextarea = wrapper.find('textarea#description');

    expect(nameInput.element.value).toBe('Analgesik');
    expect(descTextarea.element.value).toBe('Obat penahan rasa sakit');
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
    expect(wrapper.text()).toContain('Nama kategori obat wajib diisi.');
  });

  it('emits submit with trimmed payload when form is valid', async () => {
    const wrapper = mountModal();

    await wrapper.find('input#name').setValue('  Antipiretik  ');
    await wrapper.find('textarea#description').setValue('  Pereda demam  ');

    await wrapper.find('form').trigger('submit');
    await flushPromises();

    expect(wrapper.emitted('submit')).toBeTruthy();
    expect(wrapper.emitted('submit')[0][0]).toEqual({
      name: 'Antipiretik',
      description: 'Pereda demam'
    });
  });

  it('sends null description when description is left empty', async () => {
    const wrapper = mountModal();

    await wrapper.find('input#name').setValue('Antipiretik');
    await wrapper.find('textarea#description').setValue('');

    await wrapper.find('form').trigger('submit');
    await flushPromises();

    expect(wrapper.emitted('submit')).toBeTruthy();
    expect(wrapper.emitted('submit')[0][0]).toEqual({
      name: 'Antipiretik',
      description: null
    });
  });

  it('displays backend validation errors when provided in props', () => {
    const wrapper = mountModal({
      errors: {
        name: ['Nama kategori obat sudah digunakan.'],
        description: ['Deskripsi terlalu panjang.']
      }
    });

    expect(wrapper.text()).toContain('Nama kategori obat sudah digunakan.');
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
