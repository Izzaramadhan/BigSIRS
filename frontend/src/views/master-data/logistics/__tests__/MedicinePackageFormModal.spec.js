import { flushPromises, mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import MedicinePackageFormModal from '../components/MedicinePackageFormModal.vue';
import { medicinesService } from '@/services/master-data/medicines.service';

vi.mock('@/services/master-data/medicines.service', () => ({
  medicinesService: {
    getMedicines: vi.fn(),
    getMedicine: vi.fn()
  }
}));

describe('MedicinePackageFormModal.vue', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    medicinesService.getMedicines.mockResolvedValue({
      data: [
        { id: 1, name: 'Paracetamol 500mg', code: 'MED001', selling_price_per_unit: 5000, unit: { name: 'Tablet' } },
        { id: 2, name: 'Amoxicillin 500mg', code: 'MED002', selling_price_per_unit: 10000, unit: { name: 'Kapsul' } }
      ]
    });
  });

  const mountModal = (props = {}) =>
    mount(MedicinePackageFormModal, {
      props: {
        isOpen: true,
        packageData: null,
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

  it('renders "Tambah Paket Obat" title and "Simpan" button when packageData is null', () => {
    const wrapper = mountModal({ packageData: null });
    expect(wrapper.text()).toContain('Tambah Paket Obat');
    expect(wrapper.find('button[type="submit"]').text()).toBe('Simpan');
  });

  it('renders "Edit Paket Obat" title and "Perbarui" button when editing', () => {
    const wrapper = mountModal({
      packageData: {
        id: 10,
        name: 'Paket Anak Sehat',
        price: 25000,
        quantity: 5,
        description: 'Paket multivitamin anak',
        items: [
          {
            medicine_id: 1,
            medicine: { id: 1, name: 'Paracetamol Drop', unit: { name: 'Botol' } },
            quantity: 2,
            unit_price: 12500,
            subtotal: 25000
          }
        ]
      }
    });

    expect(wrapper.text()).toContain('Edit Paket Obat');
    expect(wrapper.find('button[type="submit"]').text()).toBe('Perbarui');

    expect(wrapper.find('input#name').element.value).toBe('Paket Anak Sehat');
    expect(wrapper.find('input#price').element.value).toBe('25000');
    expect(wrapper.find('input#quantity').element.value).toBe('5');
    expect(wrapper.find('textarea#description').element.value).toBe('Paket multivitamin anak');
    expect(wrapper.text()).toContain('Paracetamol Drop');
  });

  it('validates required name field before submitting', async () => {
    const wrapper = mountModal();

    await wrapper.find('input#name').setValue('');
    await wrapper.find('form').trigger('submit');
    await flushPromises();

    expect(wrapper.emitted('submit')).toBeFalsy();
    expect(wrapper.text()).toContain('Nama paket obat wajib diisi.');
  });

  it('validates that package must have at least one medicine item', async () => {
    const wrapper = mountModal();

    await wrapper.find('input#name').setValue('Paket Tanpa Obat');
    await wrapper.find('form').trigger('submit');
    await flushPromises();

    expect(wrapper.emitted('submit')).toBeFalsy();
    expect(wrapper.text()).toContain('Paket obat harus memiliki minimal satu item obat.');
  });

  it('calculates subtotal and package price when item quantity or price changes', async () => {
    const wrapper = mountModal({
      packageData: {
        id: 1,
        name: 'Paket Test',
        price: 5000,
        quantity: 1,
        items: [
          {
            medicine_id: 1,
            medicine: { id: 1, name: 'Paracetamol' },
            quantity: 1,
            unit_price: 5000,
            subtotal: 5000
          }
        ]
      }
    });

    const qtyInput = wrapper.find('.col-item-qty input');
    await qtyInput.setValue(3);

    // subtotal should be 3 * 5000 = 15000
    expect(wrapper.text()).toContain('15.000');
    expect(wrapper.find('input#price').element.value).toBe('15000');
  });

  it('removes item from list and recalculates package total', async () => {
    const wrapper = mountModal({
      packageData: {
        id: 1,
        name: 'Paket Test',
        price: 15000,
        quantity: 1,
        items: [
          {
            medicine_id: 1,
            medicine: { id: 1, name: 'Paracetamol' },
            quantity: 1,
            unit_price: 5000,
            subtotal: 5000
          },
          {
            medicine_id: 2,
            medicine: { id: 2, name: 'Amoxicillin' },
            quantity: 1,
            unit_price: 10000,
            subtotal: 10000
          }
        ]
      }
    });

    const removeBtns = wrapper.findAll('.btn-remove-item');
    expect(removeBtns).toHaveLength(2);

    // Remove first item
    await removeBtns[0].trigger('click');

    expect(wrapper.findAll('.items-table tbody tr')).toHaveLength(1);
    expect(wrapper.find('input#price').element.value).toBe('10000');
  });

  it('emits submit with clean payload when valid', async () => {
    const wrapper = mountModal({
      packageData: {
        id: 5,
        name: '  Paket Bersih  ',
        price: 10000,
        quantity: 2,
        description: '  Deskripsi singkat  ',
        items: [
          {
            medicine_id: 1,
            medicine: { id: 1, name: 'Paracetamol' },
            quantity: 2,
            unit_price: 5000,
            subtotal: 10000
          }
        ]
      }
    });

    await wrapper.find('form').trigger('submit');
    await flushPromises();

    expect(wrapper.emitted('submit')).toBeTruthy();
    expect(wrapper.emitted('submit')[0][0]).toEqual({
      name: 'Paket Bersih',
      price: 10000,
      quantity: 2,
      description: 'Deskripsi singkat',
      items: [
        {
          medicine_id: 1,
          quantity: 2,
          unit_price: 5000,
          subtotal: 10000
        }
      ]
    });
  });

  it('displays backend validation errors when provided in props', () => {
    const wrapper = mountModal({
      errors: {
        name: ['Nama paket obat sudah digunakan.'],
        price: ['Harga paket tidak valid.'],
        general: ['Terjadi kesalahan data.']
      }
    });

    expect(wrapper.text()).toContain('Nama paket obat sudah digunakan.');
    expect(wrapper.text()).toContain('Harga paket tidak valid.');
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
