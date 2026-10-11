import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import SupplierTable from '../components/SupplierTable.vue';

describe('SupplierTable.vue', () => {
  const defaultProps = {
    items: [
      {
        id: 1,
        name: 'PT Kimia Farma',
        phone: '081234567890',
        address: 'Jl. Veteran No. 10, Jakarta'
      },
      {
        id: 2,
        name: 'PT Kalbe Farma',
        phone: null,
        address: ''
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

  it('renders exact 5 columns in order: No, Nama, Telp, Alamat, Aksi', () => {
    const wrapper = mount(SupplierTable, {
      props: defaultProps
    });

    const ths = wrapper.findAll('thead th');
    expect(ths).toHaveLength(5);
    expect(ths[0].text()).toBe('No');
    expect(ths[1].text()).toContain('Nama');
    expect(ths[2].text()).toBe('Telp');
    expect(ths[3].text()).toBe('Alamat');
    expect(ths[4].text()).toBe('Aksi');
  });

  it('does not display extra columns like Kode, Status, Email, etc.', () => {
    const wrapper = mount(SupplierTable, {
      props: defaultProps
    });

    const headersText = wrapper.findAll('thead th').map((th) => th.text().trim());
    expect(headersText.some((h) => h.includes('Status'))).toBe(false);
    expect(headersText.some((h) => h.includes('Kode'))).toBe(false);
    expect(headersText.some((h) => h.includes('Email'))).toBe(false);
    expect(headersText.some((h) => h.includes('Kontak'))).toBe(false);
  });

  it('calculates row numbers correctly considering pagination', () => {
    const wrapper = mount(SupplierTable, {
      props: defaultProps
    });

    const rows = wrapper.findAll('tbody tr');
    expect(rows).toHaveLength(2);

    // Page 2, per_page 10 -> row numbers 11, 12
    const firstColTexts = rows.map((r) => r.findAll('td')[0].text().trim());
    expect(firstColTexts).toEqual(['11', '12']);
  });

  it('displays supplier data and dash "-" when phone or address is empty', () => {
    const wrapper = mount(SupplierTable, {
      props: defaultProps
    });

    const rows = wrapper.findAll('tbody tr');

    // Row 1: all data present
    const row1Tds = rows[0].findAll('td');
    expect(row1Tds[1].text()).toBe('PT Kimia Farma');
    expect(row1Tds[2].text()).toBe('081234567890');
    expect(row1Tds[3].text()).toBe('Jl. Veteran No. 10, Jakarta');

    // Row 2: phone null, address empty
    const row2Tds = rows[1].findAll('td');
    expect(row2Tds[1].text()).toBe('PT Kalbe Farma');
    expect(row2Tds[2].text()).toBe('-');
    expect(row2Tds[3].text()).toBe('-');
  });

  it('preserves leading zero on phone numbers', () => {
    const wrapper = mount(SupplierTable, {
      props: defaultProps
    });

    const rows = wrapper.findAll('tbody tr');
    const phoneCell = rows[0].findAll('td')[2].text();
    expect(phoneCell.startsWith('0')).toBe(true);
    expect(phoneCell).toBe('081234567890');
  });

  it('emits sort event when clicking on sortable header', async () => {
    const wrapper = mount(SupplierTable, {
      props: defaultProps
    });

    const sortableTh = wrapper.find('th.sortable');
    await sortableTh.trigger('click');

    expect(wrapper.emitted('sort')).toBeTruthy();
    expect(wrapper.emitted('sort')[0]).toEqual(['name']);
  });

  it('emits edit and delete when action buttons are clicked', async () => {
    const wrapper = mount(SupplierTable, {
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
    const wrapper = mount(SupplierTable, {
      props: {
        ...defaultProps,
        loading: true
      }
    });

    expect(wrapper.findComponent({ name: 'MasterDataSkeletonRow' }).exists()).toBe(true);
  });

  it('displays empty message when items array is empty', () => {
    const wrapper = mount(SupplierTable, {
      props: {
        ...defaultProps,
        items: []
      }
    });

    expect(wrapper.text()).toContain('Tidak ada data supplier.');
  });
});
