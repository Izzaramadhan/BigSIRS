import { flushPromises, mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import SearchableSelect from './SearchableSelect.vue';

describe('SearchableSelect.vue', () => {
  const sampleOptions = [
    { id: 1, name: 'Paracetamol' },
    { id: 2, name: 'Amoxicillin' }
  ];

  it('renders placeholder and options correctly', async () => {
    const wrapper = mount(SearchableSelect, {
      props: {
        modelValue: '',
        options: sampleOptions,
        placeholder: 'Pilih generik...'
      }
    });

    expect(wrapper.text()).toContain('Pilih generik...');

    // Open dropdown
    await wrapper.find('.selected-box').trigger('click');
    expect(wrapper.findAll('.option-item').length).toBe(2);
  });

  it('filters options by search keyword', async () => {
    const wrapper = mount(SearchableSelect, {
      props: {
        modelValue: '',
        options: sampleOptions
      }
    });

    await wrapper.find('.selected-box').trigger('click');
    const input = wrapper.find('.search-box input');
    await input.setValue('Amox');

    const options = wrapper.findAll('.option-item');
    expect(options.length).toBe(1);
    expect(options[0].text()).toContain('Amoxicillin');
  });

  it('renders creatable option when creatable is true and search has no exact match', async () => {
    const wrapper = mount(SearchableSelect, {
      props: {
        modelValue: '',
        options: sampleOptions,
        creatable: true
      }
    });

    await wrapper.find('.selected-box').trigger('click');
    const input = wrapper.find('.search-box input');
    await input.setValue('Cefadroxil');

    const createOption = wrapper.find('.create-option-item');
    expect(createOption.exists()).toBe(true);
    expect(createOption.text()).toContain('Tambahkan “Cefadroxil” sebagai generik baru');

    await createOption.trigger('click');
    expect(wrapper.emitted('create')).toBeTruthy();
    expect(wrapper.emitted('create')[0][0]).toBe('Cefadroxil');
  });

  it('does not render creatable option when exact match exists (case-insensitive)', async () => {
    const wrapper = mount(SearchableSelect, {
      props: {
        modelValue: '',
        options: sampleOptions,
        creatable: true
      }
    });

    await wrapper.find('.selected-box').trigger('click');
    const input = wrapper.find('.search-box input');
    await input.setValue('  paracetamol  ');

    const createOption = wrapper.find('.create-option-item');
    expect(createOption.exists()).toBe(false);
  });

  it('does not render creatable option when keyword is empty', async () => {
    const wrapper = mount(SearchableSelect, {
      props: {
        modelValue: '',
        options: sampleOptions,
        creatable: true
      }
    });

    await wrapper.find('.selected-box').trigger('click');
    const createOption = wrapper.find('.create-option-item');
    expect(createOption.exists()).toBe(false);
  });
});
