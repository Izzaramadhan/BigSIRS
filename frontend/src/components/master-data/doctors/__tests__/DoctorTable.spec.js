import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import DoctorTable from '@/components/master-data/doctors/DoctorTable.vue';

describe('DoctorTable', () => {
  const mockItems = [
    {
      id: 1,
      name: 'dr. Budi, Sp.A',
      nik: '1234567890123456',
      specialization: { name: 'Spesialis Anak' },
      str_number: 'STR123',
      sip_number: 'SIP123',
      is_active: true
    },
    {
      id: 2,
      name: 'dr. Siti, Sp.OG',
      nik: '0987654321098765',
      specialization: { name: 'Spesialis Kandungan' },
      str_number: 'STR456',
      sip_number: 'SIP456',
      is_active: false
    }
  ];

  const mockPagination = {
    current_page: 2,
    per_page: 10,
    total: 20
  };

  const mockSort = {
    column: 'name',
    direction: 'asc'
  };

  it('hanya memiliki kolom No, Nama, Spesialisasi, dan Aksi', () => {
    const wrapper = mount(DoctorTable, {
      props: {
        items: mockItems,
        pagination: mockPagination,
        sort: mockSort,
        loading: false
      }
    });

    const headers = wrapper.findAll('th');
    expect(headers.length).toBe(4);
    expect(headers[0].text()).toContain('No');
    expect(headers[1].text()).toContain('Nama Dokter');
    expect(headers[2].text()).toContain('Spesialisasi');
    expect(headers[3].text()).toContain('Aksi');
    
    // Pastikan kolom ini TIDAK ADA
    const fullText = wrapper.text();
    expect(fullText).not.toContain('No. STR');
    expect(fullText).not.toContain('No. SIP');
    expect(fullText).not.toContain('Status');
  });

  it('Spesialisasi menampilkan .name, bukan object JSON', () => {
    const wrapper = mount(DoctorTable, {
      props: {
        items: mockItems,
        pagination: mockPagination,
        sort: mockSort,
        loading: false
      }
    });

    const rows = wrapper.findAll('tbody tr');
    expect(rows[0].findAll('td')[2].text()).toBe('Spesialis Anak');
    expect(rows[1].findAll('td')[2].text()).toBe('Spesialis Kandungan');
    
    // Pastikan tidak ada object ter-stringify
    expect(wrapper.text()).not.toContain('{');
    expect(wrapper.text()).not.toContain('}');
  });

  it('Nomor urut sesuai pagination', () => {
    const wrapper = mount(DoctorTable, {
      props: {
        items: mockItems,
        pagination: mockPagination,
        sort: mockSort,
        loading: false
      }
    });

    const firstRowNo = wrapper.find('tbody tr:first-child td:first-child').text();
    expect(firstRowNo).toBe('11');
  });

  it('Ikon Lihat tersedia dan klik memanggil event', async () => {
    const wrapper = mount(DoctorTable, {
      props: {
        items: mockItems,
        pagination: mockPagination,
        sort: mockSort,
        loading: false
      }
    });

    const viewButton = wrapper.find('button[title="Lihat Detail"]');
    expect(viewButton.exists()).toBe(true);

    await viewButton.trigger('click');
    expect(wrapper.emitted('view')).toBeTruthy();
    expect(wrapper.emitted('view')[0][0]).toEqual(mockItems[0]);
  });

  it('Menampilkan pill status yang benar dan dapat di-toggle', async () => {
    const wrapper = mount(DoctorTable, {
      props: {
        items: mockItems,
        pagination: mockPagination,
        sort: mockSort,
        loading: false
      }
    });

    const pills = wrapper.findAll('button.status-pill');
    expect(pills.length).toBe(2);

    // Baris pertama (Aktif)
    const activePill = pills[0];
    expect(activePill.text()).toContain('Aktif');
    expect(activePill.classes()).toContain('status-pill--active');
    
    await activePill.trigger('click');
    expect(wrapper.emitted('toggle-status')).toBeTruthy();
    expect(wrapper.emitted('toggle-status')[0][0].is_active).toBe(true);

    // Baris kedua (Nonaktif)
    const inactivePill = pills[1];
    expect(inactivePill.text()).toContain('Nonaktif');
    expect(inactivePill.classes()).toContain('status-pill--inactive');
    
    await inactivePill.trigger('click');
    expect(wrapper.emitted('toggle-status')[1][0].is_active).toBe(false);
  });

  it('Tombol status disabled jika prop statusLoadingId sama dengan id dokter', () => {
    const wrapper = mount(DoctorTable, {
      props: {
        items: mockItems,
        pagination: mockPagination,
        sort: mockSort,
        loading: false,
        statusLoadingId: 1 // record id 1 sedang diproses
      }
    });

    const pills = wrapper.findAll('button.status-pill');
    
    // Baris pertama harus disabled dan teks berubah
    expect(pills[0].attributes('disabled')).toBeDefined();
    expect(pills[0].text()).toContain('Memproses...');
    
    // Baris kedua tidak disabled
    expect(pills[1].attributes('disabled')).toBeUndefined();
    expect(pills[1].text()).not.toContain('Memproses...');
  });
});
