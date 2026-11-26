import { beforeEach, describe, expect, it, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import ServiceTypeFormModal from '../ServiceTypeFormModal.vue'
import { SERVICE_TYPE_NAME_MAX_LENGTH } from '../service-type.helpers'

describe('ServiceTypeFormModal', () => {
  const mountModal = (propsData = {}) =>
    mount(ServiceTypeFormModal, {
      props: {
        open: true,
        serviceType: null,
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

  it('renders Tambah title and maxlength hint when creating', () => {
    const wrapper = mountModal()

    expect(wrapper.find('h2').text()).toBe('Tambah Jenis Layanan')
    expect(wrapper.get('input#name').attributes('maxlength')).toBe(
      String(SERVICE_TYPE_NAME_MAX_LENGTH),
    )
    expect(wrapper.text()).toContain(`Maksimal ${SERVICE_TYPE_NAME_MAX_LENGTH} karakter.`)
  })

  it('hydrates existing serviceType value when opening in Edit mode', async () => {
    const wrapper = mountModal({
      open: false,
      serviceType: { id: 1, name: 'Penyakit Dalam' },
    })

    await wrapper.setProps({ open: true })

    expect(wrapper.find('h2').text()).toBe('Edit Jenis Layanan')
    expect(wrapper.get('input#name').element.value).toBe('Penyakit Dalam')
  })

  it('blocks submit with client validation message when name is empty or > 45 chars', async () => {
    const wrapper = mountModal()

    await wrapper.get('form').trigger('submit.prevent')
    expect(wrapper.text()).toContain('Nama jenis layanan wajib diisi.')
    expect(wrapper.emitted('submit')).toBeFalsy()

    await wrapper.get('input#name').setValue('X'.repeat(SERVICE_TYPE_NAME_MAX_LENGTH + 1))
    await wrapper.get('form').trigger('submit.prevent')
    expect(wrapper.text()).toContain(`Nama jenis layanan maksimal ${SERVICE_TYPE_NAME_MAX_LENGTH} karakter.`)
    expect(wrapper.emitted('submit')).toBeFalsy()
  })

  it('emits normalized payload on valid submit and supports server errors', async () => {
    const wrapper = mountModal({
      serverErrors: { name: 'Nama jenis layanan sudah digunakan.' },
      generalError: 'Terjadi bentrok data.',
    })

    expect(wrapper.text()).toContain('Nama jenis layanan sudah digunakan.')
    expect(wrapper.text()).toContain('Terjadi bentrok data.')

    await wrapper.get('input#name').setValue('  Kesehatan   Anak  ')
    await wrapper.get('form').trigger('submit.prevent')

    expect(wrapper.emitted('submit')).toEqual([[{ name: 'Kesehatan Anak' }]])
  })

  it('blocks submit and close while saving is true', async () => {
    const wrapper = mountModal({ saving: true })

    await wrapper.get('input#name').setValue('Rawat Jalan')
    await wrapper.get('form').trigger('submit.prevent')
    await wrapper.get('button[aria-label="Tutup"]').trigger('click')

    expect(wrapper.emitted('submit')).toBeFalsy()
    expect(wrapper.emitted('close')).toBeFalsy()
  })
})
