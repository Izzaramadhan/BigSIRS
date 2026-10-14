import { mount } from '@vue/test-utils'
import { describe, it, expect } from 'vitest'
import ProcedureUserMappingTable from './ProcedureUserMappingTable.vue'

describe('ProcedureUserMappingTable.vue', () => {
  const mockItems = [
    {
      id: 1,
      procedure: { id: 1, code: 'KGA031', name: 'Konsultasi /Pemeriksaan /Medikasi' },
      employees: [
        { id: 10, code: '123', name: 'Person 1694', profession: 'Dokter', is_active: true }
      ]
    },
    {
      id: 2,
      procedure: { id: 2, code: 'KGA28', name: 'Pembersihan Karang Gigi' },
      employees: [
        { id: 11, code: '124', name: 'Dr. Jane Doe', profession: 'Dokter Gigi', is_active: true }
      ]
    }
  ]

  it('tidak menampilkan header kolom Kode Tindakan dan menampilkan 4 kolom dengan urutan yang benar', () => {
    const wrapper = mount(ProcedureUserMappingTable, {
      props: { items: mockItems }
    })

    const headers = wrapper.findAll('th')
    expect(headers).toHaveLength(4)

    const headerTexts = headers.map(th => th.text().trim())
    expect(headerTexts[0]).toBe('No')
    expect(headerTexts[1]).toContain('Nama Tindakan')
    expect(headerTexts[2]).toBe('Pegawai Terpetakan')
    expect(headerTexts[3]).toBe('Aksi')

    // Header Kode Tindakan tidak boleh ada
    expect(wrapper.text()).not.toContain('Kode Tindakan')
  })

  it('tidak menampilkan kode tindakan (misal: KGA031, KGA28) pada setiap baris data tabel', () => {
    const wrapper = mount(ProcedureUserMappingTable, {
      props: { items: mockItems }
    })

    const rows = wrapper.findAll('tbody tr')
    expect(rows).toHaveLength(2)

    // Periksa setiap sel pada baris pertama
    const firstRowCells = rows[0].findAll('td')
    expect(firstRowCells).toHaveLength(4)

    // Kolom 1: No
    expect(firstRowCells[0].text()).toBe('1')
    // Kolom 2: Nama Tindakan
    expect(firstRowCells[1].text()).toBe('Konsultasi /Pemeriksaan /Medikasi')
    // Kolom 3: Pegawai Terpetakan
    expect(firstRowCells[2].text()).toContain('Person 1694')
    // Kolom 4: Aksi
    expect(firstRowCells[3].exists()).toBe(true)

    // Pastikan kode tindakan KGA031 dan KGA28 tidak dirender di tabel
    expect(wrapper.text()).not.toContain('KGA031')
    expect(wrapper.text()).not.toContain('KGA28')
  })

  it('menampilkan nama dan profesi pegawai dengan format yang benar', () => {
    const wrapper = mount(ProcedureUserMappingTable, {
      props: { items: mockItems }
    })

    const employeeTag = wrapper.find('.badge-info')
    expect(employeeTag.exists()).toBe(true)
    expect(employeeTag.text()).toContain('Person 1694')
    expect(employeeTag.text()).toContain('—')
    expect(employeeTag.text()).toContain('Dokter')
    
    // Pastikan tidak ada nama dokter lain yang tidak terkait
    expect(wrapper.text()).not.toContain('Dr. drg. Suparyono Saleh')
  })

  it('mendukung sorting pada kolom Nama Tindakan dan tidak ada sorting untuk kode tindakan', async () => {
    const wrapper = mount(ProcedureUserMappingTable, {
      props: {
        items: mockItems,
        sortConfig: { column: 'name', direction: 'asc' }
      }
    })

    // Header nama tindakan memiliki kelas sortable
    const nameHeader = wrapper.find('th.col-name')
    expect(nameHeader.exists()).toBe(true)
    expect(nameHeader.classes()).toContain('sortable')

    // Klik header Nama Tindakan memicu emit sort
    await nameHeader.trigger('click')
    expect(wrapper.emitted('sort')).toBeTruthy()
    expect(wrapper.emitted('sort')[0]).toEqual([{ column: 'name', direction: 'desc' }])

    // Tidak ada header dengan sort code
    const allThs = wrapper.findAll('th')
    for (const th of allThs) {
      expect(th.classes()).not.toContain('col-code')
    }
  })

  it('menggunakan colspan 4 untuk empty state dan 4 kolom pada loading skeleton', () => {
    // Empty state
    const emptyWrapper = mount(ProcedureUserMappingTable, {
      props: { items: [] }
    })
    const emptyCell = emptyWrapper.find('.table-empty-cell')
    expect(emptyCell.exists()).toBe(true)
    expect(emptyCell.attributes('colspan')).toBe('4')

    // Loading skeleton
    const loadingWrapper = mount(ProcedureUserMappingTable, {
      props: { items: [], loading: true }
    })
    const skeletonComponent = loadingWrapper.findComponent({ name: 'MasterDataSkeletonRow' })
    expect(skeletonComponent.exists()).toBe(true)
    expect(skeletonComponent.props('columns')).toBe(4)
  })

  it('memicu emit edit dan delete ketika tombol aksi diklik', async () => {
    const wrapper = mount(ProcedureUserMappingTable, {
      props: { items: mockItems }
    })

    const actionButtons = wrapper.findComponent({ name: 'MasterDataActionButtons' })
    expect(actionButtons.exists()).toBe(true)

    await actionButtons.vm.$emit('edit')
    expect(wrapper.emitted('edit')).toBeTruthy()
    expect(wrapper.emitted('edit')[0]).toEqual([mockItems[0]])

    await actionButtons.vm.$emit('delete')
    expect(wrapper.emitted('delete')).toBeTruthy()
    expect(wrapper.emitted('delete')[0]).toEqual([mockItems[0]])
  })
})

