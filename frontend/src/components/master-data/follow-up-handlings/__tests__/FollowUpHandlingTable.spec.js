import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import FollowUpHandlingTable from '../FollowUpHandlingTable.vue';

const mockItems = [
  {
    id: 1,
    name: 'Sembuh'
  },
  {
    id: 2,
    name: 'Rujuk Eksternal'
  }
];

const mockPagination = {
  current_page: 2,
  per_page: 10,
  total: 25,
  last_page: 3
};

describe('FollowUpHandlingTable.vue', () => {
  it('renders exact 3 columns in old system order: No, Nama, Aksi', () => {
    const wrapper = mount(FollowUpHandlingTable, {
      props: {
        items: mockItems,
        pagination: mockPagination,
        loading: false
      }
    });

    const headers = wrapper.findAll('thead th');
    expect(headers).toHaveLength(3);
    expect(headers[0].text()).toContain('No');
    expect(headers[1].text()).toContain('Nama');
    expect(headers[2].text()).toContain('Aksi');
  });

  it('calculates row number based on pagination', () => {
    const wrapper = mount(FollowUpHandlingTable, {
      props: {
        items: mockItems,
        pagination: mockPagination,
        loading: false
      }
    });

    const rows = wrapper.findAll('tbody tr');
    // Page 2, per_page 10: item 0 is (2-1)*10 + 0 + 1 = 11, item 1 is 12
    expect(rows[0].find('.col-no').text()).toBe('11');
    expect(rows[1].find('.col-no').text()).toBe('12');
  });

  it('displays handling name properly', () => {
    const wrapper = mount(FollowUpHandlingTable, {
      props: {
        items: mockItems,
        pagination: mockPagination,
        loading: false
      }
    });

    const rows = wrapper.findAll('tbody tr');
    expect(rows[0].find('.col-name').text()).toBe('Sembuh');
    expect(rows[1].find('.col-name').text()).toBe('Rujuk Eksternal');
  });

  it('emits edit and delete events when action buttons are clicked', async () => {
    const wrapper = mount(FollowUpHandlingTable, {
      props: {
        items: mockItems,
        pagination: mockPagination,
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
});
