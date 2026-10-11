import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import RadiologyGroupTable from '../RadiologyGroupTable.vue';

describe('RadiologyGroupTable.vue', () => {
  const mockItems = [
    {
      id: 1,
      name: 'Rontgen Thorax',
      radiology_type_id: 2,
      radiology_category_id: 3,
      activity_type_id: 1,
      price: 150000,
      interpretation_price: 50000,
      is_active: true,
      loinc_code: '24606-6',
      loinc_url: 'https://loinc.org/24606-6/',
      type: { id: 2, name: 'Foto Polos' },
      category: { id: 3, name: 'Radiografi Konvensional' },
      item_groups: [
        { id: 10, name: 'Thorax AP/PA' },
        { id: 11, name: 'Thorax Lateral' }
      ]
    },
    {
      id: 2,
      name: 'USG Abdomen',
      radiology_type_id: null,
      radiology_category_id: null,
      activity_type_id: null,
      price: 0,
      interpretation_price: 0,
      is_active: false,
      loinc_code: null,
      loinc_url: null,
      type: null,
      category: null,
      item_groups: []
    }
  ];

  const mockPagination = {
    current_page: 2,
    per_page: 10,
    total: 12,
    last_page: 2
  };

  it('renders exact 11 columns in the specified order: No, Grup, Tipe, Kategori, Item Pemeriksaan, Harga, Harga Interpretasi, Status, Loinc Code, Loinc Url, Aksi', () => {
    const wrapper = mount(RadiologyGroupTable, {
      props: {
        items: mockItems,
        pagination: mockPagination,
        loading: false
      }
    });

    const headers = wrapper.findAll('thead th');
    expect(headers.length).toBe(11);
    expect(headers[0].text()).toContain('No');
    expect(headers[1].text()).toContain('Grup');
    expect(headers[2].text()).toContain('Tipe');
    expect(headers[3].text()).toContain('Kategori');
    expect(headers[4].text()).toContain('Item Pemeriksaan');
    expect(headers[5].text()).toContain('Harga');
    expect(headers[6].text()).toContain('Harga Interpretasi');
    expect(headers[7].text()).toContain('Status');
    expect(headers[8].text()).toContain('Loinc Code');
    expect(headers[9].text()).toContain('Loinc Url');
    expect(headers[10].text()).toContain('Aksi');
  });

  it('calculates row numbers correctly based on pagination', () => {
    const wrapper = mount(RadiologyGroupTable, {
      props: {
        items: mockItems,
        pagination: mockPagination,
        loading: false
      }
    });

    const rows = wrapper.findAll('tbody tr');
    expect(rows.length).toBe(2);
    // Page 2, index 0 => (2-1)*10 + 0 + 1 = 11
    expect(rows[0].findAll('td')[0].text()).toBe('11');
    // Page 2, index 1 => 12
    expect(rows[1].findAll('td')[0].text()).toBe('12');
  });

  it('renders relation names and Rupiah formatted prices correctly', () => {
    const wrapper = mount(RadiologyGroupTable, {
      props: {
        items: mockItems,
        pagination: mockPagination,
        loading: false
      }
    });

    const firstRowCells = wrapper.findAll('tbody tr')[0].findAll('td');
    // Grup
    expect(firstRowCells[1].text()).toContain('Rontgen Thorax');
    // Tipe
    expect(firstRowCells[2].text()).toContain('Foto Polos');
    // Kategori
    expect(firstRowCells[3].text()).toContain('Radiografi Konvensional');
    // Item Pemeriksaan (contains items)
    expect(firstRowCells[4].text()).toContain('Thorax AP/PA');
    expect(firstRowCells[4].text()).toContain('Thorax Lateral');
    // Harga
    expect(firstRowCells[5].text()).toMatch(/Rp\s*150\.000/);
    // Harga Interpretasi
    expect(firstRowCells[6].text()).toMatch(/Rp\s*50\.000/);
    // Status
    expect(firstRowCells[7].text()).toContain('Aktif');
    // Loinc Code
    expect(firstRowCells[8].text()).toContain('24606-6');
    // Loinc Url
    const link = firstRowCells[9].find('a');
    expect(link.exists()).toBe(true);
    expect(link.attributes('href')).toBe('https://loinc.org/24606-6/');
    expect(link.attributes('target')).toBe('_blank');
    expect(link.attributes('rel')).toBe('noopener noreferrer');
  });

  it('handles empty relations and 0 prices with appropriate fallbacks', () => {
    const wrapper = mount(RadiologyGroupTable, {
      props: {
        items: mockItems,
        pagination: mockPagination,
        loading: false
      }
    });

    const secondRowCells = wrapper.findAll('tbody tr')[1].findAll('td');
    // Grup
    expect(secondRowCells[1].text()).toContain('USG Abdomen');
    // Tipe fallback
    expect(secondRowCells[2].text()).toBe('-');
    // Kategori fallback
    expect(secondRowCells[3].text()).toBe('-');
    // Item Pemeriksaan fallback
    expect(secondRowCells[4].text()).toBe('-');
    // Harga 0
    expect(secondRowCells[5].text()).toMatch(/Rp\s*0/);
    // Harga Interpretasi 0
    expect(secondRowCells[6].text()).toMatch(/Rp\s*0/);
    // Status
    expect(secondRowCells[7].text()).toContain('Nonaktif');
    // Loinc Code fallback
    expect(secondRowCells[8].text()).toBe('-');
    // Loinc Url fallback
    expect(secondRowCells[9].text()).toBe('-');
  });

  it('emits edit and delete events when action buttons are clicked', async () => {
    const wrapper = mount(RadiologyGroupTable, {
      props: {
        items: mockItems,
        pagination: mockPagination,
        loading: false
      }
    });

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
