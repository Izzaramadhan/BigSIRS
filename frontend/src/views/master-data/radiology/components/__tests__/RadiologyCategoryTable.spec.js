import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import RadiologyCategoryTable from '../RadiologyCategoryTable.vue';

describe('RadiologyCategoryTable.vue', () => {
  const mockItems = [
    {
      id: 1,
      name: 'Radiografi Konvensional',
      description: 'Pemeriksaan foto rontgen umum',
      is_active: true,
      loinc_code: '24606-6',
      loinc_url: 'https://loinc.org/24606-6/',
      snomed_code: '363680008',
      snomed_url: 'https://snomed.info/id/363680008'
    },
    {
      id: 2,
      name: 'USG Abdomen',
      description: null,
      is_active: false,
      loinc_code: null,
      loinc_url: null,
      snomed_code: null,
      snomed_url: null
    }
  ];

  const mockPagination = {
    current_page: 2,
    per_page: 10,
    total: 12,
    last_page: 2
  };

  it('renders exact 9 columns in the specified order', () => {
    const wrapper = mount(RadiologyCategoryTable, {
      props: {
        items: mockItems,
        pagination: mockPagination,
        loading: false
      }
    });

    const headers = wrapper.findAll('thead th');
    expect(headers.length).toBe(9);
    expect(headers[0].text()).toContain('No');
    expect(headers[1].text()).toContain('Kategori');
    expect(headers[2].text()).toContain('Deskripsi');
    expect(headers[3].text()).toContain('Status');
    expect(headers[4].text()).toContain('Loinc Code');
    expect(headers[5].text()).toContain('Loinc Url');
    expect(headers[6].text()).toContain('Snomed Code');
    expect(headers[7].text()).toContain('Snomed Url');
    expect(headers[8].text()).toContain('Aksi');
  });

  it('calculates row numbers correctly based on pagination', () => {
    const wrapper = mount(RadiologyCategoryTable, {
      props: {
        items: mockItems,
        pagination: mockPagination,
        loading: false
      }
    });

    const rows = wrapper.findAll('tbody tr');
    expect(rows.length).toBe(2);

    // Page 2, item 0 => 11; item 1 => 12
    const firstRowCells = rows[0].findAll('td');
    const secondRowCells = rows[1].findAll('td');
    expect(firstRowCells[0].text()).toBe('11');
    expect(secondRowCells[0].text()).toBe('12');
  });

  it('renders row data correctly including valid LOINC and SNOMED URL links', () => {
    const wrapper = mount(RadiologyCategoryTable, {
      props: {
        items: mockItems,
        pagination: mockPagination,
        loading: false
      }
    });

    const rows = wrapper.findAll('tbody tr');
    const cells = rows[0].findAll('td');

    expect(cells[1].text()).toContain('Radiografi Konvensional');
    expect(cells[2].text()).toContain('Pemeriksaan foto rontgen umum');
    expect(cells[3].text()).toContain('Aktif');
    expect(cells[4].text()).toContain('24606-6');

    // LOINC URL
    const loincLink = cells[5].find('a.table-link');
    expect(loincLink.exists()).toBe(true);
    expect(loincLink.attributes('href')).toBe('https://loinc.org/24606-6/');
    expect(loincLink.attributes('target')).toBe('_blank');
    expect(loincLink.attributes('rel')).toContain('noopener');

    // SNOMED Code & URL
    expect(cells[6].text()).toContain('363680008');
    const snomedLink = cells[7].find('a.table-link');
    expect(snomedLink.exists()).toBe(true);
    expect(snomedLink.attributes('href')).toBe('https://snomed.info/id/363680008');
    expect(snomedLink.attributes('target')).toBe('_blank');
    expect(snomedLink.attributes('rel')).toContain('noopener');
  });

  it('renders "-" for null or empty values', () => {
    const wrapper = mount(RadiologyCategoryTable, {
      props: {
        items: mockItems,
        pagination: mockPagination,
        loading: false
      }
    });

    const rows = wrapper.findAll('tbody tr');
    const emptyRowCells = rows[1].findAll('td');

    expect(emptyRowCells[2].text()).toBe('-');
    expect(emptyRowCells[3].text()).toContain('Nonaktif');
    expect(emptyRowCells[4].text()).toBe('-');
    expect(emptyRowCells[5].text()).toBe('-');
    expect(emptyRowCells[6].text()).toBe('-');
    expect(emptyRowCells[7].text()).toBe('-');
  });

  it('emits edit, delete, and sort events correctly', async () => {
    const wrapper = mount(RadiologyCategoryTable, {
      props: {
        items: mockItems,
        pagination: mockPagination,
        loading: false
      }
    });

    // Click sort header
    const sortHeader = wrapper.find('th.sortable');
    await sortHeader.trigger('click');
    expect(wrapper.emitted('sort')).toBeTruthy();
    expect(wrapper.emitted('sort')[0]).toEqual(['name']);

    // Click edit button
    const editBtn = wrapper.find('.btn-action.edit');
    expect(editBtn.exists()).toBe(true);
    await editBtn.trigger('click');
    expect(wrapper.emitted('edit')).toBeTruthy();
    expect(wrapper.emitted('edit')[0][0]).toEqual(mockItems[0]);

    // Click delete button
    const deleteBtn = wrapper.find('.btn-action.delete');
    expect(deleteBtn.exists()).toBe(true);
    await deleteBtn.trigger('click');
    expect(wrapper.emitted('delete')).toBeTruthy();
    expect(wrapper.emitted('delete')[0][0]).toEqual(mockItems[0]);
  });
});
