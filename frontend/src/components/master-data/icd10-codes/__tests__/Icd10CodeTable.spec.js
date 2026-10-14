import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import Icd10CodeTable from '../Icd10CodeTable.vue';

const mockItems = [
  {
    id: 1,
    code: 'A00.0',
    name: 'Cholera due to Vibrio cholerae 01, biovar cholerae',
    english_name: 'Cholera classical',
    description: 'Kolera klasik akibat biovar kolera',
    inacbg_code: 'I-4-10-I',
    inacbg_name: 'Diare Akut dan Penyakit Menular Saluran Cerna',
    class_1_tariff: 1250000,
    class_2_tariff: 950000,
    class_3_tariff: 650000,
    is_medical_history: true
  },
  {
    id: 2,
    code: 'A01.0',
    name: 'Typhoid fever',
    english_name: null,
    description: null,
    inacbg_code: null,
    inacbg_name: null,
    class_1_tariff: null,
    class_2_tariff: null,
    class_3_tariff: null,
    is_medical_history: false
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

describe('Icd10CodeTable.vue', () => {
  it('renders exact 7 columns in old system order', () => {
    const wrapper = mount(Icd10CodeTable, {
      props: {
        items: mockItems,
        pagination: mockPagination,
        sort: mockSort,
        loading: false
      }
    });

    const headers = wrapper.findAll('thead th');
    expect(headers).toHaveLength(7);
    expect(headers[0].text()).toContain('No');
    expect(headers[1].text()).toContain('Kode');
    expect(headers[2].text()).toContain('Nama Diagnosa');
    expect(headers[3].text()).toContain('Deskripsi');
    expect(headers[4].text()).toContain('INACBG');
    expect(headers[5].text()).toContain('Tarif Kelas');
    expect(headers[6].text()).toContain('Aksi');
  });

  it('calculates row number based on pagination', () => {
    const wrapper = mount(Icd10CodeTable, {
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

  it('formats Rupiah tariffs properly for Kelas 1, 2, 3 and displays - when unavailable', () => {
    const wrapper = mount(Icd10CodeTable, {
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
    expect(rows[0].text()).toContain('Rp1.250.000');
    expect(rows[0].text()).toContain('Kelas 2:');
    expect(rows[0].text()).toContain('Rp950.000');
    expect(rows[0].text()).toContain('Kelas 3:');
    expect(rows[0].text()).toContain('Rp650.000');

    // Row 2 has null tariffs -> should display '-'
    expect(rows[1].text()).toContain('Kelas 1: -');
    expect(rows[1].text()).toContain('Kelas 2: -');
    expect(rows[1].text()).toContain('Kelas 3: -');
  });

  it('falls back to - when description or INACBG is not available', () => {
    const wrapper = mount(Icd10CodeTable, {
      props: {
        items: mockItems,
        pagination: mockPagination,
        sort: mockSort,
        loading: false
      }
    });

    const rows = wrapper.findAll('tbody tr');
    // Row 1 has description and INACBG
    expect(rows[0].find('.col-desc').text()).toBe('Kolera klasik akibat biovar kolera');
    expect(rows[0].find('.col-inacbg').text()).toContain('I-4-10-I');

    // Row 2 has no description or INACBG
    expect(rows[1].find('.col-desc').text()).toBe('-');
    expect(rows[1].find('.col-inacbg').text()).toBe('-');
  });

  it('emits edit and delete events when action buttons are clicked', async () => {
    const wrapper = mount(Icd10CodeTable, {
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
    const wrapper = mount(Icd10CodeTable, {
      props: {
        items: mockItems,
        pagination: mockPagination,
        sort: mockSort,
        loading: false
      }
    });

    const nameTh = wrapper.find('th.col-name');
    await nameTh.trigger('click');
    expect(wrapper.emitted('sort')).toBeTruthy();
    expect(wrapper.emitted('sort')[0][0]).toBe('name');
  });
});
