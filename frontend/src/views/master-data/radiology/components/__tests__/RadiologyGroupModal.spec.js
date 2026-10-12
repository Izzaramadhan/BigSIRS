import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount, flushPromises } from '@vue/test-utils';
import RadiologyGroupModal from '../RadiologyGroupModal.vue';
import axios from '@/utils/axios';

vi.mock('@/utils/axios', () => ({
  default: {
    get: vi.fn((url) => {
      if (url.includes('radiology-categories')) {
        return Promise.resolve({ data: [{ id: 1, name: 'Radiografi Konvensional' }] });
      }
      if (url.includes('radiology-types')) {
        return Promise.resolve({ data: [{ id: 2, name: 'Foto Polos' }] });
      }
      if (url.includes('activity-types')) {
        return Promise.resolve({ data: [{ id: 3, name: 'Kegiatan Radiologi' }] });
      }
      if (url.includes('radiology-item-groups')) {
        return Promise.resolve({ data: [{ id: 4, name: 'Thorax' }] });
      }
      return Promise.resolve({ data: [] });
    })
  }
}));

describe('RadiologyGroupModal.vue', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  const mountModal = (propsData = {}) => {
    return mount(RadiologyGroupModal, {
      props: {
        isOpen: true,
        group: null,
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

  it('renders correctly with default title for new group and contains all 10 fields in order', async () => {
    const wrapper = mountModal();
    await flushPromises();

    expect(wrapper.text()).toContain('Tambah Grup Radiologi');

    // 1. Nama Grup Radiologi
    expect(wrapper.find('input#name').exists()).toBe(true);
    // 2. Kategori Radiologi
    expect(wrapper.find('select#radiology_category_id').exists()).toBe(true);
    // 3. Tipe Radiologi
    expect(wrapper.find('select#radiology_type_id').exists()).toBe(true);
    // 4. Jenis Kegiatan
    expect(wrapper.find('select#activity_type_id').exists()).toBe(true);
    // 5. Kelompok Pemeriksaan
    expect(wrapper.text()).toContain('Kelompok Pemeriksaan');
    // 6. Harga
    expect(wrapper.find('input#price').exists()).toBe(true);
    // 7. Harga Interpretasi
    expect(wrapper.find('input#interpretation_price').exists()).toBe(true);
    // 8. Status Tampil
    expect(wrapper.find('input#status_active').exists()).toBe(true);
    expect(wrapper.find('input#status_inactive').exists()).toBe(true);
    // 9. Loinc Code
    expect(wrapper.find('input#loinc_code').exists()).toBe(true);
    // 10. Loinc Url
    expect(wrapper.find('input#loinc_url').exists()).toBe(true);

    // Default status is Aktif
    expect(wrapper.find('input#status_active').element.checked).toBe(true);
  });

  it('populates fields when group prop is provided for editing', async () => {
    const group = {
      id: 1,
      name: 'Rontgen Thorax Standar',
      radiology_category_id: 1,
      radiology_type_id: 2,
      activity_type_id: 3,
      radiology_item_group_ids: [4],
      price: 150000,
      interpretation_price: 50000,
      is_active: false,
      loinc_code: '24606-6',
      loinc_url: 'https://loinc.org/24606-6/'
    };

    const wrapper = mountModal({ group });
    await flushPromises();

    expect(wrapper.text()).toContain('Edit Grup Radiologi');
    expect(wrapper.find('input#name').element.value).toBe('Rontgen Thorax Standar');
    expect(wrapper.find('select#radiology_category_id').element.value).toBe('1');
    expect(wrapper.find('select#radiology_type_id').element.value).toBe('2');
    expect(wrapper.find('select#activity_type_id').element.value).toBe('3');
    expect(Number(wrapper.find('input#price').element.value)).toBe(150000);
    expect(Number(wrapper.find('input#interpretation_price').element.value)).toBe(50000);
    expect(wrapper.find('input#status_inactive').element.checked).toBe(true);
    expect(wrapper.find('input#loinc_code').element.value).toBe('24606-6');
    expect(wrapper.find('input#loinc_url').element.value).toBe('https://loinc.org/24606-6/');
  });

  it('validates required fields before submitting', async () => {
    const wrapper = mountModal();
    await flushPromises();

    await wrapper.find('form').trigger('submit');
    await flushPromises();

    expect(wrapper.emitted('submit')).toBeFalsy();
    expect(wrapper.text()).toContain('Nama grup radiologi wajib diisi.');
  });

  it('emits submit event with proper payload when valid data is entered', async () => {
    const wrapper = mountModal();
    await flushPromises();

    await wrapper.find('input#name').setValue('Rontgen Baru');
    await wrapper.find('select#radiology_category_id').setValue('1');
    await wrapper.find('select#radiology_type_id').setValue('2');
    await wrapper.find('select#activity_type_id').setValue('3');
    await wrapper.find('input#price').setValue(200000);
    await wrapper.find('input#interpretation_price').setValue(75000);
    await wrapper.find('input#loinc_code').setValue('1234-5');
    await wrapper.find('input#loinc_url').setValue('https://loinc.org/1234-5');

    await wrapper.find('form').trigger('submit');
    await flushPromises();

    expect(wrapper.emitted('submit')).toBeTruthy();
    const payload = wrapper.emitted('submit')[0][0];
    expect(payload.name).toBe('Rontgen Baru');
    expect(payload.radiology_category_id).toBe(1);
    expect(payload.radiology_type_id).toBe(2);
    expect(payload.activity_type_id).toBe(3);
    expect(payload.price).toBe(200000);
    expect(payload.interpretation_price).toBe(75000);
    expect(payload.is_active).toBe(true);
    expect(payload.loinc_code).toBe('1234-5');
    expect(payload.loinc_url).toBe('https://loinc.org/1234-5');
  });
});
