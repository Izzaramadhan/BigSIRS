import { beforeEach, describe, expect, it, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import LetterTypeFormModal from '../LetterTypeFormModal.vue'
import {
  LETTER_TYPE_DESCRIPTION_MAX_LENGTH,
  LETTER_TYPE_NAME_MAX_LENGTH,
  LETTER_TYPE_RESOURCE_MAX_LENGTH,
} from '../letter-types/letter-type.helpers'

describe('LetterTypeFormModal.vue', () => {
  const mountModal = (propsData = {}) =>
    mount(LetterTypeFormModal, {
      props: {
        open: true,
        letterType: null,
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

  it('renders add mode with 3 fields and exact maxlength attributes', () => {
    const wrapper = mountModal()

    expect(wrapper.find('h2').text()).toBe('Tambah Surat')
    expect(wrapper.get('input#name').attributes('maxlength')).toBe(
      String(LETTER_TYPE_NAME_MAX_LENGTH),
    )
    expect(wrapper.get('textarea#description').attributes('maxlength')).toBe(
      String(LETTER_TYPE_DESCRIPTION_MAX_LENGTH),
    )
    expect(wrapper.get('input#legacy_resource').attributes('maxlength')).toBe(
      String(LETTER_TYPE_RESOURCE_MAX_LENGTH),
    )
  })

  it('hydrates stored name, description, and legacy_resource in edit mode', async () => {
    const wrapper = mountModal({
      open: false,
      letterType: {
        id: 1,
        name: 'Surat Edit',
        description: 'Test Edit',
        legacy_resource: 'test.php',
      },
    })

    await wrapper.setProps({ open: true })

    expect(wrapper.find('h2').text()).toBe('Edit Surat')
    expect(wrapper.get('input#name').element.value).toBe('Surat Edit')
    expect(wrapper.get('textarea#description').element.value).toBe('Test Edit')
    expect(wrapper.get('input#legacy_resource').element.value).toBe('test.php')
  })

  it('validates required name and 50/255 length constraints before submit', async () => {
    const wrapper = mountModal()

    await wrapper.get('form').trigger('submit.prevent')
    expect(wrapper.text()).toContain('Nama surat wajib diisi.')
    expect(wrapper.emitted('submit')).toBeFalsy()

    await wrapper.get('input#name').setValue('N'.repeat(LETTER_TYPE_NAME_MAX_LENGTH + 1))
    await wrapper
      .get('textarea#description')
      .setValue('D'.repeat(LETTER_TYPE_DESCRIPTION_MAX_LENGTH + 1))
    await wrapper
      .get('input#legacy_resource')
      .setValue('R'.repeat(LETTER_TYPE_RESOURCE_MAX_LENGTH + 1))
    await wrapper.get('form').trigger('submit.prevent')

    expect(wrapper.text()).toContain(`maksimal ${LETTER_TYPE_NAME_MAX_LENGTH} karakter`)
    expect(wrapper.text()).toContain(`maksimal ${LETTER_TYPE_DESCRIPTION_MAX_LENGTH} karakter`)
    expect(wrapper.text()).toContain(`maksimal ${LETTER_TYPE_RESOURCE_MAX_LENGTH} karakter`)
    expect(wrapper.emitted('submit')).toBeFalsy()
  })

  it('displays serverErrors and generalError and emits normalized payload on valid submit', async () => {
    const wrapper = mountModal({
      serverErrors: { name: 'Nama surat sudah digunakan.' },
      generalError: 'Anda tidak memiliki izin untuk mengakses atau mengubah data surat.',
    })

    expect(wrapper.text()).toContain('Nama surat sudah digunakan.')
    expect(wrapper.text()).toContain(
      'Anda tidak memiliki izin untuk mengakses atau mengubah data surat.',
    )

    await wrapper.get('input#name').setValue('  Surat Submit  ')
    await wrapper.get('textarea#description').setValue('  ')
    await wrapper.get('input#legacy_resource').setValue(' surat_submit.php ')
    await wrapper.get('form').trigger('submit.prevent')

    expect(wrapper.emitted('submit')[0][0]).toEqual({
      name: 'Surat Submit',
      description: null,
      legacy_resource: 'surat_submit.php',
    })
  })

  it('prevents double-submit and closing while saving is true', async () => {
    const wrapper = mountModal({ saving: true })

    await wrapper.get('input#name').setValue('Surat Simpan')
    await wrapper.get('form').trigger('submit.prevent')
    await wrapper.get('button[aria-label="Tutup"]').trigger('click')

    expect(wrapper.emitted('submit')).toBeFalsy()
    expect(wrapper.emitted('close')).toBeFalsy()
  })
})
