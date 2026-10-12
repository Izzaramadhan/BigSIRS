import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import MedicineUnitTable from '../components/MedicineUnitTable.vue';

describe('MedicineUnitTable.vue', () => {
  const defaultProps = {
    items: [
      { id: 1, name: 'Tablet', description: 'Bentuk sediaan tablet' },
      { id: 2, name: 'Kapsul', description: null },
      { id: 3, name: 'Botol', description: '' }
    ],
    pagination: {
      current_page: 2,
      per_page: 10,
      total: 25,
      last_page: 3
    },
    sort: { column: 'name', direction: 'asc' },
    loading: false
  };

  it('renders exact 4 columns in order: No, Nama, Deskripsi, Aksi', () => {
    const wrapper = mount(MedicineUnitTable, {
      props: defaultProps
    });

    const ths = wrapper.findAll('thead th');
    expect(ths).toHaveLength(4);
    expect(ths[0].text()).toBe('No');
    expect(ths[1].text()).toContain('Nama');
    expect(ths[2].text()).toBe('Deskripsi');
    expect(ths[3].text()).toBe('Aksi');
  });

  it('does not display extra columns like Status, Code, or others', () => {
    const wrapper = mount(MedicineUnitTable, {
      props: defaultProps
    });

    const headersText = wrapper.findAll('thead th').map((th) => th.text().trim());
    expect(headersText.some((h) => h.includes('Status'))).toBe(false);
    expect(headersText.some((h) => h.includes('Kode'))).toBe(false);
  });

  it('calculates row numbers correctly considering pagination', () => {
    const wrapper = mount(MedicineUnitTable, {
      props: defaultProps
    });

    const rows = wrapper.findAll('tbody tr');
    expect(rows).toHaveLength(3);

    // Page 2, per_page 10 -> row numbers 11, 12, 13
    const firstColTexts = rows.map((r) => r.findAll('td')[0].text().trim());
    expect(firstColTexts).toEqual(['11', '12', '13']);
  });

  it('displays unit name and displays dash "-" if description is null or empty', () => {
    const wrapper = mount(MedicineUnitTable, {
      props: defaultProps
    });

    const rows = wrapper.findAll('tbody tr');
    // Row 1: Tablet, Bentuk sediaan tablet
    expect(rows[0].findAll('td')[1].text()).toBe('Tablet');
    expect(rows[0].findAll('td')[2].text()).toBe('Bentuk sediaan tablet');

    // Row 2: Kapsul, null -> '-'
    expect(rows[1].findAll('td')[1].text()).toBe('Kapsul');
    expect(rows[1].findAll('td')[2].text()).toBe('-');

    // Row 3: Botol, empty -> '-'
    expect(rows[2].findAll('td')[1].text()).toBe('Botol');
    expect(rows[2].findAll('td')[2].text()).toBe('-');
  });

  it('emits sort event when clicking on sortable header', async () => {
    const wrapper = mount(MedicineUnitTable, {
      props: defaultProps
    });

    const sortableTh = wrapper.find('th.sortable');
    await sortableTh.trigger('click');

    expect(wrapper.emitted('sort')).toBeTruthy();
    expect(wrapper.emitted('sort')[0]).toEqual(['name']);
  });

  it('emits edit and delete when action buttons are clicked', async () => {
    const wrapper = mount(MedicineUnitTable, {
      props: defaultProps
    });

    const firstRowActions = wrapper.findAll('tbody tr')[0].findAll('button');
    expect(firstRowActions.length).toBeGreaterThanOrEqual(2);

    await firstRowActions[0].trigger('click');
    expect(wrapper.emitted('edit')).toBeTruthy();
    expect(wrapper.emitted('edit')[0][0]).toEqual(defaultProps.items[0]);

    await firstRowActions[1].trigger('click');
    expect(wrapper.emitted('delete')).toBeTruthy();
    expect(wrapper.emitted('delete')[0][0]).toEqual(defaultProps.items[0]);
  });

  it('displays skeleton rows when loading is true', () => {
    const wrapper = mount(MedicineUnitTable, {
      props: {
        ...defaultProps,
        loading: true
      }
    });

    expect(wrapper.findComponent({ name: 'MasterDataSkeletonRow' }).exists()).toBe(true);
  });

  it('displays empty message when items array is empty', () => {
    const wrapper = mount(MedicineUnitTable, {
      props: {
        ...defaultProps,
        items: []
      }
    });

    expect(wrapper.text()).toContain('Tidak ada data satuan obat.');
  });
});
