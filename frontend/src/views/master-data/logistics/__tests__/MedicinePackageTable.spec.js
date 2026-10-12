import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import MedicinePackageTable from '../components/MedicinePackageTable.vue';

describe('MedicinePackageTable.vue', () => {
  const defaultProps = {
    items: [
      {
        id: 1,
        name: 'Paket Flu & Batuk',
        price: 35000,
        items: [
          { id: 101, medicine: { id: 1, name: 'Paracetamol 500mg' } },
          { id: 102, medicine: { id: 2, name: 'Dextromethorphan' } },
          { id: 103, medicine: { id: 3, name: 'Vitamin C 500mg' } },
          { id: 104, medicine: { id: 4, name: 'CTM 4mg' } }
        ]
      },
      {
        id: 2,
        name: 'Paket Lambung Ringan',
        price: 25000,
        items: [
          { id: 105, medicine: { id: 5, name: 'Antasida Doen' } }
        ]
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

  it('renders exact 5 columns in order: No, Nama, Harga, Obat, Aksi', () => {
    const wrapper = mount(MedicinePackageTable, {
      props: defaultProps
    });

    const ths = wrapper.findAll('thead th');
    expect(ths).toHaveLength(5);
    expect(ths[0].text()).toBe('No');
    expect(ths[1].text()).toContain('Nama');
    expect(ths[2].text()).toContain('Harga');
    expect(ths[3].text()).toContain('Obat');
    expect(ths[4].text()).toBe('Aksi');
  });

  it('does not display extra columns like Status, Kode, Deskripsi, Tipe', () => {
    const wrapper = mount(MedicinePackageTable, {
      props: defaultProps
    });

    const headersText = wrapper.findAll('thead th').map((th) => th.text().trim());
    expect(headersText.some((h) => h.includes('Status'))).toBe(false);
    expect(headersText.some((h) => h.includes('Kode'))).toBe(false);
    expect(headersText.some((h) => h.includes('Deskripsi'))).toBe(false);
    expect(headersText.some((h) => h.includes('Tipe'))).toBe(false);
  });

  it('calculates row numbers correctly considering pagination', () => {
    const wrapper = mount(MedicinePackageTable, {
      props: defaultProps
    });

    const rows = wrapper.findAll('tbody tr');
    expect(rows).toHaveLength(2);

    // Page 2, per_page 10 -> row numbers 11, 12
    const firstColTexts = rows.map((r) => r.findAll('td')[0].text().trim());
    expect(firstColTexts).toEqual(['11', '12']);
  });

  it('formats price in proper Rupiah format', () => {
    const wrapper = mount(MedicinePackageTable, {
      props: defaultProps
    });

    const rows = wrapper.findAll('tbody tr');
    const price1 = rows[0].findAll('td')[2].text().trim();
    const price2 = rows[1].findAll('td')[2].text().trim();

    expect(price1).toContain('Rp');
    expect(price1).toContain('35.000');
    expect(price2).toContain('Rp');
    expect(price2).toContain('25.000');
  });

  it('displays medicine summary chip with "+X lainnya" when items > 3', () => {
    const wrapper = mount(MedicinePackageTable, {
      props: defaultProps
    });

    const rows = wrapper.findAll('tbody tr');
    const row1MedicinesCell = rows[0].findAll('td')[3];
    const chips = row1MedicinesCell.findAll('.medicine-chip');

    // 3 individual chips + 1 more-chip
    expect(chips).toHaveLength(4);
    expect(chips[0].text()).toBe('Paracetamol 500mg');
    expect(chips[1].text()).toBe('Dextromethorphan');
    expect(chips[2].text()).toBe('Vitamin C 500mg');
    expect(chips[3].text()).toContain('+1 lainnya');
  });

  it('emits sort event when clicking on sortable headers', async () => {
    const wrapper = mount(MedicinePackageTable, {
      props: defaultProps
    });

    const nameTh = wrapper.findAll('th.sortable')[0];
    await nameTh.trigger('click');

    expect(wrapper.emitted('sort')).toBeTruthy();
    expect(wrapper.emitted('sort')[0]).toEqual(['name']);

    const priceTh = wrapper.findAll('th.sortable')[1];
    await priceTh.trigger('click');

    expect(wrapper.emitted('sort')[1]).toEqual(['price']);
  });

  it('emits edit and delete when action buttons are clicked', async () => {
    const wrapper = mount(MedicinePackageTable, {
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
    const wrapper = mount(MedicinePackageTable, {
      props: {
        ...defaultProps,
        loading: true
      }
    });

    expect(wrapper.findComponent({ name: 'MasterDataSkeletonRow' }).exists()).toBe(true);
  });

  it('displays empty message when items array is empty', () => {
    const wrapper = mount(MedicinePackageTable, {
      props: {
        ...defaultProps,
        items: []
      }
    });

    expect(wrapper.text()).toContain('Tidak ada data paket obat.');
  });
});
