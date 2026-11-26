import { mount } from '@vue/test-utils'
import { afterEach, describe, expect, it } from 'vitest'
import { h } from 'vue'
import BaseButton from '../BaseButton.vue'
import BaseField from '../BaseField.vue'
import BaseInput from '../BaseInput.vue'
import BaseModal from '../BaseModal.vue'

afterEach(() => {
  document.body.innerHTML = ''
})

describe('BaseButton', () => {
  it('default type="button" agar tidak men-submit form secara tidak sengaja', () => {
    const wrapper = mount(BaseButton, { slots: { default: 'Simpan' } })
    expect(wrapper.attributes('type')).toBe('button')
  })

  it('terkunci dan aria-busy saat loading', () => {
    const wrapper = mount(BaseButton, { props: { loading: true }, slots: { default: 'Simpan' } })
    expect(wrapper.attributes('disabled')).toBeDefined()
    expect(wrapper.attributes('aria-busy')).toBe('true')
  })
})

describe('BaseField', () => {
  const mountField = (props) =>
    mount(BaseField, {
      props: { label: 'Nama Bed', ...props },
      slots: { default: (field) => h(BaseInput, field) },
    })

  it('menghubungkan label ke input', () => {
    const wrapper = mountField({ id: 'bed-name' })
    expect(wrapper.find('label').attributes('for')).toBe('bed-name')
    expect(wrapper.find('input').attributes('id')).toBe('bed-name')
  })

  it('menandai input invalid dan menautkan pesan error untuk pembaca layar', () => {
    const wrapper = mountField({ id: 'bed-name', error: 'Nama bed wajib diisi.' })
    const input = wrapper.find('input')

    expect(input.attributes('aria-invalid')).toBe('true')
    expect(input.attributes('aria-describedby')).toBe('bed-name-message')
    expect(wrapper.find('#bed-name-message').text()).toBe('Nama bed wajib diisi.')
  })
})

describe('BaseModal', () => {
  const mountModal = (props) =>
    mount(BaseModal, {
      props: { open: true, title: 'Tambah Bed', ...props },
      global: { stubs: { teleport: true } },
      attachTo: document.body,
    })

  it('dialog berlabel judulnya', () => {
    const wrapper = mountModal()
    const dialog = wrapper.find('[role="dialog"]')
    expect(wrapper.find(`#${dialog.attributes('aria-labelledby')}`).text()).toBe('Tambah Bed')
  })

  it('menutup dengan tombol Escape', async () => {
    const wrapper = mountModal()
    document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape' }))
    expect(wrapper.emitted('close')).toHaveLength(1)
  })

  it('persistent: Escape dan tombol tutup tidak menutup modal', async () => {
    const wrapper = mountModal({ persistent: true })
    document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape' }))
    await wrapper.find('button[aria-label="Tutup"]').trigger('click')
    expect(wrapper.emitted('close')).toBeUndefined()
  })
})
