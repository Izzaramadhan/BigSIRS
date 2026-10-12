import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import MedicineTable from '../components/MedicineTable.vue';

describe('MedicineTable.vue', () => {
  const defaultProps = {
    items: [
      {
        id: 1,
        code: 'OBT001',
        name: 'Paracetamol 500mg',
        kfa_code: '930001',
        description: 'Obat penurun panas',
        unit: { id: 1, name: 'Tablet' },
        category: { id: 1, name: 'Analgesik' },
        classification: { id: 1, name: 'Obat Bebas' },
        route: { id: 1, name: 'Oral' },
        generic: { id: 1, name: 'Paracetamol' }
      },
      {
        id: 2,
        code: null,
        name: 'Amoxicillin 500mg',
        kfa_code: null,
        description: null,
        unit: null,
        category: null,
        classification: null,
        route: null,
        generic: null
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

  it('renders exact 11 columns in order: No, Kode, Nama, Satuan, Kategori, Golongan, Rute, Generik, Kode KFA, Deskripsi, Aksi', () => {
    const wrapper = mount(MedicineTable, {
      props: defaultProps
    });

    const ths = wrapper.findAll('thead th');
    expect(ths).toHaveLength(11);
    expect(ths[0].text()).toBe('No');
    expect(ths[1].text()).toContain('Kode');
    expect(ths[2].text()).toContain('Nama');
    expect(ths[3].text()).toBe('Satuan');
    expect(ths[4].text()).toBe('Kategori');
    expect(ths[5].text()).toBe('Golongan');
    expect(ths[6].text()).toBe('Rute');
    expect(ths[7].text()).toBe('Generik');
    expect(ths[8].text()).toBe('Kode KFA');
    expect(ths[9].text()).toBe('Deskripsi');
    expect(ths[10].text()).toBe('Aksi');
  });

  it('calculates row numbers correctly considering pagination', () => {
    const wrapper = mount(MedicineTable, {
      props: defaultProps
    });

    const rows = wrapper.findAll('tbody tr');
    expect(rows).toHaveLength(2);

    // Page 2, per_page 10 -> row numbers 11, 12
    const firstColTexts = rows.map((r) => r.findAll('td')[0].text().trim());
    expect(firstColTexts).toEqual(['11', '12']);
  });

  it('displays relation names and fallback dash "-" when relation or fields are null', () => {
    const wrapper = mount(MedicineTable, {
      props: defaultProps
    });

    const rows = wrapper.findAll('tbody tr');

    // Row 1: All relations present
    const row1Tds = rows[0].findAll('td');
    expect(row1Tds[1].text()).toBe('OBT001');
    expect(row1Tds[2].text()).toBe('Paracetamol 500mg');
    expect(row1Tds[3].text()).toBe('Tablet');
    expect(row1Tds[4].text()).toBe('Analgesik');
    expect(row1Tds[5].text()).toBe('Obat Bebas');
    expect(row1Tds[6].text()).toBe('Oral');
    expect(row1Tds[7].text()).toBe('Paracetamol');
    expect(row1Tds[8].text()).toBe('930001');
    expect(row1Tds[9].text()).toBe('Obat penurun panas');

    // Row 2: Nulls fall back to '-'
    const row2Tds = rows[1].findAll('td');
    expect(row2Tds[1].text()).toBe('-');
    expect(row2Tds[2].text()).toBe('Amoxicillin 500mg');
    expect(row2Tds[3].text()).toBe('-');
    expect(row2Tds[4].text()).toBe('-');
    expect(row2Tds[5].text()).toBe('-');
    expect(row2Tds[6].text()).toBe('-');
    expect(row2Tds[7].text()).toBe('-');
    expect(row2Tds[8].text()).toBe('-');
    expect(row2Tds[9].text()).toBe('-');
  });

  it('emits sort event when clicking on sortable header', async () => {
    const wrapper = mount(MedicineTable, {
      props: defaultProps
    });

    const nameTh = wrapper.findAll('th.sortable')[1];
    await nameTh.trigger('click');

    expect(wrapper.emitted('sort')).toBeTruthy();
    expect(wrapper.emitted('sort')[0]).toEqual(['name']);
  });

  it('emits edit and delete when action buttons are clicked', async () => {
    const wrapper = mount(MedicineTable, {
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
    const wrapper = mount(MedicineTable, {
      props: {
        ...defaultProps,
        loading: true
      }
    });

    expect(wrapper.findComponent({ name: 'MasterDataSkeletonRow' }).exists()).toBe(true);
  });

  it('displays empty message when items array is empty', () => {
    const wrapper = mount(MedicineTable, {
      props: {
        ...defaultProps,
        items: []
      }
    });

    expect(wrapper.text()).toContain('Tidak ada data obat.');
  });
});
