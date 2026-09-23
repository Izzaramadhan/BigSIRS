import { describe, it, expect, beforeEach, afterEach } from 'vitest';
import { mount } from '@vue/test-utils';
import TariffComponentFormModal from '../TariffComponentFormModal.vue';

describe('TariffComponentFormModal.vue', () => {
  beforeEach(() => {
    const el = document.createElement('div');
    el.id = 'modal-target';
    document.body.appendChild(el);
  });

  afterEach(() => {
    document.body.innerHTML = '';
  });

  it('renders correctly when open for adding', async () => {
    const wrapper = mount(TariffComponentFormModal, {
      props: {
        isOpen: true,
        editData: null,
      },
      global: {
        stubs: {
          teleport: true
        }
      }
    });

    expect(wrapper.text()).toContain('Tambah Komponen');
    expect(wrapper.find('#name').element.value).toBe('');
    expect(wrapper.find('#description').element.value).toBe('');
  });

  it('renders correctly when open for editing', async () => {
    const editData = {
      id: 1,
      name: 'Test Component',
      description: 'Test Description',
      is_active: false
    };

    const wrapper = mount(TariffComponentFormModal, {
      props: {
        isOpen: true,
        editData,
      },
      global: {
        stubs: {
          teleport: true
        }
      }
    });

    expect(wrapper.text()).toContain('Edit Komponen');
    expect(wrapper.find('#name').element.value).toBe('Test Component');
    expect(wrapper.find('#description').element.value).toBe('Test Description');
  });

  it('emits save event with correct data when form is submitted', async () => {
    const wrapper = mount(TariffComponentFormModal, {
      props: {
        isOpen: true,
        editData: null,
      },
      global: {
        stubs: {
          teleport: true
        }
      }
    });

    await wrapper.find('#name').setValue(' New Component ');
    await wrapper.find('#description').setValue(' Description ');
    
    // trigger submit
    await wrapper.find('form').trigger('submit.prevent');

    expect(wrapper.emitted('save')).toBeTruthy();
    expect(wrapper.emitted('save')[0][0]).toEqual({
      name: 'New Component',
      description: 'Description',
      is_active: true
    });
  });

  it('emits close event when batal is clicked', async () => {
    const wrapper = mount(TariffComponentFormModal, {
      props: {
        isOpen: true,
        editData: null,
      },
      global: {
        stubs: {
          teleport: true
        }
      }
    });

    // Click Batal button
    await wrapper.findAll('button').filter(b => b.text() === 'Batal').at(0).trigger('click');

    expect(wrapper.emitted('close')).toBeTruthy();
  });
});
