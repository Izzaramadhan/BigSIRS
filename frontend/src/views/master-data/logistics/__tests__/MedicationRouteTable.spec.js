import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import MedicationRouteTable from '../components/MedicationRouteTable.vue';

describe('MedicationRouteTable.vue', () => {
  const defaultProps = {
    items: [
      {
        id: 1,
        name: 'ORAL',
        object_code: 'MDCTx000000002'
      },
      {
        id: 2,
        name: 'Jalur buntu',
        object_code: null
      }
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

  it('renders exact 4 columns in order: No, Nama Jalur Masuk, Objek, Aksi', () => {
    const wrapper = mount(MedicationRouteTable, {
      props: defaultProps
    });

    const ths = wrapper.findAll('thead th');
    expect(ths).toHaveLength(4);
    expect(ths[0].text()).toBe('No');
    expect(ths[1].text()).toContain('Nama Jalur Masuk');
    expect(ths[2].text()).toContain('Objek');
    expect(ths[3].text()).toBe('Aksi');
  });

  it('does not display extra columns like Status, Kode, Deskripsi, Tipe', () => {
    const wrapper = mount(MedicationRouteTable, {
      props: defaultProps
    });

    const headersText = wrapper.findAll('thead th').map((th) => th.text().trim());
    expect(headersText.some((h) => h.includes('Status'))).toBe(false);
    expect(headersText.some((h) => h.includes('Kode') && !h.includes('Objek'))).toBe(false);
    expect(headersText.some((h) => h.includes('Deskripsi'))).toBe(false);
    expect(headersText.some((h) => h.includes('Tipe'))).toBe(false);
  });

  it('calculates row numbers correctly considering pagination', () => {
    const wrapper = mount(MedicationRouteTable, {
      props: defaultProps
    });

    const rows = wrapper.findAll('tbody tr');
    expect(rows).toHaveLength(2);

    // Page 2, per_page 10 -> row numbers 11, 12
    const firstColTexts = rows.map((r) => r.findAll('td')[0].text().trim());
    expect(firstColTexts).toEqual(['11', '12']);
  });

  it('displays route data and dash "-" when object_code is null or dash', () => {
    const wrapper = mount(MedicationRouteTable, {
      props: defaultProps
    });

    const rows = wrapper.findAll('tbody tr');

    // Row 1: all data present
    const row1Tds = rows[0].findAll('td');
    expect(row1Tds[1].text()).toBe('ORAL');
    expect(row1Tds[2].text()).toBe('MDCTx000000002');

    // Row 2: object_code null -> '-'
    const row2Tds = rows[1].findAll('td');
    expect(row2Tds[1].text()).toBe('Jalur buntu');
    expect(row2Tds[2].text()).toBe('-');
  });

  it('emits sort event when clicking on sortable headers', async () => {
    const wrapper = mount(MedicationRouteTable, {
      props: defaultProps
    });

    const nameTh = wrapper.findAll('th.sortable')[0];
    await nameTh.trigger('click');

    expect(wrapper.emitted('sort')).toBeTruthy();
    expect(wrapper.emitted('sort')[0]).toEqual(['name']);

    const objTh = wrapper.findAll('th.sortable')[1];
    await objTh.trigger('click');

    expect(wrapper.emitted('sort')[1]).toEqual(['object_code']);
  });

  it('emits edit and delete when action buttons are clicked', async () => {
    const wrapper = mount(MedicationRouteTable, {
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
    const wrapper = mount(MedicationRouteTable, {
      props: {
        ...defaultProps,
        loading: true
      }
    });

    expect(wrapper.findComponent({ name: 'MasterDataSkeletonRow' }).exists()).toBe(true);
  });

  it('displays empty message when items array is empty', () => {
    const wrapper = mount(MedicationRouteTable, {
      props: {
        ...defaultProps,
        items: []
      }
    });

    expect(wrapper.text()).toContain('Tidak ada data jalur masuk obat.');
  });
});
