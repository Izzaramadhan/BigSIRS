import { mount } from '@vue/test-utils'
import { describe, it, expect } from 'vitest'
import ProcedureUserMappingTable from './ProcedureUserMappingTable.vue'

describe('ProcedureUserMappingTable.vue', () => {
  it('menampilkan nama dan profesi pegawai dengan format yang benar', () => {
    const mockItems = [
      {
        id: 1,
        procedure: { id: 1, code: 'KGA031', name: 'Konsultasi /Pemeriksaan /Medikasi' },
        employees: [
          { id: 10, code: '123', name: 'Person 1694', profession: 'Dokter', is_active: true }
        ]
      }
    ]

    const wrapper = mount(ProcedureUserMappingTable, {
      props: { items: mockItems }
    })

    const employeeTag = wrapper.find('.badge-info')
    expect(employeeTag.exists()).toBe(true)
    expect(employeeTag.text()).toContain('Person 1694')
    expect(employeeTag.text()).toContain('—')
    expect(employeeTag.text()).toContain('Dokter')
    
    // Pastikan tidak ada nama dokter lain yang dirender
    expect(wrapper.text()).not.toContain('Dr. drg. Suparyono Saleh')
  })
})
