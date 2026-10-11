import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import LaboratoryItemTable from '../LaboratoryItemTable.vue';

describe('LaboratoryItemTable.vue', () => {
  const mockItems = [
    {
      id: 1,
      name: 'KOLESTEROL TOTAL',
      reference_value: '<200',
      unit: 'mg/dL',
      is_active: true
    },
    {
      id: 2,
      name: 'HBSAG',
      reference_value: 'Negatif',
      unit: null,
      is_active: false
    },
    {
      id: 3,
      name: 'ITEM TANPA NILAI',
      reference_value: null,
      unit: null,
      is_active: true
    }
  ];

  const mockPagination = {
    current_page: 1,
    per_page: 10,
    total: 3,
    last_page: 1
  };

  it('renders exact 5 columns in the specified order and does NOT contain Status column', () => {
    const wrapper = mount(LaboratoryItemTable, {
      props: {
        items: mockItems,
        pagination: mockPagination,
        loading: false
      }
    });

    const headers = wrapper.findAll('thead th');
    expect(headers.length).toBe(5);
    expect(headers[0].text()).toContain('No');
    expect(headers[1].text()).toContain('Nama Item Lab');
    expect(headers[2].text()).toContain('Standar Normal / Nilai Rujukan');
    expect(headers[3].text()).toContain('Satuan');
    expect(headers[4].text()).toContain('Aksi');

    // Verify Status column does not exist
    expect(wrapper.find('th.col-status').exists()).toBe(false);
    expect(wrapper.find('.status-badge').exists()).toBe(false);
  });

  it('renders row data correctly including reference values and units', () => {
    const wrapper = mount(LaboratoryItemTable, {
      props: {
        items: mockItems,
        pagination: mockPagination,
        loading: false
      }
    });

    const rows = wrapper.findAll('tbody tr');
    expect(rows.length).toBe(3);

    // Row 1
    const firstRowCells = rows[0].findAll('td');
    expect(firstRowCells.length).toBe(5);
    expect(firstRowCells[0].text()).toBe('1');
    expect(firstRowCells[1].text()).toContain('KOLESTEROL TOTAL');
    expect(firstRowCells[2].text()).toContain('<200');
    expect(firstRowCells[3].text()).toContain('mg/dL');

    // Row 2
    const secondRowCells = rows[1].findAll('td');
    expect(secondRowCells.length).toBe(5);
    expect(secondRowCells[0].text()).toBe('2');
    expect(secondRowCells[1].text()).toContain('HBSAG');
    expect(secondRowCells[2].text()).toContain('Negatif');
    expect(secondRowCells[3].text()).toBe('-');
  });

  it('renders "-" for null or empty values', () => {
    const wrapper = mount(LaboratoryItemTable, {
      props: {
        items: mockItems,
        pagination: mockPagination,
        loading: false
      }
    });

    const rows = wrapper.findAll('tbody tr');
    const thirdRowCells = rows[2].findAll('td');
    expect(thirdRowCells[2].text()).toBe('-');
    expect(thirdRowCells[3].text()).toBe('-');
  });

  it('emits sort event when clicking on sortable headers', async () => {
    const wrapper = mount(LaboratoryItemTable, {
      props: {
        items: mockItems,
        pagination: mockPagination,
        loading: false
      }
    });

    const nameHeader = wrapper.find('th.col-name');
    await nameHeader.trigger('click');
    expect(wrapper.emitted('sort')).toBeTruthy();
    expect(wrapper.emitted('sort')[0]).toEqual(['name']);

    const refHeader = wrapper.find('th.col-ref');
    await refHeader.trigger('click');
    expect(wrapper.emitted('sort')[1]).toEqual(['reference_value']);

    const unitHeader = wrapper.find('th.col-unit');
    await unitHeader.trigger('click');
    expect(wrapper.emitted('sort')[2]).toEqual(['unit']);
  });

  it('renders only edit and delete action buttons and emits events with item payload', async () => {
    const wrapper = mount(LaboratoryItemTable, {
      props: {
        items: mockItems,
        pagination: mockPagination,
        loading: false
      }
    });

    // Check that toggle/nonaktifkan button is NOT present
    expect(wrapper.find('.btn-action.toggle-status').exists()).toBe(false);

    // Each row has exactly 2 action buttons (Edit and Delete)
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
