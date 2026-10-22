import { flushPromises, mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import AddRoomClassDialog from '@/components/master-data/rooms/AddRoomClassDialog.vue'

describe('AddRoomClassDialog.vue', () => {
  const mountDialog = (props = {}) =>
    mount(AddRoomClassDialog, {
      props: {
        isOpen: true,
        initialName: 'Kelas VVIP',
        isSubmitting: false,
        errorMessage: '',
        ...props,
      },
      global: {
        stubs: {
          Teleport: true,
        },
      },
    })

  it('renders confirmation title, info text, and pre-filled name input', async () => {
    const wrapper = mountDialog({ initialName: 'Kelas Eksekutif' })
    await flushPromises()

    expect(wrapper.text()).toContain('Tambah Kelas Baru')
    expect(wrapper.text()).toContain(
      'Data kelas “Kelas Eksekutif” belum tersedia. Tambahkan sebagai data kelas baru?',
    )
    const input = wrapper.find('input#room_class_new_name')
    expect(input.exists()).toBe(true)
    expect(input.element.value).toBe('Kelas Eksekutif')
  })

  it('emits close event when Batal or close button is clicked', async () => {
    const wrapper = mountDialog()
    await flushPromises()

    const cancelBtn = wrapper.find('.btn-cancel')
    await cancelBtn.trigger('click')
    expect(wrapper.emitted('close')).toBeTruthy()

    const closeBtn = wrapper.find('.dialog-close-btn')
    await closeBtn.trigger('click')
    expect(wrapper.emitted('close').length).toBe(2)
  })

  it('validates empty name and displays error message without submitting', async () => {
    const wrapper = mountDialog()
    await flushPromises()

    const input = wrapper.find('input#room_class_new_name')
    await input.setValue('   ')

    const submitBtn = wrapper.find('.btn-primary')
    await submitBtn.trigger('click')
    await flushPromises()

    expect(wrapper.emitted('submit')).toBeFalsy()
    expect(wrapper.text()).toContain('Nama kelas wajib diisi.')
  })

  it('normalizes spaces and emits submit with normalized name', async () => {
    const wrapper = mountDialog({ initialName: '  Kelas   Super   VIP  ' })
    await flushPromises()

    const submitBtn = wrapper.find('.btn-primary')
    await submitBtn.trigger('click')
    await flushPromises()

    expect(wrapper.emitted('submit')).toBeTruthy()
    expect(wrapper.emitted('submit')[0][0]).toBe('Kelas Super VIP')
  })

  it('displays backend error message when errorMessage prop is provided', async () => {
    const wrapper = mountDialog({
      errorMessage: 'Nama kelas sudah digunakan.',
    })
    await flushPromises()

    expect(wrapper.text()).toContain('Nama kelas sudah digunakan.')
    expect(wrapper.find('input#room_class_new_name').classes()).toContain('is-invalid')
  })

  it('disables buttons and shows loading indicator when isSubmitting is true', async () => {
    const wrapper = mountDialog({
      isSubmitting: true,
    })
    await flushPromises()

    const submitBtn = wrapper.find('.btn-primary')
    expect(submitBtn.attributes('disabled')).toBeDefined()
    expect(submitBtn.text()).toContain('Menyimpan...')

    const cancelBtn = wrapper.find('.btn-cancel')
    expect(cancelBtn.attributes('disabled')).toBeDefined()
  })
})
