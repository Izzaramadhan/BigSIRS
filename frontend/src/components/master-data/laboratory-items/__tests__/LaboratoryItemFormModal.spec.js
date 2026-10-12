import { describe, it, expect } from 'vitest';
import { mount, flushPromises } from '@vue/test-utils';
import LaboratoryItemFormModal from '../LaboratoryItemFormModal.vue';

describe('LaboratoryItemFormModal.vue', () => {
  const mountModal = (propsData = {}) => {
    return mount(LaboratoryItemFormModal, {
      props: {
        isOpen: true,
        item: null,
        isSubmitting: false,
        errors: {},
        ...propsData
      },
      global: {
        stubs: {
          teleport: true
        }
      }
    });
  };

  it('renders correctly with default title and no status field', () => {
    const wrapper = mountModal();

    expect(wrapper.text()).toContain('Tambah Item Lab');
    expect(wrapper.find('input#name').element.value).toBe('');
    expect(wrapper.find('textarea#reference_value').element.value).toBe('');
    expect(wrapper.find('input#unit').element.value).toBe('');
    // Verify status field is completely absent from DOM
    expect(wrapper.find('#is_active').exists()).toBe(false);
    expect(wrapper.text()).not.toContain('Status');
  });

  it('populates fields when item prop is provided without showing status field', () => {
    const item = {
      id: 5,
      name: 'KOLESTEROL TOTAL',
      reference_value: '<200',
      unit: 'mg/dL',
      is_active: false
    };

    const wrapper = mountModal({ item });

    expect(wrapper.text()).toContain('Edit Item Lab');
    expect(wrapper.find('input#name').element.value).toBe('KOLESTEROL TOTAL');
    expect(wrapper.find('textarea#reference_value').element.value).toBe('<200');
    expect(wrapper.find('input#unit').element.value).toBe('mg/dL');
    expect(wrapper.find('#is_active').exists()).toBe(false);
  });

  it('emits submit event with trimmed payload and preserves default status on create', async () => {
    const wrapper = mountModal();

    await wrapper.find('input#name').setValue('  HBSAG  ');
    await wrapper.find('textarea#reference_value').setValue('  Negatif  ');
    await wrapper.find('input#unit').setValue('   ');

    await wrapper.find('form').trigger('submit.prevent');

    expect(wrapper.emitted('submit')).toBeTruthy();
    const payload = wrapper.emitted('submit')[0][0];
    expect(payload.name).toBe('HBSAG');
    expect(payload.reference_value).toBe('Negatif');
    expect(payload.unit).toBeNull();
    expect(payload.is_active).toBe(true);
  });

  it('emits submit event preserving existing status when editing', async () => {
    const item = {
      id: 5,
      name: 'KOLESTEROL TOTAL',
      reference_value: '<200',
      unit: 'mg/dL',
      is_active: false // Existing status is false
    };

    const wrapper = mountModal({ item });

    await wrapper.find('input#name').setValue('KOLESTEROL TOTAL EDIT');
    await wrapper.find('form').trigger('submit.prevent');

    expect(wrapper.emitted('submit')).toBeTruthy();
    const payload = wrapper.emitted('submit')[0][0];
    expect(payload.name).toBe('KOLESTEROL TOTAL EDIT');
    expect(payload.is_active).toBe(false); // Preserves existing inactive status
  });

  it('renders field validation errors when passed in props', () => {
    const wrapper = mountModal({
      errors: {
        name: ['Nama item lab wajib diisi.'],
        reference_value: ['Nilai rujukan tidak valid.']
      }
    });

    expect(wrapper.text()).toContain('Nama item lab wajib diisi.');
    expect(wrapper.text()).toContain('Nilai rujukan tidak valid.');
    expect(wrapper.find('input#name').classes()).toContain('is-invalid');
    expect(wrapper.find('textarea#reference_value').classes()).toContain('is-invalid');
  });

  it('emits close event when Batal button is clicked', async () => {
    const wrapper = mountModal();

    const cancelBtn = wrapper.find('.btn-cancel');
    expect(cancelBtn.exists()).toBe(true);
    await cancelBtn.trigger('click');

    expect(wrapper.emitted('close')).toBeTruthy();
  });

  describe('Convert Pangkat feature', () => {
    it('converts unit power notation (x10^3/uL -> x10³/uL) and displays preview', async () => {
      const wrapper = mountModal();

      await wrapper.find('input#unit').setValue('x10^3/uL');

      const convertButtons = wrapper.findAll('.btn-convert-pangkat');
      // Unit convert button is the second convert button
      const convertUnitBtn = convertButtons[1];
      expect(convertUnitBtn.exists()).toBe(true);
      expect(convertUnitBtn.attributes('type')).toBe('button');

      await convertUnitBtn.trigger('click');
      await flushPromises();

      expect(wrapper.find('input#unit').element.value).toBe('x10³/uL');
      expect(wrapper.text()).toContain('Preview:');
      expect(wrapper.text()).toContain('x10³/uL');
    });

    it('converts reference value notation and displays preview', async () => {
      const wrapper = mountModal();

      await wrapper.find('textarea#reference_value').setValue('4.5 - 11.0 x 10^3 / uL');

      const convertButtons = wrapper.findAll('.btn-convert-pangkat');
      const convertRefBtn = convertButtons[0];

      await convertRefBtn.trigger('click');
      await flushPromises();

      expect(wrapper.find('textarea#reference_value').element.value).toBe('4.5 - 11.0 x 10³ / uL');
      expect(wrapper.text()).toContain('Preview:');
      expect(wrapper.text()).toContain('4.5 - 11.0 x 10³ / uL');
    });

    it('is idempotent and informs user when no caret notation is found', async () => {
      const wrapper = mountModal();

      const unitInput = wrapper.find('input#unit');
      await unitInput.setValue('100 mg');

      const convertUnitBtn = wrapper.findAll('.btn-convert-pangkat')[1];
      await convertUnitBtn.trigger('click');

      // Value stays 100 mg
      expect(unitInput.element.value).toBe('100 mg');
      expect(wrapper.text()).toContain('Tidak ada format pangkat yang perlu dikonversi.');

      // Click again
      await convertUnitBtn.trigger('click');
      expect(unitInput.element.value).toBe('100 mg');
    });

    it('safely handles empty field when Convert Pangkat is clicked without closing modal', async () => {
      const wrapper = mountModal();

      const convertUnitBtn = wrapper.findAll('.btn-convert-pangkat')[1];
      await convertUnitBtn.trigger('click');

      expect(wrapper.find('input#unit').element.value).toBe('');
      expect(wrapper.text()).toContain('Tidak ada format pangkat yang perlu dikonversi.');
      expect(wrapper.emitted('close')).toBeFalsy();
      expect(wrapper.emitted('submit')).toBeFalsy();
    });

    it('submits plain Unicode characters without HTML tags', async () => {
      const wrapper = mountModal();

      await wrapper.find('input#name').setValue('LEKOSIT');
      await wrapper.find('input#unit').setValue('10^3/uL');

      const convertUnitBtn = wrapper.findAll('.btn-convert-pangkat')[1];
      await convertUnitBtn.trigger('click');

      await wrapper.find('form').trigger('submit.prevent');

      expect(wrapper.emitted('submit')).toBeTruthy();
      const payload = wrapper.emitted('submit')[0][0];
      expect(payload.unit).toBe('10³/uL');
      expect(payload.unit).not.toContain('<sup>');
      expect(payload.unit).not.toContain('<span>');
    });

    it('preserves existing superscript value in edit mode without automatic re-conversion', async () => {
      const item = {
        id: 7,
        name: 'HITUNG LEKOSIT',
        reference_value: '5 - 10 x 10³ / uL',
        unit: '10³/uL',
        is_active: true
      };

      const wrapper = mountModal({ item });

      expect(wrapper.find('input#unit').element.value).toBe('10³/uL');
      expect(wrapper.find('textarea#reference_value').element.value).toBe('5 - 10 x 10³ / uL');

      // Submit without changes
      await wrapper.find('form').trigger('submit.prevent');

      expect(wrapper.emitted('submit')).toBeTruthy();
      const payload = wrapper.emitted('submit')[0][0];
      expect(payload.unit).toBe('10³/uL');
      expect(payload.reference_value).toBe('5 - 10 x 10³ / uL');
    });
  });
});

