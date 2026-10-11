import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import LaboratoryCategoryTable from '../LaboratoryCategoryTable.vue';

describe('LaboratoryCategoryTable.vue', () => {
  const mockItems = [
    {
      id: 1,
      name: 'Hematologi Rutin',
      description: 'Pemeriksaan darah lengkap',
      type: 'lab klinik',
      is_active: true,
      loinc_code: '58410-2',
      loinc_url: 'https://loinc.org/58410-2/',
      snomed_code: '252275004',
      snomed_url: 'https://snomed.info/id/252275004'
    },
    {
      id: 2,
      name: 'Kategori Kosong',
      description: null,
      type: null,
      is_active: false,
      loinc_code: null,
      loinc_url: null,
      snomed_code: null,
      snomed_url: null
    }
  ];

  const mockPagination = {
    current_page: 1,
    per_page: 10,
    total: 2,
    last_page: 1
  };

  it('renders exact 9 columns in the specified order', () => {
    const wrapper = mount(LaboratoryCategoryTable, {
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
    expect(headers[3].text()).toContain('Tipe');
    expect(headers[4].text()).toContain('Status');
    expect(headers[5].text()).toContain('LOINC Code');
    expect(headers[6].text()).toContain('LOINC URL');
    expect(headers[7].text()).toContain('SNOMED Code');
    expect(headers[8].text()).toContain('Aksi');
  });

  it('renders row data correctly including valid LOINC URL link', () => {
    const wrapper = mount(LaboratoryCategoryTable, {
      props: {
        items: mockItems,
        pagination: mockPagination,
        loading: false
      }
    });

    const rows = wrapper.findAll('tbody tr');
    expect(rows.length).toBe(2);

    // Row 1
    const firstRowCells = rows[0].findAll('td');
    expect(firstRowCells[0].text()).toBe('1');
    expect(firstRowCells[1].text()).toContain('Hematologi Rutin');
    expect(firstRowCells[2].text()).toContain('Pemeriksaan darah lengkap');
    expect(firstRowCells[3].text()).toContain('lab klinik');
    expect(firstRowCells[4].text()).toContain('Aktif');
    expect(firstRowCells[5].text()).toContain('58410-2');

    // Link check for LOINC URL
    const loincLink = firstRowCells[6].find('a.table-link');
    expect(loincLink.exists()).toBe(true);
    expect(loincLink.attributes('href')).toBe('https://loinc.org/58410-2/');
    expect(loincLink.attributes('target')).toBe('_blank');
    expect(loincLink.attributes('rel')).toContain('noopener');

    expect(firstRowCells[7].text()).toContain('252275004');
  });

  it('renders "-" for null or empty values', () => {
    const wrapper = mount(LaboratoryCategoryTable, {
      props: {
        items: mockItems,
        pagination: mockPagination,
        loading: false
      }
    });

    const rows = wrapper.findAll('tbody tr');
    // Row 2 has null fields
    const secondRowCells = rows[1].findAll('td');
    expect(secondRowCells[2].text()).toBe('-');
    expect(secondRowCells[3].text()).toBe('-');
    expect(secondRowCells[5].text()).toBe('-');
    expect(secondRowCells[6].text()).toBe('-');
    expect(secondRowCells[7].text()).toBe('-');
  });

  it('emits sort event when clicking on sortable header', async () => {
    const wrapper = mount(LaboratoryCategoryTable, {
      props: {
        items: mockItems,
        pagination: mockPagination,
        loading: false
      }
    });

    const nameHeader = wrapper.find('th.sortable');
    await nameHeader.trigger('click');

    expect(wrapper.emitted('sort')).toBeTruthy();
    expect(wrapper.emitted('sort')[0]).toEqual(['name']);
  });

  it('renders only edit and delete action buttons and does not render toggle/nonaktifkan button', async () => {
    const wrapper = mount(LaboratoryCategoryTable, {
      props: {
        items: mockItems,
        pagination: mockPagination,
        loading: false
      }
    });

    // Verify toggle/nonaktifkan button is NOT present anywhere in table
    expect(wrapper.find('.btn-action.toggle-status').exists()).toBe(false);
    expect(wrapper.find('button[aria-label="Nonaktifkan"]').exists()).toBe(false);
    expect(wrapper.find('button[aria-label="Aktifkan"]').exists()).toBe(false);

    // Verify each row has exactly 2 action buttons (Edit and Delete)
    const firstRowActionButtons = wrapper.findAll('tbody tr:first-child .btn-action');
    expect(firstRowActionButtons.length).toBe(2);

    const editBtn = wrapper.find('.btn-action.edit');
    expect(editBtn.exists()).toBe(true);
    await editBtn.trigger('click');

    expect(wrapper.emitted('edit')).toBeTruthy();
    expect(wrapper.emitted('edit')[0][0]).toEqual(mockItems[0]);

    const deleteBtn = wrapper.find('.btn-action.delete');
    expect(deleteBtn.exists()).toBe(true);
    await deleteBtn.trigger('click');

    expect(wrapper.emitted('delete')).toBeTruthy();
    expect(wrapper.emitted('delete')[0][0]).toEqual(mockItems[0]);
  });
});
