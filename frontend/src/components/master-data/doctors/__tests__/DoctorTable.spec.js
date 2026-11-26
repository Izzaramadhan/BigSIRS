import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import DoctorTable from '@/components/master-data/doctors/DoctorTable.vue'

describe('DoctorTable', () => {
  const mockItems = [
    {
      id: 1,
      name: 'dr. Budi, Sp.A',
      specialization: { name: 'Spesialis Anak' },
      str_number: 'STR123',
      sip_number: 'SIP123',
      is_active: true,
    },
    {
      id: 2,
      name: 'dr. Siti, Sp.OG',
      specialization: { name: 'Spesialis Kandungan' },
      str_number: 'STR456',
      sip_number: 'SIP456',
      is_active: false,
    },
  ]

  it('hanya memiliki kolom No, Nama Dokter, Spesialisasi, dan Aksi', () => {
    const wrapper = mount(DoctorTable, {
      props: {
        items: mockItems,
        offset: 0,
        loading: false,
      },
    })

    const headers = wrapper.findAll('thead th').map((th) => th.text())
    expect(headers).toEqual(['No', 'Nama Dokter', 'Spesialisasi', 'Aksi'])

    const fullText = wrapper.text()
    expect(fullText).not.toContain('No. STR')
    expect(fullText).not.toContain('No. SIP')
    expect(fullText).not.toContain('Status')
  })

  it('menampilkan specialization.name tanpa stringify object JSON dan mendukung fallback kosong', () => {
    const wrapper = mount(DoctorTable, {
      props: {
        items: [...mockItems, { id: 3, name: 'dr. Andi', specialization: null }],
        offset: 0,
        loading: false,
      },
    })

    const rows = wrapper.findAll('tbody tr')
    expect(rows[0].findAll('td')[2].text()).toBe('Spesialis Anak')
    expect(rows[1].findAll('td')[2].text()).toBe('Spesialis Kandungan')
    expect(rows[2].findAll('td')[2].text()).toBe('—')
    expect(wrapper.text()).not.toContain('{')
  })

  it('menghitung nomor urut lintas halaman sesuai offset', () => {
    const wrapper = mount(DoctorTable, {
      props: {
        items: mockItems,
        offset: 15,
        loading: false,
      },
    })

    expect(wrapper.find('tbody tr:first-child td:first-child').text()).toBe('16')
    expect(wrapper.find('tbody tr:nth-child(2) td:first-child').text()).toBe('17')
  })

  it('tombol Lihat Detail, Edit, dan Hapus memiliki aria-label dan memancarkan event', async () => {
    const wrapper = mount(DoctorTable, {
      props: {
        items: mockItems,
        offset: 0,
        loading: false,
      },
    })

    await wrapper.get('button[aria-label="Lihat detail dr. Budi, Sp.A"]').trigger('click')
    expect(wrapper.emitted('view')[0][0]).toEqual(mockItems[0])

    await wrapper.get('button[aria-label="Edit dr. Budi, Sp.A"]').trigger('click')
    expect(wrapper.emitted('edit')[0][0]).toEqual(mockItems[0])

    await wrapper.get('button[aria-label="Hapus dr. Budi, Sp.A"]').trigger('click')
    expect(wrapper.emitted('delete')[0][0]).toEqual(mockItems[0])
  })

  it('menampilkan skeleton loading saat prop loading true', () => {
    const wrapper = mount(DoctorTable, {
      props: {
        items: [],
        offset: 0,
        loading: true,
      },
    })

    expect(wrapper.get('tbody').attributes('aria-busy')).toBe('true')
    expect(wrapper.findAll('tbody tr')).toHaveLength(5)
  })
})
