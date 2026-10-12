import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import MedicationSignaTable from '../components/MedicationSignaTable.vue';

describe('MedicationSignaTable.vue', () => {
  const defaultProps = {
    items: [
      {
        id: 1,
        name: '3 x 1 sehari'
      },
      {
        id: 2,
        name: '1 x 1 sehari sesudah makan'
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

  it('renders exact 3 columns in order: No, Nama, Aksi', () => {
    const wrapper = mount(MedicationSignaTable, {
      props: defaultProps
    });

    const ths = wrapper.findAll('thead th');
    expect(ths).toHaveLength(3);
    expect(ths[0].text()).toBe('No');
    expect(ths[1].text()).toContain('Nama');
    expect(ths[2].text()).toBe('Aksi');
  });

  it('does not display extra columns like Status, Kode, Deskripsi, Tipe, Objek', () => {
    const wrapper = mount(MedicationSignaTable, {
      props: defaultProps
    });

    const headersText = wrapper.findAll('thead th').map((th) => th.text().trim());
    expect(headersText.some((h) => h.includes('Status'))).toBe(false);
    expect(headersText.some((h) => h.includes('Kode'))).toBe(false);
    expect(headersText.some((h) => h.includes('Deskripsi'))).toBe(false);
    expect(headersText.some((h) => h.includes('Tipe'))).toBe(false);
    expect(headersText.some((h) => h.includes('Objek'))).toBe(false);
  });

  it('calculates row numbers correctly considering pagination', () => {
    const wrapper = mount(MedicationSignaTable, {
      props: defaultProps
    });

    const rows = wrapper.findAll('tbody tr');
    expect(rows).toHaveLength(2);

    // Page 2, per_page 10 -> row numbers 11, 12
    const firstColTexts = rows.map((r) => r.findAll('td')[0].text().trim());
    expect(firstColTexts).toEqual(['11', '12']);
  });

  it('displays signa data and dash "-" when name is empty', () => {
    const wrapper = mount(MedicationSignaTable, {
      props: {
        ...defaultProps,
        items: [
          { id: 1, name: '2 x 1 sendok teh' },
          { id: 2, name: '' }
        ]
      }
    });

    const rows = wrapper.findAll('tbody tr');
    const row1Tds = rows[0].findAll('td');
    expect(row1Tds[1].text()).toBe('2 x 1 sendok teh');

    const row2Tds = rows[1].findAll('td');
    expect(row2Tds[1].text()).toBe('-');
  });

  it('emits sort event when clicking on sortable headers', async () => {
    const wrapper = mount(MedicationSignaTable, {
      props: defaultProps
    });

    const nameTh = wrapper.findAll('th.sortable')[0];
    await nameTh.trigger('click');

    expect(wrapper.emitted('sort')).toBeTruthy();
    expect(wrapper.emitted('sort')[0]).toEqual(['name']);
  });

  it('emits edit and delete when action buttons are clicked', async () => {
    const wrapper = mount(MedicationSignaTable, {
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
    const wrapper = mount(MedicationSignaTable, {
      props: {
        ...defaultProps,
        loading: true
      }
    });

    expect(wrapper.findComponent({ name: 'MasterDataSkeletonRow' }).exists()).toBe(true);
  });

  it('displays empty message when items array is empty', () => {
    const wrapper = mount(MedicationSignaTable, {
      props: {
        ...defaultProps,
        items: []
      }
    });

    expect(wrapper.text()).toContain('Tidak ada data signa obat.');
  });
});
