import { flushPromises, mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import WarehouseFormModal from '../components/WarehouseFormModal.vue';

describe('WarehouseFormModal.vue', () => {
  const mountModal = (props = {}) =>
    mount(WarehouseFormModal, {
      props: {
        isOpen: true,
        warehouse: null,
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

  it('renders "Tambah Gudang" title and "Simpan" button when warehouse is null', () => {
    const wrapper = mountModal({ warehouse: null });
    expect(wrapper.text()).toContain('Tambah Gudang');
    expect(wrapper.find('button[type="submit"]').text()).toBe('Simpan');
  });

  it('renders "Edit Gudang" title and "Perbarui" button when editing', () => {
    const wrapper = mountModal({
      warehouse: {
        id: 10,
        name: 'Gudang Farmasi Utama',
        code: '007',
        description: 'Penyimpanan persediaan farmasi'
      }
    });

    expect(wrapper.text()).toContain('Edit Gudang');
    expect(wrapper.find('button[type="submit"]').text()).toBe('Perbarui');

    expect(wrapper.find('input#name').element.value).toBe('Gudang Farmasi Utama');
    expect(wrapper.find('input#code').element.value).toBe('007');
    expect(wrapper.find('textarea#description').element.value).toBe('Penyimpanan persediaan farmasi');
  });

  it('contains strictly 3 form fields in exact order: Nama, Kode, Deskripsi', () => {
    const wrapper = mountModal();

    const inputs = wrapper.findAll('input, textarea');
    expect(inputs).toHaveLength(3);
    expect(inputs[0].attributes('id')).toBe('name');
    expect(inputs[1].attributes('id')).toBe('code');
    expect(inputs[2].attributes('id')).toBe('description');

    // No extra fields like status, type, location
    expect(wrapper.find('input#status').exists()).toBe(false);
    expect(wrapper.find('input#type').exists()).toBe(false);
    expect(wrapper.find('.toggle-input').exists()).toBe(false);
  });

  it('validates required name field before submitting', async () => {
    const wrapper = mountModal();

    const nameInput = wrapper.find('input#name');
    await nameInput.setValue('');

    await wrapper.find('form').trigger('submit');
    await flushPromises();

    expect(wrapper.emitted('submit')).toBeFalsy();
    expect(wrapper.text()).toContain('Nama gudang wajib diisi.');
  });

  it('emits submit with trimmed payload when form is valid, preserving code string format', async () => {
    const wrapper = mountModal();

    await wrapper.find('input#name').setValue('  Gudang IGD  ');
    await wrapper.find('input#code').setValue('003');
    await wrapper.find('textarea#description').setValue('  Gudang untuk obat gawat darurat  ');

    await wrapper.find('form').trigger('submit');
    await flushPromises();

    expect(wrapper.emitted('submit')).toBeTruthy();
    expect(wrapper.emitted('submit')[0][0]).toEqual({
      name: 'Gudang IGD',
      code: '003',
      description: 'Gudang untuk obat gawat darurat'
    });
  });

  it('sends null for optional fields left empty', async () => {
    const wrapper = mountModal();

    await wrapper.find('input#name').setValue('Gudang Sentral');
    await wrapper.find('input#code').setValue('');
    await wrapper.find('textarea#description').setValue('');

    await wrapper.find('form').trigger('submit');
    await flushPromises();

    expect(wrapper.emitted('submit')).toBeTruthy();
    expect(wrapper.emitted('submit')[0][0]).toEqual({
      name: 'Gudang Sentral',
      code: null,
      description: null
    });
  });

  it('displays backend validation errors when provided in props', () => {
    const wrapper = mountModal({
      errors: {
        name: ['Nama gudang sudah digunakan.'],
        code: ['Kode gudang sudah digunakan.'],
        description: ['Deskripsi terlalu panjang.'],
        general: ['Terjadi kesalahan pada data gudang.']
      }
    });

    expect(wrapper.text()).toContain('Nama gudang sudah digunakan.');
    expect(wrapper.text()).toContain('Kode gudang sudah digunakan.');
    expect(wrapper.text()).toContain('Deskripsi terlalu panjang.');
    expect(wrapper.text()).toContain('Terjadi kesalahan pada data gudang.');
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
