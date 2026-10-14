import { flushPromises, mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import MedicineFormModal from '../components/MedicineFormModal.vue';
import lookupService from '@/services/lookup.service';
import genericMedicinesService from '@/services/master-data/genericMedicines.service';

vi.mock('@/services/lookup.service', () => ({
  default: {
    getMedicineUnits: vi.fn(),
    getMedicineCategories: vi.fn(),
    getMedicineClassifications: vi.fn(),
    getMedicineRoutes: vi.fn(),
    getGenericMedicines: vi.fn()
  }
}));

vi.mock('@/services/master-data/genericMedicines.service', () => ({
  default: {
    getGenericMedicines: vi.fn(),
    createGenericMedicine: vi.fn()
  }
}));

describe('MedicineFormModal.vue', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    lookupService.getMedicineUnits.mockResolvedValue([{ id: 1, name: 'Tablet' }]);
    lookupService.getMedicineCategories.mockResolvedValue([{ id: 1, name: 'Analgesik' }]);
    lookupService.getMedicineClassifications.mockResolvedValue([{ id: 1, name: 'Obat Bebas' }]);
    lookupService.getMedicineRoutes.mockResolvedValue([{ id: 1, name: 'Oral' }]);
    lookupService.getGenericMedicines.mockResolvedValue([{ id: 1, name: 'Paracetamol' }]);
    genericMedicinesService.createGenericMedicine.mockReset();
  });

  const mountModal = (props = {}) =>
    mount(MedicineFormModal, {
      props: {
        isOpen: true,
        medicine: null,
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

  it('renders "Tambah Obat" title and "Simpan" button when medicine is null', async () => {
    const wrapper = mountModal({ medicine: null });
    await flushPromises();

    expect(wrapper.text()).toContain('Tambah Obat');
    expect(wrapper.find('button[type="submit"]').text()).toBe('Simpan');
  });

  it('renders "Edit Obat" title and "Perbarui" button when editing', async () => {
    const wrapper = mountModal({
      medicine: {
        id: 10,
        code: 'OBT010',
        name: 'Paracetamol 500mg',
        kfa_code: '930010',
        function: 'Antipiretik',
        medicine_unit_id: 1,
        medicine_category_id: 1,
        medicine_classification_id: 1,
        medicine_route_id: 1,
        generic_medicine_id: 1,
        description: 'Obat sakit kepala',
        generic: { id: 1, name: 'Paracetamol' }
      }
    });
    await flushPromises();

    expect(wrapper.text()).toContain('Edit Obat');
    expect(wrapper.find('button[type="submit"]').text()).toBe('Perbarui');

    expect(wrapper.find('input#code').element.value).toBe('OBT010');
    expect(wrapper.find('input#name').element.value).toBe('Paracetamol 500mg');
    expect(wrapper.find('input#kfa_code').element.value).toBe('930010');
    expect(wrapper.find('input#function').element.value).toBe('Antipiretik');
    expect(wrapper.find('select#medicine_unit_id').element.value).toBe('1');
    expect(wrapper.find('select#medicine_category_id').element.value).toBe('1');
    expect(wrapper.find('select#medicine_classification_id').element.value).toBe('1');
    expect(wrapper.find('select#medicine_route_id').element.value).toBe('1');
    expect(wrapper.find('select#generic_medicine_id').element.value).toBe('1');
    expect(wrapper.find('textarea#description').element.value).toBe('Obat sakit kepala');
  });

  it('contains strictly 10 form fields in order', async () => {
    const wrapper = mountModal();
    await flushPromises();

    expect(wrapper.find('input#code').exists()).toBe(true);
    expect(wrapper.find('input#name').exists()).toBe(true);
    expect(wrapper.find('input#kfa_code').exists()).toBe(true);
    expect(wrapper.find('input#function').exists()).toBe(true);
    expect(wrapper.find('select#medicine_unit_id').exists()).toBe(true);
    expect(wrapper.find('select#medicine_category_id').exists()).toBe(true);
    expect(wrapper.find('select#medicine_classification_id').exists()).toBe(true);
    expect(wrapper.find('select#medicine_route_id').exists()).toBe(true);
    expect(wrapper.find('select#generic_medicine_id').exists()).toBe(true);
    expect(wrapper.find('textarea#description').exists()).toBe(true);
  });

  it('validates required name field before submitting', async () => {
    const wrapper = mountModal();
    await flushPromises();

    const nameInput = wrapper.find('input#name');
    await nameInput.setValue('');

    await wrapper.find('form').trigger('submit');
    await flushPromises();

    expect(wrapper.emitted('submit')).toBeFalsy();
    expect(wrapper.text()).toContain('Nama obat wajib diisi.');
  });

  it('emits submit with properly structured payload when form is valid', async () => {
    const wrapper = mountModal();
    await flushPromises();

    await wrapper.find('input#code').setValue('OBT001');
    await wrapper.find('input#name').setValue('Paracetamol 500mg');
    await wrapper.find('input#kfa_code').setValue('930001');
    await wrapper.find('input#function').setValue('Pereda Demam');
    await wrapper.find('select#medicine_unit_id').setValue('1');
    await wrapper.find('select#medicine_category_id').setValue('1');
    await wrapper.find('select#medicine_classification_id').setValue('1');
    await wrapper.find('select#medicine_route_id').setValue('1');
    await wrapper.find('select#generic_medicine_id').setValue('1');
    await wrapper.find('textarea#description').setValue('Keterangan obat');

    await wrapper.find('form').trigger('submit');
    await flushPromises();

    expect(wrapper.emitted('submit')).toBeTruthy();
    expect(wrapper.emitted('submit')[0][0]).toEqual({
      code: 'OBT001',
      name: 'Paracetamol 500mg',
      kfa_code: '930001',
      function: 'Pereda Demam',
      medicine_unit_id: 1,
      medicine_category_id: 1,
      medicine_classification_id: 1,
      medicine_route_id: 1,
      generic_medicine_id: 1,
      description: 'Keterangan obat'
    });
  });

  it('sends null for optional fields left empty', async () => {
    const wrapper = mountModal();
    await flushPromises();

    await wrapper.find('input#name').setValue('Amoxicillin');

    await wrapper.find('form').trigger('submit');
    await flushPromises();

    expect(wrapper.emitted('submit')).toBeTruthy();
    expect(wrapper.emitted('submit')[0][0]).toEqual({
      code: null,
      name: 'Amoxicillin',
      kfa_code: null,
      function: null,
      medicine_unit_id: null,
      medicine_category_id: null,
      medicine_classification_id: null,
      medicine_route_id: null,
      generic_medicine_id: null,
      description: null
    });
  });

  it('displays backend validation errors when provided in props', async () => {
    const wrapper = mountModal({
      errors: {
        code: ['Kode obat sudah digunakan.'],
        name: ['Nama obat tidak valid.'],
        general: ['Terjadi kesalahan validasi server.']
      }
    });
    await flushPromises();

    expect(wrapper.text()).toContain('Kode obat sudah digunakan.');
    expect(wrapper.text()).toContain('Nama obat tidak valid.');
    expect(wrapper.text()).toContain('Terjadi kesalahan validasi server.');
  });

  it('disables submit button when isSubmitting is true', async () => {
    const wrapper = mountModal({
      isSubmitting: true
    });
    await flushPromises();

    const submitBtn = wrapper.find('button[type="submit"]');
    expect(submitBtn.attributes('disabled')).toBeDefined();
  });

  it('emits close event when cancel button is clicked', async () => {
    const wrapper = mountModal();
    await flushPromises();

    const cancelBtn = wrapper.find('button.btn-cancel');
    await cancelBtn.trigger('click');

    expect(wrapper.emitted('close')).toBeTruthy();
  });

  it('renders Generik searchable select with correct placeholder and search placeholder', async () => {
    const wrapper = mountModal();
    await flushPromises();

    expect(wrapper.text()).toContain('Generik');
    const searchableBox = wrapper.find('.selected-box');
    expect(searchableBox.exists()).toBe(true);
    expect(searchableBox.text()).toContain('Cari atau tambahkan generik');

    // Open dropdown
    await searchableBox.trigger('click');
    await flushPromises();

    const searchInput = wrapper.find('.search-box input');
    expect(searchInput.exists()).toBe(true);
    expect(searchInput.attributes('placeholder')).toBe('Cari generik...');
  });

  it('shows creatable option when typed query does not match existing generic, and handles creation flow', async () => {
    lookupService.getGenericMedicines.mockResolvedValue([]);
    genericMedicinesService.createGenericMedicine.mockResolvedValue({
      id: 999,
      name: 'Cefixime Trihydrate'
    });

    const wrapper = mountModal();
    await flushPromises();

    // Open dropdown
    await wrapper.find('.selected-box').trigger('click');
    await flushPromises();

    // Type query
    const searchInput = wrapper.find('.search-box input');
    await searchInput.setValue('Cefixime Trihydrate');
    await searchInput.trigger('input');
    await flushPromises();

    // Check creatable option appears
    const createOption = wrapper.find('.create-option-item');
    expect(createOption.exists()).toBe(true);
    expect(createOption.text()).toContain('Tambahkan “Cefixime Trihydrate” sebagai generik baru');

    // Click create option
    await createOption.trigger('click');
    await flushPromises();

    // AddGenericDialog should be open
    const dialog = wrapper.findComponent({ name: 'AddGenericDialog' });
    expect(dialog.exists()).toBe(true);
    expect(dialog.props('isOpen')).toBe(true);
    expect(dialog.props('initialName')).toBe('Cefixime Trihydrate');

    // Submit dialog
    await dialog.vm.$emit('submit', 'Cefixime Trihydrate');
    await flushPromises();

    expect(genericMedicinesService.createGenericMedicine).toHaveBeenCalledWith({
      name: 'Cefixime Trihydrate'
    });
    expect(wrapper.emitted('toast')).toBeTruthy();
    expect(wrapper.emitted('toast')[0][0]).toEqual({
      type: 'success',
      title: 'Berhasil',
      message: 'Data generik berhasil ditambahkan.'
    });
    expect(wrapper.find('select#generic_medicine_id').element.value).toBe('999');
    expect(dialog.props('isOpen')).toBe(false);
  });

  it('does not show creatable option if query matches an existing option exactly', async () => {
    lookupService.getGenericMedicines.mockResolvedValue([
      { id: 1, name: 'Paracetamol' }
    ]);

    const wrapper = mountModal();
    await flushPromises();

    await wrapper.find('.selected-box').trigger('click');
    await flushPromises();

    const searchInput = wrapper.find('.search-box input');
    await searchInput.setValue('Paracetamol');
    await searchInput.trigger('input');
    await flushPromises();

    expect(wrapper.find('.create-option-item').exists()).toBe(false);
  });

  it('keeps confirmation dialog open and displays error when generic creation fails', async () => {
    lookupService.getGenericMedicines.mockResolvedValue([]);
    genericMedicinesService.createGenericMedicine.mockRejectedValue({
      response: {
        status: 422,
        data: {
          errors: {
            name: ['Nama generik sudah terdaftar.']
          }
        }
      }
    });

    const wrapper = mountModal();
    await flushPromises();

    await wrapper.find('.selected-box').trigger('click');
    await flushPromises();

    const searchInput = wrapper.find('.search-box input');
    await searchInput.setValue('Paracetamol');
    await searchInput.trigger('input');
    await flushPromises();

    // Trigger create
    const createOption = wrapper.find('.create-option-item');
    if (createOption.exists()) {
      await createOption.trigger('click');
      await flushPromises();
    }

    const dialog = wrapper.findComponent({ name: 'AddGenericDialog' });
    await dialog.vm.$emit('submit', 'Paracetamol');
    await flushPromises();

    expect(dialog.props('isOpen')).toBe(true);
    expect(dialog.props('errorMessage')).toBe('Nama generik sudah terdaftar.');
  });

  it('preserves existing generic on edit even if not in default lookup list', async () => {
    lookupService.getGenericMedicines.mockResolvedValue([
      { id: 1, name: 'Paracetamol' }
    ]);

    const wrapper = mountModal({
      medicine: {
        id: 5,
        name: 'Ranitidine 150mg',
        generic_medicine_id: 16,
        generic: { id: 16, name: 'RANITIDINE TABLET 150 MG' }
      }
    });
    await flushPromises();

    const selectedBox = wrapper.find('.selected-box');
    expect(selectedBox.text()).toContain('RANITIDINE TABLET 150 MG');
    expect(wrapper.find('select#generic_medicine_id').element.value).toBe('16');
  });

  it('handles empty generic search results showing "Data generik tidak ditemukan"', async () => {
    lookupService.getGenericMedicines.mockResolvedValue([]);

    const wrapper = mountModal();
    await flushPromises();

    // Open dropdown
    await wrapper.find('.selected-box').trigger('click');
    await flushPromises();

    expect(wrapper.find('.no-options').text()).toContain('Data generik tidak ditemukan');
  });
});
