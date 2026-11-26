import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import DoctorDetailModal from '@/components/master-data/doctors/DoctorDetailModal.vue'

function mountDetailModal(props = {}) {
  return mount(DoctorDetailModal, {
    props: {
      open: true,
      loading: false,
      detail: null,
      ...props,
    },
    global: {
      stubs: {
        Teleport: true,
      },
    },
  })
}

describe('DoctorDetailModal', () => {
  it('merender seluruh section detail (termasuk Alergi dan Nomor HP) serta nilai kosong sebagai —', () => {
    const wrapper = mountDetailModal({
      detail: {
        person: {
          name: 'dr. Andi',
          gender: 'L',
          blood_type: null,
          allergies: 'Amoxicillin',
          phone: '081234567890',
        },
        professional: {
          is_active: true,
          str_number: null,
        },
      },
    })

    const fullText = wrapper.text()
    expect(fullText).toContain('Identitas Person / Pegawai')
    expect(fullText).toContain('Alamat & Kontak')
    expect(fullText).toContain('Profil Profesional Dokter')
    expect(fullText).toContain('dr. Andi')
    expect(fullText).toContain('Laki-laki')
    expect(fullText).toContain('Amoxicillin')
    expect(fullText).toContain('081234567890')

    const values = wrapper.findAll('dd')
    const emptyValues = values.filter((v) => v.text() === '—')
    expect(emptyValues.length).toBeGreaterThan(0)
  })

  it('menampilkan indikator loading detail dan errorMessage jika gagal memuat', async () => {
    const loadingWrapper = mountDetailModal({ loading: true, detail: null })
    expect(loadingWrapper.text()).toContain('Memuat detail dokter...')
    expect(loadingWrapper.find('svg.animate-spin').exists()).toBe(true)

    const errorWrapper = mountDetailModal({
      loading: false,
      detail: null,
      errorMessage: 'Gagal memuat detail dokter.',
    })
    expect(errorWrapper.get('[role="alert"]').text()).toContain('Gagal memuat detail dokter.')
  })

  it('memancarkan event edit dan close dari tombol footer', async () => {
    const wrapper = mountDetailModal({
      detail: {
        person: { name: 'dr. Andi', gender: 'L' },
        professional: { is_active: true },
      },
    })

    const buttons = wrapper.findAll('button')
    await buttons.find((b) => b.text().trim() === 'Edit Dokter').trigger('click')
    expect(wrapper.emitted('edit')).toBeTruthy()

    await buttons.find((b) => b.text().trim() === 'Tutup').trigger('click')
    expect(wrapper.emitted('close')).toBeTruthy()
  })
})
