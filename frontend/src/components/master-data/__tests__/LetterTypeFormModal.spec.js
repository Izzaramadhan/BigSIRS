import { mount } from '@vue/test-utils';
import { describe, it, expect } from 'vitest';
import LetterTypeFormModal from '../LetterTypeFormModal.vue';

describe('LetterTypeFormModal.vue', () => {
  it('renders correctly for add new mode', async () => {
    const wrapper = mount(LetterTypeFormModal, {
      props: {
        isOpen: false,
        letterTypeData: null
      }
    });

    await wrapper.setProps({ isOpen: true });

    expect(wrapper.find('.modal-title').text()).toBe('Tambah Surat');
    
    // Check if fields exist and are empty
    const nameInput = wrapper.find('input#name');
    expect(nameInput.exists()).toBe(true);
    expect(nameInput.element.value).toBe('');

    const descInput = wrapper.find('textarea#description');
    expect(descInput.exists()).toBe(true);
    expect(descInput.element.value).toBe('');
    
    // Legacy resource field should not be visible when adding
    const readonlyInputs = wrapper.findAll('input[disabled]');
    // Note: status switch is also an input, but not disabled by default
    const hasLegacyResource = Array.from(readonlyInputs).some(el => el.classes().includes('bg-gray-100'));
    expect(hasLegacyResource).toBe(false);
  });

  it('renders correctly for edit mode', async () => {
    const letterTypeData = {
      id: 1,
      name: 'Surat Edit',
      description: 'Test Edit',
      legacy_resource: 'test.php',
      is_active: true
    };

    const wrapper = mount(LetterTypeFormModal, {
      props: {
        isOpen: false,
        letterTypeData
      }
    });

    await wrapper.setProps({ isOpen: true });

    expect(wrapper.find('.modal-title').text()).toBe('Edit Surat');
    
    const nameInput = wrapper.find('input#name');
    expect(nameInput.element.value).toBe('Surat Edit');

    const descInput = wrapper.find('textarea#description');
    expect(descInput.element.value).toBe('Test Edit');
    
    // Legacy resource should be visible in edit mode if it exists
    const legacyInput = wrapper.find('.bg-gray-100');
    expect(legacyInput.exists()).toBe(true);
    expect(legacyInput.element.value).toBe('test.php');
  });

  it('emits close event when cancel is clicked', async () => {
    const wrapper = mount(LetterTypeFormModal, {
      props: { isOpen: true }
    });

    await wrapper.find('.btn-secondary').trigger('click');
    expect(wrapper.emitted()).toHaveProperty('close');
  });

  it('emits submit event with form data on submit', async () => {
    const wrapper = mount(LetterTypeFormModal, {
      props: { isOpen: true }
    });

    await wrapper.find('input#name').setValue('Surat Submit');
    await wrapper.find('form').trigger('submit.prevent');

    expect(wrapper.emitted()).toHaveProperty('submit');
    expect(wrapper.emitted('submit')[0][0]).toEqual({
      name: 'Surat Submit',
      description: '',
      legacy_resource: null,
      is_active: true
    });
  });
});
