import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import LaboratoryGroupTable from '../LaboratoryGroupTable.vue';

describe('LaboratoryGroupTable.vue', () => {
  const sampleItems = [
    {
      id: 1,
      name: 'Complete Blood Count + LED',
      price: 85000,
      harga_jaspel: 25000,
      is_active: true,
      loinc_code: '58410-2',
      loinc_url: 'https://loinc.org/58410-2/',
      snomed_code: '439958008',
      snomed_url: 'https://browser.ihtsdotools.org/?perspective=full&conceptId1=439958008',
      category: { id: 1, name: 'Hematologi Rutin' },
      items: [
        { id: 6, name: 'HEMOGLOBIN', unit: 'g/dL' },
        { id: 7, name: 'HITUNG LEKOSIT', unit: '10³/uL' }
      ]
    },
    {
      id: 2,
      name: 'Paket Kolesterol',
      price: null,
      harga_jaspel: null,
      is_active: false,
      loinc_code: null,
      loinc_url: 'not-a-valid-url',
      snomed_code: null,
      snomed_url: null,
      category: null,
      items: []
    }
  ];

  const defaultProps = {
    items: sampleItems,
    loading: false,
    sort: { column: 'name', direction: 'asc' },
    pagination: {
      current_page: 1,
      per_page: 10,
      total: 2,
      last_page: 1
    }
  };

  const mountTable = (propsData = {}) => {
    return mount(LaboratoryGroupTable, {
      props: { ...defaultProps, ...propsData }
    });
  };

  it('renders table headers with exactly 12 columns in specified order', () => {
    const wrapper = mountTable();
    const ths = wrapper.findAll('thead th');

    expect(ths.length).toBe(12);
    expect(ths[0].text()).toBe('No');
    expect(ths[1].text()).toBe('Grup');
    expect(ths[2].text()).toBe('Kategori');
    expect(ths[3].text()).toBe('Item');
    expect(ths[4].text()).toBe('Harga');
    expect(ths[5].text()).toBe('Harga Jaspel');
    expect(ths[6].text()).toBe('Status');
    expect(ths[7].text()).toBe('LOINC Code');
    expect(ths[8].text()).toBe('LOINC URL');
    expect(ths[9].text()).toBe('SNOMED Code');
    expect(ths[10].text()).toBe('SNOMED URL');
    expect(ths[11].text()).toBe('Aksi');
  });

  it('renders data correctly with currency formatting and safe text rendering', () => {
    const wrapper = mountTable();
    const rows = wrapper.findAll('tbody tr');
    expect(rows.length).toBe(2);

    const firstRowCells = rows[0].findAll('td');
    // 1. No
    expect(firstRowCells[0].text()).toBe('1');
    // 2. Grup
    expect(firstRowCells[1].text()).toBe('Complete Blood Count + LED');
    // 3. Kategori
    expect(firstRowCells[2].text()).toBe('Hematologi Rutin');
    // 4. Item (safe rendering, superscript preserved)
    expect(firstRowCells[3].text()).toContain('HEMOGLOBIN');
    expect(firstRowCells[3].text()).toContain('HITUNG LEKOSIT');
    expect(firstRowCells[3].text()).toContain('10³/uL');
    // 5. Harga
    expect(firstRowCells[4].text()).toContain('Rp');
    expect(firstRowCells[4].text()).toContain('85.000');
    // 6. Harga Jaspel
    expect(firstRowCells[5].text()).toContain('Rp');
    expect(firstRowCells[5].text()).toContain('25.000');
    // 7. Status
    expect(firstRowCells[6].text()).toBe('Aktif');
    // 8. LOINC Code
    expect(firstRowCells[7].text()).toBe('58410-2');
    // 9. LOINC URL (valid URL becomes link with "Buka tautan")
    const loincLink = firstRowCells[8].find('a');
    expect(loincLink.exists()).toBe(true);
    expect(loincLink.attributes('href')).toBe('https://loinc.org/58410-2/');
    expect(loincLink.attributes('target')).toBe('_blank');
    expect(loincLink.attributes('rel')).toContain('noopener');
    expect(loincLink.text()).toBe('Buka tautan');
    // 10. SNOMED Code
    expect(firstRowCells[9].text()).toBe('439958008');
    // 11. SNOMED URL
    const snomedLink = firstRowCells[10].find('a');
    expect(snomedLink.exists()).toBe(true);
    expect(snomedLink.text()).toBe('Buka tautan');
    // 12. Aksi
    expect(firstRowCells[11].find('.action-buttons').exists()).toBe(true);
  });

  it('displays empty or invalid values as dash "-"', () => {
    const wrapper = mountTable();
    const secondRowCells = wrapper.findAll('tbody tr')[1].findAll('td');

    // Kategori is null
    expect(secondRowCells[2].text()).toBe('-');
    // Items is empty
    expect(secondRowCells[3].text()).toBe('-');
    // Harga is null
    expect(secondRowCells[4].text()).toBe('-');
    // Harga Jaspel is null
    expect(secondRowCells[5].text()).toBe('-');
    // Status is false (Nonaktif badge)
    expect(secondRowCells[6].text()).toBe('Nonaktif');
    // LOINC Code is null
    expect(secondRowCells[7].text()).toBe('-');
    // LOINC URL is invalid
    expect(secondRowCells[8].text()).toBe('-');
    // SNOMED Code is null
    expect(secondRowCells[9].text()).toBe('-');
    // SNOMED URL is null
    expect(secondRowCells[10].text()).toBe('-');
  });

  it('emits edit and delete events when action buttons are clicked', async () => {
    const wrapper = mountTable();
    const firstRow = wrapper.findAll('tbody tr')[0];

    await firstRow.find('button.edit').trigger('click');
    expect(wrapper.emitted('edit')).toBeTruthy();
    expect(wrapper.emitted('edit')[0][0].id).toBe(1);

    await firstRow.find('button.delete').trigger('click');
    expect(wrapper.emitted('delete')).toBeTruthy();
    expect(wrapper.emitted('delete')[0][0].id).toBe(1);
  });

  it('emits sort event when sortable headers are clicked', async () => {
    const wrapper = mountTable();

    await wrapper.find('th.col-group').trigger('click');
    expect(wrapper.emitted('sort')).toBeTruthy();
    expect(wrapper.emitted('sort')[0][0]).toEqual({ column: 'name', direction: 'desc' });
  });
});
