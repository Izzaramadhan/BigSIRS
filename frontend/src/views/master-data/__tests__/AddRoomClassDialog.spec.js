import { flushPromises, mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import AddRoomClassDialog from '@/components/master-data/rooms/AddRoomClassDialog.vue'
import {
  ROOM_CLASS_CODE_MAX_LENGTH,
  ROOM_CLASS_NAME_MAX_LENGTH,
} from '@/components/master-data/room-classes/room-class.helpers'

describe('AddRoomClassDialog.vue', () => {
  const mountDialog = (props = {}) =>
    mount(AddRoomClassDialog, {
      props: {
        open: true,
        initialName: 'Kelas VVIP',
        saving: false,
        serverErrors: {},
        errorMessage: '',
        ...props,
      },
      global: {
        stubs: {
          Teleport: true,
        },
      },
    })

  const findButtonByText = (wrapper, text) =>
    wrapper.findAll('button').find((button) => button.text().trim() === text)

  const findControlByLabel = (wrapper, labelText) => {
    const label = wrapper
      .findAll('label')
      .find((node) => node.text().replace('*', '').trim() === labelText)
    const forId = label.attributes('for')
    return wrapper.get(`[id="${forId}"]`)
  }

  it('merender field Nama Kelas terisi awal, Kode Kelas kosong, dan Harga Kelas kosong tanpa auto-generate', async () => {
    const wrapper = mountDialog({ initialName: 'Kelas Eksekutif' })
    await flushPromises()

    expect(wrapper.text()).toContain('Tambah Kelas Baru')
    expect(wrapper.text()).toContain('Kelas Eksekutif')
    expect(findControlByLabel(wrapper, 'Nama Kelas').element.value).toBe('Kelas Eksekutif')
    expect(findControlByLabel(wrapper, 'Kode Kelas').element.value).toBe('')
    expect(findControlByLabel(wrapper, 'Harga Kelas').element.value).toBe('')
  })

  it('memancarkan event close saat tombol Batal atau Tutup diklik', async () => {
    const wrapper = mountDialog()
    await flushPromises()

    await findButtonByText(wrapper, 'Batal').trigger('click')
    expect(wrapper.emitted('close')).toHaveLength(1)

    await wrapper.get('button[aria-label="Tutup"]').trigger('click')
    expect(wrapper.emitted('close')).toHaveLength(2)
  })

  it('memvalidasi field wajib (Nama, Kode, Harga) dan batas panjang maksimum tanpa mengirim payload', async () => {
    const wrapper = mountDialog()
    await flushPromises()

    await findControlByLabel(wrapper, 'Nama Kelas').setValue('   ')
    await wrapper.get('form').trigger('submit')
    await flushPromises()

    expect(wrapper.emitted('submit')).toBeFalsy()
    expect(wrapper.text()).toContain('Nama kelas wajib diisi.')
    expect(wrapper.text()).toContain('Kode kelas wajib diisi.')
    expect(wrapper.text()).toContain('Harga kelas wajib diisi.')
    expect(findControlByLabel(wrapper, 'Nama Kelas').attributes('aria-invalid')).toBe('true')
    expect(findControlByLabel(wrapper, 'Kode Kelas').attributes('aria-invalid')).toBe('true')
    expect(findControlByLabel(wrapper, 'Harga Kelas').attributes('aria-invalid')).toBe('true')

    await findControlByLabel(wrapper, 'Nama Kelas').setValue('A'.repeat(ROOM_CLASS_NAME_MAX_LENGTH + 1))
    await findControlByLabel(wrapper, 'Kode Kelas').setValue('B'.repeat(ROOM_CLASS_CODE_MAX_LENGTH + 1))
    await findControlByLabel(wrapper, 'Harga Kelas').setValue('10000')
    await wrapper.get('form').trigger('submit')
    await flushPromises()

    expect(wrapper.emitted('submit')).toBeFalsy()
    expect(wrapper.text()).toContain(`Nama kelas maksimal ${ROOM_CLASS_NAME_MAX_LENGTH} karakter.`)
    expect(wrapper.text()).toContain(`Kode kelas maksimal ${ROOM_CLASS_CODE_MAX_LENGTH} karakter.`)
  })

  it('memformat tampilan Rupiah dan mengirim payload { name, code, price } termasuk harga 0', async () => {
    const wrapper = mountDialog({ initialName: '  Kelas   Super   VIP  ' })
    await flushPromises()

    await findControlByLabel(wrapper, 'Kode Kelas').setValue('  SVIP-01  ')
    const priceInput = findControlByLabel(wrapper, 'Harga Kelas')
    await priceInput.setValue('250000')
    expect(priceInput.element.value).toBe('250.000')

    await wrapper.get('form').trigger('submit')
    await flushPromises()

    expect(wrapper.emitted('submit')[0][0]).toEqual({
      name: 'Kelas Super VIP',
      code: 'SVIP-01',
      price: 250000,
    })

    // Verifikasi harga 0 diterima secara valid
    await priceInput.setValue('0')
    expect(priceInput.element.value).toBe('0')
    await wrapper.get('form').trigger('submit')
    await flushPromises()

    expect(wrapper.emitted('submit')[1][0]).toEqual({
      name: 'Kelas Super VIP',
      code: 'SVIP-01',
      price: 0,
    })
  })

  it('menampilkan error 422 per field melalui serverErrors dan error umum melalui errorMessage', async () => {
    const wrapper = mountDialog({
      serverErrors: {
        name: 'Nama kelas sudah digunakan.',
        code: 'Kode kelas sudah digunakan.',
      },
      errorMessage: 'Gagal menambahkan data kelas.',
    })
    await flushPromises()

    expect(wrapper.text()).toContain('Nama kelas sudah digunakan.')
    expect(wrapper.text()).toContain('Kode kelas sudah digunakan.')
    expect(wrapper.get('[role="alert"]').text()).toContain('Gagal menambahkan data kelas.')
    expect(findControlByLabel(wrapper, 'Nama Kelas').attributes('aria-invalid')).toBe('true')
    expect(findControlByLabel(wrapper, 'Kode Kelas').attributes('aria-invalid')).toBe('true')
  })

  it('mencegah double submit, menonaktifkan tombol, dan menjaga modal tetap terbuka saat saving true', async () => {
    const wrapper = mountDialog({
      saving: true,
    })
    await flushPromises()

    const submitBtn = findButtonByText(wrapper, 'Menyimpan…')
    expect(submitBtn.attributes('disabled')).toBeDefined()
    expect(submitBtn.attributes('aria-busy')).toBe('true')
    expect(findButtonByText(wrapper, 'Batal').attributes('disabled')).toBeDefined()

    await wrapper.get('form').trigger('submit')
    await flushPromises()
    expect(wrapper.emitted('submit')).toBeFalsy()
  })
})
