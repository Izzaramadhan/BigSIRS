import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import RadiologyItemTable from '../RadiologyItemTable.vue';

describe('RadiologyItemTable.vue', () => {
  const mockItems = [
    {
      id: 1,
      name: 'Rontgen Thorax PA',
      radiology_item_group_id: 10,
      is_active: true,
      group: {
        id: 10,
        name: 'Foto Rontgen Tanpa Bahan Kontras'
      }
    },
    {
      id: 2,
      name: 'USG Abdomen Upper-Lower',
      radiology_item_group_id: null,
      is_active: false,
      group: null
    }
  ];

  const mockPagination = {
    current_page: 2,
    per_page: 10,
    total: 12,
    last_page: 2
  };

  it('renders exact 4 columns in the specified order: No, Item, Kelompok Item, Aksi', () => {
    const wrapper = mount(RadiologyItemTable, {
      props: {
        items: mockItems,
        pagination: mockPagination,
        loading: false
      }
    });

    const headers = wrapper.findAll('thead th');
    expect(headers.length).toBe(4);
    expect(headers[0].text()).toContain('No');
    expect(headers[1].text()).toContain('Item');
    expect(headers[2].text()).toContain('Kelompok Item');
    expect(headers[3].text()).toContain('Aksi');
  });

  it('calculates row numbers correctly based on pagination', () => {
    const wrapper = mount(RadiologyItemTable, {
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

  it('renders row data correctly with item name and group name', () => {
    const wrapper = mount(RadiologyItemTable, {
      props: {
        items: mockItems,
        pagination: mockPagination,
        loading: false
      }
    });

    const rows = wrapper.findAll('tbody tr');
    const cells = rows[0].findAll('td');

    expect(cells[1].text()).toContain('Rontgen Thorax PA');
    expect(cells[2].text()).toContain('Foto Rontgen Tanpa Bahan Kontras');
  });

  it('renders "-" when group is null or missing', () => {
    const wrapper = mount(RadiologyItemTable, {
      props: {
        items: mockItems,
        pagination: mockPagination,
        loading: false
      }
    });

    const rows = wrapper.findAll('tbody tr');
    const cells = rows[1].findAll('td');

    expect(cells[1].text()).toContain('USG Abdomen Upper-Lower');
    expect(cells[2].text()).toBe('-');
  });

  it('emits edit, delete, and sort events correctly', async () => {
    const wrapper = mount(RadiologyItemTable, {
      props: {
        items: mockItems,
        pagination: mockPagination,
        loading: false
      }
    });

    // Click sort header on Item column
    const sortHeaders = wrapper.findAll('th.sortable');
    await sortHeaders[0].trigger('click');
    expect(wrapper.emitted('sort')).toBeTruthy();
    expect(wrapper.emitted('sort')[0]).toEqual(['name']);

    // Click sort header on Kelompok Item column
    await sortHeaders[1].trigger('click');
    expect(wrapper.emitted('sort')[1]).toEqual(['radiology_item_group_id']);

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
