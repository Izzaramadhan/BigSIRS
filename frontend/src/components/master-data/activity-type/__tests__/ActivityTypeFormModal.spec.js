import { beforeEach, describe, expect, it, vi } from 'vitest'
import { flushPromises, mount } from '@vue/test-utils'
import ActivityTypeFormModal from '../ActivityTypeFormModal.vue'
import { ACTIVITY_TYPE_NAME_MAX_LENGTH } from '../activity-type.helpers'
import activityTypeService from '@/services/activityType.service'

vi.mock('@/services/activityType.service', () => {
  const mockService = {
    lookup: vi.fn(),
  }
  return {
    activityTypeService: mockService,
    default: mockService,
  }
})

describe('ActivityTypeFormModal', () => {
  const mountModal = (propsData = {}) =>
    mount(ActivityTypeFormModal, {
      props: {
        open: true,
        activityType: null,
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
    activityTypeService.lookup.mockResolvedValue({
      data: [{ id: 10, name: 'Pelayanan Rawat Jalan' }],
    })
  })

  it('renders add mode with maxlength 100 and loads parent lookup', async () => {
    const wrapper = mountModal()
    await flushPromises()

    expect(wrapper.find('h2').text()).toBe('Tambah Jenis Kegiatan')
    expect(wrapper.get('input#name').attributes('maxlength')).toBe(
      String(ACTIVITY_TYPE_NAME_MAX_LENGTH),
    )
    expect(activityTypeService.lookup).toHaveBeenCalledWith({})
    expect(wrapper.get('select#parent_id').text()).toContain('Pelayanan Rawat Jalan')
  })

  it('hydrates edit mode, excludes current id in lookup, and injects fallback parent if missing', async () => {
    activityTypeService.lookup.mockResolvedValueOnce({ data: [] })

    const wrapper = mountModal({
      open: false,
      activityType: {
        id: 1,
        name: 'Penyakit Dalam',
        parent_id: 10,
        parent: { id: 10, name: 'Pelayanan Rawat Jalan' },
      },
    })

    await wrapper.setProps({ open: true })
    await flushPromises()

    expect(wrapper.find('h2').text()).toBe('Edit Jenis Kegiatan')
    expect(activityTypeService.lookup).toHaveBeenCalledWith({ exclude_id: 1 })
    expect(wrapper.get('input#name').element.value).toBe('Penyakit Dalam')
    expect(wrapper.get('select#parent_id').element.value).toBe('10')
    expect(wrapper.get('select#parent_id').text()).toContain('Pelayanan Rawat Jalan')
  })

  it('validates empty name and max 100 chars client-side before submit', async () => {
    const wrapper = mountModal()
    await flushPromises()

    await wrapper.get('form').trigger('submit.prevent')
    expect(wrapper.text()).toContain('Nama jenis kegiatan wajib diisi.')
    expect(wrapper.emitted('submit')).toBeFalsy()

    await wrapper.get('input#name').setValue('X'.repeat(ACTIVITY_TYPE_NAME_MAX_LENGTH + 1))
    await wrapper.get('form').trigger('submit.prevent')
    expect(wrapper.text()).toContain(`maksimal ${ACTIVITY_TYPE_NAME_MAX_LENGTH} karakter`)
    expect(wrapper.emitted('submit')).toBeFalsy()
  })

  it('displays serverErrors, generalError, and warning alert if lookup fails without blocking submit', async () => {
    activityTypeService.lookup.mockRejectedValueOnce({
      response: { status: 500, data: { message: 'SQLSTATE error' } },
    })

    const wrapper = mountModal({
      serverErrors: { name: 'Nama jenis kegiatan sudah digunakan.', parent_id: 'Induk tidak valid.' },
      generalError: 'Gagal menyimpan data.',
    })
    await flushPromises()

    expect(wrapper.text()).toContain('Nama jenis kegiatan sudah digunakan.')
    expect(wrapper.text()).toContain('Induk tidak valid.')
    expect(wrapper.text()).toContain('Gagal menyimpan data.')

    // Non-blocking lookup warning visible when generalError cleared
    await wrapper.setProps({ generalError: '' })
    expect(wrapper.text()).toContain('Daftar induk jenis kegiatan gagal dimuat.')

    await wrapper.get('input#name').setValue('  Kesehatan Anak  ')
    await wrapper.get('form').trigger('submit.prevent')

    expect(wrapper.emitted('submit')[0][0]).toEqual({
      name: 'Kesehatan Anak',
      parent_id: null,
    })
  })

  it('prevents double-submit and close while saving is true', async () => {
    const wrapper = mountModal({ saving: true })
    await flushPromises()

    await wrapper.get('input#name').setValue('Radiologi')
    await wrapper.get('form').trigger('submit.prevent')
    await wrapper.get('button[aria-label="Tutup"]').trigger('click')

    expect(wrapper.emitted('submit')).toBeFalsy()
    expect(wrapper.emitted('close')).toBeFalsy()
  })
})
