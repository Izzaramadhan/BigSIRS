import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import RadiologyTypeTable from '../RadiologyTypeTable.vue';

describe('RadiologyTypeTable.vue', () => {
  const mockItems = [
    {
      id: 1,
      name: 'Radiografi Konvensional',
      description: 'Pemeriksaan foto rontgen umum',
      is_active: true
    },
    {
      id: 2,
      name: 'Ultrasonografi',
      description: null,
      is_active: true
    }
  ];

  const mockPagination = {
    current_page: 2,
    per_page: 10,
    total: 12,
    last_page: 2
  };

  it('renders exact 4 columns in the specified order', () => {
    const wrapper = mount(RadiologyTypeTable, {
      props: {
        items: mockItems,
        pagination: mockPagination,
        loading: false
      }
    });

    const headers = wrapper.findAll('thead th');
    expect(headers.length).toBe(4);
    expect(headers[0].text()).toContain('No');
    expect(headers[1].text()).toContain('Tipe');
    expect(headers[2].text()).toContain('Deskripsi');
    expect(headers[3].text()).toContain('Aksi');
  });

  it('calculates row numbers correctly based on pagination', () => {
    const wrapper = mount(RadiologyTypeTable, {
      props: {
        items: mockItems,
        pagination: mockPagination,
        loading: false
      }
    });

    const rows = wrapper.findAll('tbody tr');
    expect(rows.length).toBe(2);

    const firstRowCells = rows[0].findAll('td');
    const secondRowCells = rows[1].findAll('td');
    expect(firstRowCells[0].text()).toBe('11');
    expect(secondRowCells[0].text()).toBe('12');
  });

  it('renders row data correctly', () => {
    const wrapper = mount(RadiologyTypeTable, {
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
  });

  it('renders "-" for null or empty description', () => {
    const wrapper = mount(RadiologyTypeTable, {
      props: {
        items: mockItems,
        pagination: mockPagination,
        loading: false
      }
    });

    const rows = wrapper.findAll('tbody tr');
    const emptyRowCells = rows[1].findAll('td');

    expect(emptyRowCells[2].text()).toBe('-');
  });

  it('emits edit, delete, and sort events correctly', async () => {
    const wrapper = mount(RadiologyTypeTable, {
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
