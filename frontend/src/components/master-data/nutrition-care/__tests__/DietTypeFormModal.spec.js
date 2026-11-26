import { beforeEach, describe, expect, it, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import DietTypeFormModal from '../DietTypeFormModal.vue'
import {
  DIET_TYPE_DESCRIPTION_MAX_LENGTH,
  DIET_TYPE_NAME_MAX_LENGTH,
} from '../diet-type.helpers'

describe('DietTypeFormModal', () => {
  const mountModal = (propsData = {}) =>
    mount(DietTypeFormModal, {
      props: {
        open: true,
        dietType: null,
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

  it('renders correctly for add new mode with maxlength hints', () => {
    const wrapper = mountModal()

    expect(wrapper.find('h2').text()).toBe('Tambah Asuhan Gizi')
    expect(wrapper.get('input#name').attributes('maxlength')).toBe(
      String(DIET_TYPE_NAME_MAX_LENGTH),
    )
    expect(wrapper.get('textarea#description').attributes('maxlength')).toBe(
      String(DIET_TYPE_DESCRIPTION_MAX_LENGTH),
    )
  })

  it('hydrates stored name and description in edit mode', async () => {
    const wrapper = mountModal({
      open: false,
      dietType: { id: 1, name: 'Diet Jantung', description: 'Rendah kolesterol' },
    })

    await wrapper.setProps({ open: true })

    expect(wrapper.find('h2').text()).toBe('Edit Asuhan Gizi')
    expect(wrapper.get('input#name').element.value).toBe('Diet Jantung')
    expect(wrapper.get('textarea#description').element.value).toBe('Rendah kolesterol')
  })

  it('validates empty name and max character limits before submit', async () => {
    const wrapper = mountModal()

    await wrapper.get('form').trigger('submit.prevent')
    expect(wrapper.text()).toContain('Nama asuhan gizi wajib diisi.')
    expect(wrapper.emitted('submit')).toBeFalsy()

    await wrapper.get('input#name').setValue('X'.repeat(DIET_TYPE_NAME_MAX_LENGTH + 1))
    await wrapper.get('textarea#description').setValue('Y'.repeat(DIET_TYPE_DESCRIPTION_MAX_LENGTH + 1))
    await wrapper.get('form').trigger('submit.prevent')

    expect(wrapper.text()).toContain(`maksimal ${DIET_TYPE_NAME_MAX_LENGTH} karakter`)
    expect(wrapper.text()).toContain(`maksimal ${DIET_TYPE_DESCRIPTION_MAX_LENGTH} karakter`)
    expect(wrapper.emitted('submit')).toBeFalsy()
  })

  it('emits normalized payload and displays server errors', async () => {
    const wrapper = mountModal({
      serverErrors: { name: 'Nama asuhan gizi sudah digunakan.' },
      generalError: 'Terjadi kesalahan server.',
    })

    expect(wrapper.text()).toContain('Nama asuhan gizi sudah digunakan.')
    expect(wrapper.text()).toContain('Terjadi kesalahan server.')

    await wrapper.get('input#name').setValue('  Diet   Diabetes  ')
    await wrapper.get('textarea#description').setValue('   ')
    await wrapper.get('form').trigger('submit.prevent')

    expect(wrapper.emitted('submit')[0][0]).toEqual({
      name: 'Diet Diabetes',
      description: null,
    })
  })

  it('blocks double-submit and close while saving is true', async () => {
    const wrapper = mountModal({ saving: true })

    await wrapper.get('input#name').setValue('Diet Lunak')
    await wrapper.get('form').trigger('submit.prevent')
    await wrapper.get('button[aria-label="Tutup"]').trigger('click')

    expect(wrapper.emitted('submit')).toBeFalsy()
    expect(wrapper.emitted('close')).toBeFalsy()
  })
})
