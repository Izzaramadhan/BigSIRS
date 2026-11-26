import { beforeEach, describe, expect, it, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import InsuranceTypeFormModal from '../InsuranceTypeFormModal.vue'
import { INACBG_ID_MAX_LENGTH, INSURANCE_NAME_MAX_LENGTH } from '../insurance-type.helpers'

describe('InsuranceTypeFormModal.vue', () => {
  const mountModal = (propsData = {}) =>
    mount(InsuranceTypeFormModal, {
      props: {
        open: true,
        insuranceType: null,
        saving: false,
        ...propsData,
      },
      global: {
        stubs: {
          Teleport: true,
        },
      },
    })

  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('renders correctly for add new mode without status checkbox', () => {
    const wrapper = mountModal()

    expect(wrapper.find('h2').text()).toBe('Tambah Jenis Asuransi')
    expect(wrapper.text()).toContain('Simpan')
    expect(wrapper.text()).not.toContain('Simpan Perubahan')
    expect(wrapper.find('input[type="checkbox"]').exists()).toBe(false)
    expect(wrapper.text()).not.toContain('Nonaktif')
  })

  it('renders correctly for edit mode and preserves legacy code/type on submit', async () => {
    const wrapper = mountModal({
      insuranceType: {
        id: 5,
        code: 'ASR-05',
        type: 'bpjs',
        name: 'BPJS Pensiunan',
        is_government: true,
        inacbg_id: '12',
      },
    })

    expect(wrapper.find('h2').text()).toBe('Edit Jenis Asuransi')
    expect(wrapper.text()).toContain('Simpan Perubahan')
    expect(wrapper.get('input#name').element.value).toBe('BPJS Pensiunan')
    expect(wrapper.get('input#inacbg_id').element.value).toBe('12')

    await wrapper.get('input#name').setValue('BPJS Pensiunan PNS')
    await wrapper.get('form').trigger('submit.prevent')

    expect(wrapper.emitted('submit')[0][0]).toEqual({
      name: 'BPJS Pensiunan PNS',
      is_government: true,
      inacbg_id: '12',
      code: 'ASR-05',
      type: 'bpjs',
    })
  })

  it('validates empty name and field max lengths before submit', async () => {
    const wrapper = mountModal()

    await wrapper.get('form').trigger('submit.prevent')
    expect(wrapper.text()).toContain('Nama jenis asuransi wajib diisi.')
    expect(wrapper.emitted('submit')).toBeFalsy()

    await wrapper.get('input#name').setValue('X'.repeat(INSURANCE_NAME_MAX_LENGTH + 1))
    await wrapper.get('input#inacbg_id').setValue('1'.repeat(INACBG_ID_MAX_LENGTH + 1))
    await wrapper.get('form').trigger('submit.prevent')

    expect(wrapper.text()).toContain(`maksimal ${INSURANCE_NAME_MAX_LENGTH} karakter`)
    expect(wrapper.text()).toContain(`maksimal ${INACBG_ID_MAX_LENGTH} karakter`)
    expect(wrapper.emitted('submit')).toBeFalsy()
  })

  it('emits normalized payload for new record and displays server errors', async () => {
    const wrapper = mountModal({
      serverErrors: { name: 'Nama jenis asuransi sudah digunakan.' },
      generalError: 'Gagal menyimpan data.',
    })

    expect(wrapper.text()).toContain('Nama jenis asuransi sudah digunakan.')
    expect(wrapper.text()).toContain('Gagal menyimpan data.')

    await wrapper.get('input#name').setValue('  Asuransi ABC  ')
    const radios = wrapper.findAll('input[type="radio"]')
    await radios[1].setValue(true)
    await wrapper.get('input#inacbg_id').setValue(' 99 ')
    await wrapper.get('form').trigger('submit.prevent')

    expect(wrapper.emitted('submit')[0][0]).toEqual({
      name: 'Asuransi ABC',
      is_government: true,
      inacbg_id: '99',
    })
  })

  it('emits close on Batal and blocks close/submit while saving', async () => {
    const idleWrapper = mountModal()
    const batalBtn = idleWrapper.findAll('button').find((b) => b.text().trim() === 'Batal')
    await batalBtn.trigger('click')
    expect(idleWrapper.emitted('close')).toBeTruthy()

    const savingWrapper = mountModal({ saving: true })
    await savingWrapper.get('input#name').setValue('UMUM')
    await savingWrapper.get('form').trigger('submit.prevent')
    await savingWrapper.get('button[aria-label="Tutup"]').trigger('click')
    expect(savingWrapper.emitted('submit')).toBeFalsy()
    expect(savingWrapper.emitted('close')).toBeFalsy()
  })
})
