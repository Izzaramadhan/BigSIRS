import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import LaboratoryGroupFormModal from '../LaboratoryGroupFormModal.vue';

describe('LaboratoryGroupFormModal.vue', () => {
  const categories = [
    { id: 1, name: 'Hematologi Rutin' },
    { id: 2, name: 'Kimia Darah' }
  ];

  const availableItems = [
    { id: 6, name: 'HEMOGLOBIN', unit: 'g/dL' },
    { id: 7, name: 'HITUNG LEKOSIT', unit: '10³/uL' },
    { id: 8, name: 'GLUKOSA PUASA', unit: 'mg/dL' }
  ];

  const defaultProps = {
    isOpen: true,
    group: null,
    categories,
    availableItems,
    isLoadingCategories: false,
    isLoadingItems: false,
    isSubmitting: false,
    errors: {}
  };

  const mountModal = (propsData = {}) => {
    return mount(LaboratoryGroupFormModal, {
      props: { ...defaultProps, ...propsData },
      global: {
        stubs: {
          Teleport: true
        }
      }
    });
  };

  it('renders all 10 fields in exact specified order', () => {
    const wrapper = mountModal();

    expect(wrapper.text()).toContain('Tambah Grup Lab');
    expect(wrapper.text()).toContain('Simpan');

    // 1. Nama Grup Lab
    const nameInput = wrapper.find('#name');
    expect(nameInput.exists()).toBe(true);

    // 2. Kategori Lab
    const catSelect = wrapper.find('#laboratory_category_id');
    expect(catSelect.exists()).toBe(true);

    // 3. Item Lab
    const itemSelect = wrapper.find('#laboratory_item_id');
    expect(itemSelect.exists()).toBe(true);

    // 4. Harga
    const priceInput = wrapper.find('#price');
    expect(priceInput.exists()).toBe(true);

    // 5. Harga Jaspel
    const jaspelInput = wrapper.find('#harga_jaspel');
    expect(jaspelInput.exists()).toBe(true);

    // 6. Status
    const statusActive = wrapper.find('#status_active');
    const statusInactive = wrapper.find('#status_inactive');
    expect(statusActive.exists()).toBe(true);
    expect(statusInactive.exists()).toBe(true);

    // 7. LOINC Code
    const loincCode = wrapper.find('#loinc_code');
    expect(loincCode.exists()).toBe(true);

    // 8. LOINC URL
    const loincUrl = wrapper.find('#loinc_url');
    expect(loincUrl.exists()).toBe(true);

    // 9. SNOMED Code
    const snomedCode = wrapper.find('#snomed_code');
    expect(snomedCode.exists()).toBe(true);

    // 10. SNOMED URL
    const snomedUrl = wrapper.find('#snomed_url');
    expect(snomedUrl.exists()).toBe(true);
  });

  it('disables Item Lab select until Kategori Lab is selected', async () => {
    const wrapper = mountModal();

    expect(wrapper.find('#laboratory_item_id').element.disabled).toBe(true);

    await wrapper.find('#laboratory_category_id').setValue('1');
    await wrapper.vm.$nextTick();

    expect(wrapper.find('#laboratory_item_id').element.disabled).toBe(false);
  });

  it('renders correctly in edit mode and populates all fields', () => {
    const existingGroup = {
      id: 1,
      name: 'Paket Hematologi',
      laboratory_category_id: 1,
      laboratory_item_id: 6,
      items: [{ id: 6, name: 'HEMOGLOBIN' }],
      price: 50000,
      harga_jaspel: 15000,
      is_active: false,
      loinc_code: '58410-2',
      loinc_url: 'https://loinc.org/58410-2/',
      snomed_code: '439958008',
      snomed_url: 'https://browser.ihtsdotools.org/'
    };

    const wrapper = mountModal({ group: existingGroup });

    expect(wrapper.text()).toContain('Edit Grup Lab');
    expect(wrapper.text()).toContain('Simpan Perubahan');

    expect(wrapper.find('#name').element.value).toBe('Paket Hematologi');
    expect(wrapper.find('#laboratory_category_id').element.value).toBe('1');
    expect(wrapper.find('#laboratory_item_id').element.value).toBe('6');
    expect(wrapper.find('#price').element.value).toBe('50000');
    expect(wrapper.find('#harga_jaspel').element.value).toBe('15000');
    expect(wrapper.find('#status_inactive').element.checked).toBe(true);
    expect(wrapper.find('#loinc_code').element.value).toBe('58410-2');
    expect(wrapper.find('#loinc_url').element.value).toBe('https://loinc.org/58410-2/');
    expect(wrapper.find('#snomed_code').element.value).toBe('439958008');
    expect(wrapper.find('#snomed_url').element.value).toBe('https://browser.ihtsdotools.org/');
  });

  it('emits submit with formatted payload on valid form submission', async () => {
    const wrapper = mountModal();

    await wrapper.find('#name').setValue('  Paket Darah Lengkap  ');
    await wrapper.find('#laboratory_category_id').setValue('1');
    await wrapper.find('#laboratory_item_id').setValue('7');
    await wrapper.find('#price').setValue('85000');
    await wrapper.find('#harga_jaspel').setValue('25000');
    await wrapper.find('#status_active').setChecked();
    await wrapper.find('#loinc_code').setValue('58410-2');
    await wrapper.find('#loinc_url').setValue('https://loinc.org/58410-2/');

    const form = wrapper.find('form');
    await form.trigger('submit');

    expect(wrapper.emitted('submit')).toBeTruthy();
    const emittedPayload = wrapper.emitted('submit')[0][0];

    expect(emittedPayload.name).toBe('Paket Darah Lengkap');
    expect(emittedPayload.laboratory_category_id).toBe(1);
    expect(emittedPayload.laboratory_item_id).toBe(7);
    expect(emittedPayload.laboratory_item_ids).toEqual([7]);
    expect(emittedPayload.price).toBe(85000);
    expect(emittedPayload.harga_jaspel).toBe(25000);
    expect(emittedPayload.is_active).toBe(true);
    expect(emittedPayload.loinc_code).toBe('58410-2');
    expect(emittedPayload.loinc_url).toBe('https://loinc.org/58410-2/');
    expect(emittedPayload.snomed_code).toBeNull();
    expect(emittedPayload.snomed_url).toBeNull();
  });

  it('validates invalid URL and prevents submission', async () => {
    const wrapper = mountModal();

    await wrapper.find('#name').setValue('Paket Baru');
    await wrapper.find('#laboratory_category_id').setValue('1');
    await wrapper.find('#laboratory_item_id').setValue('6');
    await wrapper.find('#loinc_url').setValue('invalid-url-format');

    const form = wrapper.find('form');
    await form.trigger('submit');

    expect(wrapper.emitted('submit')).toBeFalsy();
    expect(wrapper.text()).toContain('Format URL LOINC tidak valid');
  });

  it('displays backend validation errors when passed via props', () => {
    const errors = {
      name: ['Nama grup lab wajib diisi.'],
      laboratory_category_id: ['Kategori lab wajib dipilih.'],
      laboratory_item_ids: ['Item lab wajib dipilih.']
    };

    const wrapper = mountModal({ errors });

    expect(wrapper.text()).toContain('Nama grup lab wajib diisi.');
    expect(wrapper.text()).toContain('Kategori lab wajib dipilih.');
    expect(wrapper.text()).toContain('Item lab wajib dipilih.');
  });

  it('emits close when cancel button is clicked', async () => {
    const wrapper = mountModal();

    const cancelBtn = wrapper.find('.btn-cancel');
    expect(cancelBtn.exists()).toBe(true);
    await cancelBtn.trigger('click');
    expect(wrapper.emitted('close')).toBeTruthy();
  });
});
