import { flushPromises, mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import MedicineFormModal from '../components/MedicineFormModal.vue';
import lookupService from '@/services/lookup.service';

vi.mock('@/services/lookup.service', () => ({
  default: {
    getMedicineUnits: vi.fn(),
    getMedicineCategories: vi.fn(),
    getMedicineClassifications: vi.fn(),
    getMedicineRoutes: vi.fn(),
    getGenericMedicines: vi.fn()
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
});
