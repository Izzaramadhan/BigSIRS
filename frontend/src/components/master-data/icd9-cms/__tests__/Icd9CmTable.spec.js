import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import Icd9CmTable from '../Icd9CmTable.vue';

const mockItems = [
  {
    id: 1,
    code: '01.01',
    name: 'Cisternal puncture',
    inacbg_code: 'Q-5-10-I',
    inacbg_name: 'Prosedur Cranial Ringan',
    class_1_tariff: 1500000,
    class_2_tariff: 1200000,
    class_3_tariff: 900000
  },
  {
    id: 2,
    code: '01.02',
    name: 'Ventriculopuncture through previously implanted catheter',
    inacbg_code: null,
    inacbg_name: null,
    class_1_tariff: null,
    class_2_tariff: null,
    class_3_tariff: null
  }
];

const mockPagination = {
  current_page: 2,
  per_page: 10,
  total: 25,
  last_page: 3
};

const mockSort = {
  column: 'code',
  direction: 'asc'
};

describe('Icd9CmTable.vue', () => {
  it('renders exact 6 columns in old system order', () => {
    const wrapper = mount(Icd9CmTable, {
      props: {
        items: mockItems,
        pagination: mockPagination,
        sort: mockSort,
        loading: false
      }
    });

    const headers = wrapper.findAll('thead th');
    expect(headers).toHaveLength(6);
    expect(headers[0].text()).toContain('No');
    expect(headers[1].text()).toContain('Kode');
    expect(headers[2].text()).toContain('ICD');
    expect(headers[3].text()).toContain('INACBG');
    expect(headers[4].text()).toContain('Tarif Kelas');
    expect(headers[5].text()).toContain('Aksi');
  });

  it('calculates row number based on pagination', () => {
    const wrapper = mount(Icd9CmTable, {
      props: {
        items: mockItems,
        pagination: mockPagination,
        sort: mockSort,
        loading: false
      }
    });

    const rows = wrapper.findAll('tbody tr');
    // Page 2 with per_page 10: item 0 is (2-1)*10 + 0 + 1 = 11, item 1 is 12
    expect(rows[0].find('.col-no').text()).toBe('11');
    expect(rows[1].find('.col-no').text()).toBe('12');
  });

  it('displays ICD name without code repetition', () => {
    const wrapper = mount(Icd9CmTable, {
      props: {
        items: mockItems,
        pagination: mockPagination,
        sort: mockSort,
        loading: false
      }
    });

    const rows = wrapper.findAll('tbody tr');
    expect(rows[0].find('.col-icd').text()).toBe('Cisternal puncture');
    expect(rows[1].find('.col-icd').text()).toBe('Ventriculopuncture through previously implanted catheter');
  });

  it('displays INACBG code and name in single column or - when empty', () => {
    const wrapper = mount(Icd9CmTable, {
      props: {
        items: mockItems,
        pagination: mockPagination,
        sort: mockSort,
        loading: false
      }
    });

    const rows = wrapper.findAll('tbody tr');
    expect(rows[0].find('.col-inacbg').text()).toContain('Q-5-10-I');
    expect(rows[0].find('.col-inacbg').text()).toContain('Prosedur Cranial Ringan');

    expect(rows[1].find('.col-inacbg').text()).toBe('-');
  });

  it('formats Rupiah tariffs properly for Kelas 1, 2, 3 and displays - when unavailable', () => {
    const wrapper = mount(Icd9CmTable, {
      props: {
        items: mockItems,
        pagination: mockPagination,
        sort: mockSort,
        loading: false
      }
    });

    const rows = wrapper.findAll('tbody tr');
    // Row 1 has tariffs
    expect(rows[0].text()).toContain('Kelas 1:');
    expect(rows[0].text()).toContain('Rp1.500.000');
    expect(rows[0].text()).toContain('Kelas 2:');
    expect(rows[0].text()).toContain('Rp1.200.000');
    expect(rows[0].text()).toContain('Kelas 3:');
    expect(rows[0].text()).toContain('Rp900.000');

    // Row 2 has null tariffs -> should display '-'
    expect(rows[1].text()).toContain('Kelas 1: -');
    expect(rows[1].text()).toContain('Kelas 2: -');
    expect(rows[1].text()).toContain('Kelas 3: -');
  });

  it('emits edit and delete events when action buttons are clicked', async () => {
    const wrapper = mount(Icd9CmTable, {
      props: {
        items: mockItems,
        pagination: mockPagination,
        sort: mockSort,
        loading: false
      }
    });

    const editBtn = wrapper.find('.actions-cell button.edit');
    await editBtn.trigger('click');
    expect(wrapper.emitted('edit')).toBeTruthy();
    expect(wrapper.emitted('edit')[0][0]).toEqual(mockItems[0]);

    const deleteBtn = wrapper.find('.actions-cell button.delete');
    await deleteBtn.trigger('click');
    expect(wrapper.emitted('delete')).toBeTruthy();
    expect(wrapper.emitted('delete')[0][0]).toEqual(mockItems[0]);
  });

  it('emits sort event when sortable column header is clicked', async () => {
    const wrapper = mount(Icd9CmTable, {
      props: {
        items: mockItems,
        pagination: mockPagination,
        sort: mockSort,
        loading: false
      }
    });

    const nameTh = wrapper.find('th.col-icd');
    await nameTh.trigger('click');
    expect(wrapper.emitted('sort')).toBeTruthy();
    expect(wrapper.emitted('sort')[0][0]).toBe('name');
  });
});
