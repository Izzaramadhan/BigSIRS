import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount, flushPromises } from '@vue/test-utils';
import RadiologyItemModal from '../RadiologyItemModal.vue';
import axios from '@/utils/axios';

vi.mock('@/utils/axios', () => ({
  default: {
    get: vi.fn(),
  },
}));

describe('RadiologyItemModal.vue', () => {
  const mockGroups = [
    { id: 10, name: 'Foto Rontgen Tanpa Bahan Kontras', is_active: true },
    { id: 20, name: 'Foto Rontgen Dengan Bahan Kontras', is_active: true }
  ];

  beforeEach(() => {
    vi.clearAllMocks();
    axios.get.mockResolvedValue({
      data: {
        data: mockGroups
      }
    });
  });

  const mountModal = (propsData = {}) => {
    return mount(RadiologyItemModal, {
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

  it('renders correctly with default title for new item, inputs, placeholders, and default Status Aktif', async () => {
    const wrapper = mountModal();
    await flushPromises();

    expect(wrapper.text()).toContain('Tambah Item Radiologi');
    
    // Nama Item
    const nameInput = wrapper.find('input#name');
    expect(nameInput.element.value).toBe('');
    expect(nameInput.attributes('placeholder')).toBe('Masukkan nama item radiologi');

    // Kelompok Item Radiologi
    const groupSelect = wrapper.find('select#radiology_item_group_id');
    expect(groupSelect.element.value).toBe('');
    expect(wrapper.text()).toContain('Foto Rontgen Tanpa Bahan Kontras');

    // Status Tampil
    const activeRadio = wrapper.find('input#status_active');
    expect(activeRadio.element.checked).toBe(true);

    const inactiveRadio = wrapper.find('input#status_inactive');
    expect(inactiveRadio.element.checked).toBe(false);

    expect(wrapper.find('button[type="submit"]').text()).toContain('Simpan');
  });

  it('populates fields correctly when item prop is provided (Status Aktif)', async () => {
    const item = {
      id: 1,
      name: 'Rontgen Thorax PA',
      radiology_item_group_id: 10,
      is_active: true,
      group: { id: 10, name: 'Foto Rontgen Tanpa Bahan Kontras' }
    };

    const wrapper = mountModal({ item });
    await flushPromises();

    expect(wrapper.text()).toContain('Edit Item Radiologi');
    expect(wrapper.find('input#name').element.value).toBe('Rontgen Thorax PA');
    expect(wrapper.find('select#radiology_item_group_id').element.value).toBe('10');
    expect(wrapper.find('input#status_active').element.checked).toBe(true);
    expect(wrapper.find('input#status_inactive').element.checked).toBe(false);
    expect(wrapper.find('button[type="submit"]').text()).toContain('Simpan Perubahan');
  });

  it('populates fields correctly when item prop is provided with Status Nonaktif', async () => {
    const item = {
      id: 2,
      name: 'Item Nonaktif',
      radiology_item_group_id: 20,
      is_active: false,
      group: { id: 20, name: 'Foto Rontgen Dengan Bahan Kontras' }
    };

    const wrapper = mountModal({ item });
    await flushPromises();

    expect(wrapper.text()).toContain('Edit Item Radiologi');
    expect(wrapper.find('input#name').element.value).toBe('Item Nonaktif');
    expect(wrapper.find('select#radiology_item_group_id').element.value).toBe('20');
    expect(wrapper.find('input#status_active').element.checked).toBe(false);
    expect(wrapper.find('input#status_inactive').element.checked).toBe(true);
  });

  it('preserves saved group even if inactive or not in default active lookup list', async () => {
    const item = {
      id: 3,
      name: 'Special Item',
      radiology_item_group_id: 99,
      is_active: true,
      group: { id: 99, name: 'Kelompok Khusus Nonaktif' }
    };

    const wrapper = mountModal({ item });
    await flushPromises();

    expect(wrapper.find('select#radiology_item_group_id').element.value).toBe('99');
    expect(wrapper.text()).toContain('Kelompok Khusus Nonaktif');
  });

  it('emits submit event with trimmed payload and status Aktif', async () => {
    const wrapper = mountModal();
    await flushPromises();

    await wrapper.find('input#name').setValue('  Rontgen Clavicula  ');
    await wrapper.find('select#radiology_item_group_id').setValue('10');

    await wrapper.find('form').trigger('submit.prevent');

    expect(wrapper.emitted('submit')).toBeTruthy();
    const payload = wrapper.emitted('submit')[0][0];
    expect(payload.name).toBe('Rontgen Clavicula');
    expect(payload.radiology_item_group_id).toBe(10);
    expect(payload.is_active).toBe(true);
  });

  it('emits submit event with status Nonaktif when Nonaktif radio is selected', async () => {
    const wrapper = mountModal();
    await flushPromises();

    await wrapper.find('input#name').setValue('Rontgen Wrist');
    await wrapper.find('select#radiology_item_group_id').setValue('20');
    await wrapper.find('input#status_inactive').setValue(true);

    await wrapper.find('form').trigger('submit.prevent');

    expect(wrapper.emitted('submit')).toBeTruthy();
    const payload = wrapper.emitted('submit')[0][0];
    expect(payload.name).toBe('Rontgen Wrist');
    expect(payload.radiology_item_group_id).toBe(20);
    expect(payload.is_active).toBe(false);
  });

  it('validates required name and group on client side before emit', async () => {
    const wrapper = mountModal();
    await flushPromises();

    await wrapper.find('input#name').setValue('   ');
    await wrapper.find('select#radiology_item_group_id').setValue('');
    await wrapper.find('form').trigger('submit.prevent');

    expect(wrapper.emitted('submit')).toBeFalsy();
    expect(wrapper.text()).toContain('Nama item radiologi wajib diisi.');
    expect(wrapper.text()).toContain('Kelompok item radiologi wajib dipilih.');
  });

  it('renders field validation errors when passed in props (422)', () => {
    const wrapper = mountModal({
      errors: {
        name: ['Nama item radiologi sudah digunakan.'],
        radiology_item_group_id: ['Kelompok item tidak valid.'],
        general: 'Terjadi kesalahan sistem.'
      }
    });

    expect(wrapper.text()).toContain('Nama item radiologi sudah digunakan.');
    expect(wrapper.text()).toContain('Kelompok item tidak valid.');
    expect(wrapper.text()).toContain('Terjadi kesalahan sistem.');
    expect(wrapper.find('input#name').classes()).toContain('is-invalid');
    expect(wrapper.find('select#radiology_item_group_id').classes()).toContain('is-invalid');
  });

  it('disables submit button when isSubmitting is true', () => {
    const wrapper = mountModal({
      isSubmitting: true
    });

    const submitBtn = wrapper.find('button[type="submit"]');
    expect(submitBtn.attributes('disabled')).toBeDefined();
    expect(submitBtn.text()).toContain('Menyimpan...');
  });

  it('emits close event when Batal button is clicked', async () => {
    const wrapper = mountModal();
    await flushPromises();

    const cancelBtn = wrapper.find('.btn-cancel');
    expect(cancelBtn.exists()).toBe(true);
    await cancelBtn.trigger('click');

    expect(wrapper.emitted('close')).toBeTruthy();
  });
});
