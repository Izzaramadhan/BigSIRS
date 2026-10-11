import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import WarehouseTable from '../components/WarehouseTable.vue';

describe('WarehouseTable.vue', () => {
  const defaultProps = {
    items: [
      {
        id: 1,
        code: 'GDG-01',
        name: 'Gudang Utama Farmasi',
        description: 'Gudang penyimpanan induk obat dan alkes'
      },
      {
        id: 2,
        code: '002',
        name: 'Gudang Rawat Inap',
        description: ''
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

  it('renders exact 5 columns in order: No, Kode, Nama, Deskripsi, Aksi with Kode before Nama', () => {
    const wrapper = mount(WarehouseTable, {
      props: defaultProps
    });

    const ths = wrapper.findAll('thead th');
    expect(ths).toHaveLength(5);
    expect(ths[0].text()).toBe('No');
    expect(ths[1].text()).toContain('Kode');
    expect(ths[2].text()).toContain('Nama');
    expect(ths[3].text()).toBe('Deskripsi');
    expect(ths[4].text()).toBe('Aksi');
  });

  it('does not display extra columns like Status, Tipe, Lokasi, etc.', () => {
    const wrapper = mount(WarehouseTable, {
      props: defaultProps
    });

    const headersText = wrapper.findAll('thead th').map((th) => th.text().trim());
    expect(headersText.some((h) => h.includes('Status'))).toBe(false);
    expect(headersText.some((h) => h.includes('Tipe'))).toBe(false);
    expect(headersText.some((h) => h.includes('Lokasi'))).toBe(false);
  });

  it('calculates row numbers correctly considering pagination', () => {
    const wrapper = mount(WarehouseTable, {
      props: defaultProps
    });

    const rows = wrapper.findAll('tbody tr');
    expect(rows).toHaveLength(2);

    // Page 2, per_page 10 -> row numbers 11, 12
    const firstColTexts = rows.map((r) => r.findAll('td')[0].text().trim());
    expect(firstColTexts).toEqual(['11', '12']);
  });

  it('displays warehouse data and dash "-" when description or code is empty', () => {
    const wrapper = mount(WarehouseTable, {
      props: defaultProps
    });

    const rows = wrapper.findAll('tbody tr');

    // Row 1: all data present (Kode before Nama)
    const row1Tds = rows[0].findAll('td');
    expect(row1Tds[1].text()).toBe('GDG-01');
    expect(row1Tds[2].text()).toBe('Gudang Utama Farmasi');
    expect(row1Tds[3].text()).toBe('Gudang penyimpanan induk obat dan alkes');

    // Row 2: description empty
    const row2Tds = rows[1].findAll('td');
    expect(row2Tds[1].text()).toBe('002');
    expect(row2Tds[2].text()).toBe('Gudang Rawat Inap');
    expect(row2Tds[3].text()).toBe('-');
  });

  it('preserves leading zero on code strings', () => {
    const wrapper = mount(WarehouseTable, {
      props: defaultProps
    });

    const rows = wrapper.findAll('tbody tr');
    const codeCell = rows[1].findAll('td')[1].text();
    expect(codeCell.startsWith('0')).toBe(true);
    expect(codeCell).toBe('002');
  });

  it('emits sort event when clicking on sortable headers', async () => {
    const wrapper = mount(WarehouseTable, {
      props: defaultProps
    });

    const codeTh = wrapper.findAll('th.sortable')[0];
    await codeTh.trigger('click');

    expect(wrapper.emitted('sort')).toBeTruthy();
    expect(wrapper.emitted('sort')[0]).toEqual(['code']);
  });

  it('emits edit and delete when action buttons are clicked', async () => {
    const wrapper = mount(WarehouseTable, {
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
    const wrapper = mount(WarehouseTable, {
      props: {
        ...defaultProps,
        loading: true
      }
    });

    expect(wrapper.findComponent({ name: 'MasterDataSkeletonRow' }).exists()).toBe(true);
  });

  it('displays empty message when items array is empty', () => {
    const wrapper = mount(WarehouseTable, {
      props: {
        ...defaultProps,
        items: []
      }
    });

    expect(wrapper.text()).toContain('Tidak ada data gudang.');
  });
});
