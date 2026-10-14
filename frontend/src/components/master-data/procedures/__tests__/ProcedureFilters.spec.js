import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import ProcedureFilters from '../ProcedureFilters.vue';

describe('ProcedureFilters.vue', () => {
  const mockCategories = [
    { value: 1, label: 'Tindakan Medis Operatif' },
    { value: 2, label: 'Tindakan Non-Operatif' }
  ];

  const mockTariffTypes = [
    { value: 10, label: 'Tarif Umum' },
    { value: 20, label: 'Tarif BPJS' }
  ];

  const defaultProps = {
    filters: {
      search: '',
      category_id: '',
      tariff_type_id: ''
    },
    loading: false,
    categories: mockCategories,
    categoriesLoading: false,
    tariffTypes: mockTariffTypes,
    tariffTypesLoading: false
  };

  it('renders all 4 controls: search, category select, tariff type select, and refresh button', () => {
    const wrapper = mount(ProcedureFilters, {
      props: defaultProps
    });

    // 1. Search input
    expect(wrapper.find('input').exists()).toBe(true);

    // 2 & 3. Category & Tariff Type selects
    const selects = wrapper.findAll('select.filter-select');
    expect(selects).toHaveLength(2);

    const categorySelect = selects[0];
    const tariffSelect = selects[1];

    expect(categorySelect.attributes('aria-label')).toBe('Filter Kategori Tindakan');
    expect(tariffSelect.attributes('aria-label')).toBe('Filter Jenis Tarif');

    // Default "Semua" options
    expect(categorySelect.find('option[value=""]').text()).toBe('Semua Kategori');
    expect(tariffSelect.find('option[value=""]').text()).toBe('Semua Jenis Tarif');

    // 4. Refresh button
    const refreshBtn = wrapper.find('button.btn-icon');
    expect(refreshBtn.exists()).toBe(true);
    expect(refreshBtn.attributes('title')).toBe('Refresh Data');
  });

  it('populates options from props correctly without hardcoding', () => {
    const wrapper = mount(ProcedureFilters, {
      props: defaultProps
    });

    const selects = wrapper.findAll('select.filter-select');
    const categoryOptions = selects[0].findAll('option');
    expect(categoryOptions).toHaveLength(3); // "Semua Kategori" + 2 items
    expect(categoryOptions[1].text()).toBe('Tindakan Medis Operatif');
    expect(categoryOptions[1].element.value).toBe('1');
    expect(categoryOptions[2].text()).toBe('Tindakan Non-Operatif');
    expect(categoryOptions[2].element.value).toBe('2');

    const tariffOptions = selects[1].findAll('option');
    expect(tariffOptions).toHaveLength(3); // "Semua Jenis Tarif" + 2 items
    expect(tariffOptions[1].text()).toBe('Tarif Umum');
    expect(tariffOptions[1].element.value).toBe('10');
    expect(tariffOptions[2].text()).toBe('Tarif BPJS');
    expect(tariffOptions[2].element.value).toBe('20');
  });

  it('emits filter event when Category select changes', async () => {
    const wrapper = mount(ProcedureFilters, {
      props: defaultProps
    });

    const categorySelect = wrapper.findAll('select.filter-select')[0];
    await categorySelect.setValue('2');

    expect(wrapper.emitted('filter')).toBeTruthy();
    expect(wrapper.emitted('filter')[0]).toEqual([{ key: 'category_id', value: 2 }]);
  });

  it('emits empty value when "Semua Kategori" is selected', async () => {
    const wrapper = mount(ProcedureFilters, {
      props: {
        ...defaultProps,
        filters: { ...defaultProps.filters, category_id: 1 }
      }
    });

    const categorySelect = wrapper.findAll('select.filter-select')[0];
    await categorySelect.setValue('');

    expect(wrapper.emitted('filter')).toBeTruthy();
    expect(wrapper.emitted('filter')[0]).toEqual([{ key: 'category_id', value: '' }]);
  });

  it('emits filter event when Tariff Type select changes', async () => {
    const wrapper = mount(ProcedureFilters, {
      props: defaultProps
    });

    const tariffSelect = wrapper.findAll('select.filter-select')[1];
    await tariffSelect.setValue('20');

    expect(wrapper.emitted('filter')).toBeTruthy();
    expect(wrapper.emitted('filter')[0]).toEqual([{ key: 'tariff_type_id', value: 20 }]);
  });

  it('emits empty value when "Semua Jenis Tarif" is selected', async () => {
    const wrapper = mount(ProcedureFilters, {
      props: {
        ...defaultProps,
        filters: { ...defaultProps.filters, tariff_type_id: 10 }
      }
    });

    const tariffSelect = wrapper.findAll('select.filter-select')[1];
    await tariffSelect.setValue('');

    expect(wrapper.emitted('filter')).toBeTruthy();
    expect(wrapper.emitted('filter')[0]).toEqual([{ key: 'tariff_type_id', value: '' }]);
  });

  it('displays loading state and disables selects while options are loading', () => {
    const wrapper = mount(ProcedureFilters, {
      props: {
        ...defaultProps,
        categoriesLoading: true,
        tariffTypesLoading: true
      }
    });

    const selects = wrapper.findAll('select.filter-select');
    expect(selects[0].attributes('disabled')).toBeDefined();
    expect(selects[0].find('option[value=""]').text()).toBe('Memuat kategori...');

    expect(selects[1].attributes('disabled')).toBeDefined();
    expect(selects[1].find('option[value=""]').text()).toBe('Memuat jenis tarif...');
  });

  it('emits refresh event when refresh button is clicked', async () => {
    const wrapper = mount(ProcedureFilters, {
      props: defaultProps
    });

    const refreshBtn = wrapper.find('button.btn-icon');
    await refreshBtn.trigger('click');

    expect(wrapper.emitted('refresh')).toBeTruthy();
  });

  it('renders Download Excel button with correct tooltip and label in 5th position', () => {
    const wrapper = mount(ProcedureFilters, {
      props: defaultProps
    });

    const exportBtn = wrapper.find('button.btn-export');
    expect(exportBtn.exists()).toBe(true);
    expect(exportBtn.text()).toContain('Download Excel');
    expect(exportBtn.attributes('title')).toBe('Download data tindakan dalam format Excel');
    expect(exportBtn.attributes('aria-label')).toBe('Download data tindakan dalam format Excel');
  });

  it('emits export event when Download Excel button is clicked', async () => {
    const wrapper = mount(ProcedureFilters, {
      props: defaultProps
    });

    const exportBtn = wrapper.find('button.btn-export');
    await exportBtn.trigger('click');

    expect(wrapper.emitted('export')).toBeTruthy();
    expect(wrapper.emitted('export')).toHaveLength(1);
  });

  it('disables Download Excel button and displays "Mengunduh..." when exporting is true', () => {
    const wrapper = mount(ProcedureFilters, {
      props: {
        ...defaultProps,
        exporting: true
      }
    });

    const exportBtn = wrapper.find('button.btn-export');
    expect(exportBtn.attributes('disabled')).toBeDefined();
    expect(exportBtn.text()).toContain('Mengunduh...');
  });
});

