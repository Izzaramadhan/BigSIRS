import { flushPromises, mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import AddGenericDialog from '../components/AddGenericDialog.vue';

describe('AddGenericDialog.vue', () => {
  const mountDialog = (props = {}) =>
    mount(AddGenericDialog, {
      props: {
        isOpen: true,
        initialName: 'Amoxicillin Forte',
        isSubmitting: false,
        errorMessage: '',
        ...props
      },
      global: {
        stubs: {
          Teleport: true
        }
      }
    });

  it('renders confirmation title, info text, and pre-filled name input', async () => {
    const wrapper = mountDialog({ initialName: 'Paracetamol Drop' });
    await flushPromises();

    expect(wrapper.text()).toContain('Tambah Generik Baru');
    expect(wrapper.text()).toContain('Data generik “Paracetamol Drop” belum tersedia. Tambahkan sebagai data generik baru?');
    const input = wrapper.find('input#generic_new_name');
    expect(input.exists()).toBe(true);
    expect(input.element.value).toBe('Paracetamol Drop');
  });

  it('emits close event when Batal or close button is clicked', async () => {
    const wrapper = mountDialog();
    await flushPromises();

    const cancelBtn = wrapper.find('.btn-cancel');
    await cancelBtn.trigger('click');
    expect(wrapper.emitted('close')).toBeTruthy();

    const closeBtn = wrapper.find('.dialog-close-btn');
    await closeBtn.trigger('click');
    expect(wrapper.emitted('close').length).toBe(2);
  });

  it('validates empty name and displays error message without submitting', async () => {
    const wrapper = mountDialog();
    await flushPromises();

    const input = wrapper.find('input#generic_new_name');
    await input.setValue('   ');

    const submitBtn = wrapper.find('.btn-primary');
    await submitBtn.trigger('click');
    await flushPromises();

    expect(wrapper.emitted('submit')).toBeFalsy();
    expect(wrapper.text()).toContain('Nama generik wajib diisi.');
  });

  it('normalizes spaces and emits submit with normalized name', async () => {
    const wrapper = mountDialog({ initialName: '  Asam   Mefenamat  ' });
    await flushPromises();

    const submitBtn = wrapper.find('.btn-primary');
    await submitBtn.trigger('click');
    await flushPromises();

    expect(wrapper.emitted('submit')).toBeTruthy();
    expect(wrapper.emitted('submit')[0][0]).toBe('Asam Mefenamat');
  });

  it('displays backend error message when errorMessage prop is provided', async () => {
    const wrapper = mountDialog({
      errorMessage: 'Nama generik sudah terdaftar.'
    });
    await flushPromises();

    expect(wrapper.text()).toContain('Nama generik sudah terdaftar.');
    expect(wrapper.find('input#generic_new_name').classes()).toContain('is-invalid');
  });

  it('disables buttons and shows loading indicator when isSubmitting is true', async () => {
    const wrapper = mountDialog({
      isSubmitting: true
    });
    await flushPromises();

    const submitBtn = wrapper.find('.btn-primary');
    expect(submitBtn.attributes('disabled')).toBeDefined();
    expect(submitBtn.text()).toContain('Menyimpan...');

    const cancelBtn = wrapper.find('.btn-cancel');
    expect(cancelBtn.attributes('disabled')).toBeDefined();
  });
});
