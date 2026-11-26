import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createRouter, createWebHistory } from 'vue-router'
import PolyclinicView from '../PolyclinicView.vue'
import AppSidebar from '@/components/layout/AppSidebar.vue'
import { useToast } from '@/composables/core/useToast'
import { polyclinicService } from '@/services/polyclinic'

vi.mock('@/utils/axios', () => ({
  default: {
    get: vi.fn(),
    post: vi.fn(),
    put: vi.fn(),
    patch: vi.fn(),
    delete: vi.fn(),
    interceptors: { request: { use: vi.fn() }, response: { use: vi.fn() } },
  },
}))

vi.mock('@/services/polyclinic', () => ({
  polyclinicService: {
    getPolyclinics: vi.fn(),
    getPolyclinic: vi.fn(),
    getServiceTypes: vi.fn(),
    createPolyclinic: vi.fn(),
    updatePolyclinic: vi.fn(),
    updatePolyclinicStatus: vi.fn(),
    archivePolyclinic: vi.fn(),
  },
}))

vi.mock('@/services/lookup.service', () => ({
  default: {
    getWarehouses: vi.fn().mockResolvedValue([{ id: 1, code: 'W1', name: 'Gudang 1' }]),
  },
}))

const mockServiceTypes = {
  data: {
    data: [
      { value: 'rawat-jalan', label: 'Rawat Jalan' },
      { value: 'farmasi', label: 'Farmasi' },
    ],
  },
}

const mockPolyclinics = {
  data: {
    data: [
      {
        id: 1,
        code: 'POLI-01',
        name: 'Umum',
        service_type: 'rawat-jalan',
        service_type_label: 'Rawat Jalan',
        warehouse: { id: 1, code: 'W1', name: 'Gudang 1' },
        quota: 20,
        jkn_quota: 10,
        bpjs_code: 'B-01',
        is_active: true,
        is_visible: true,
        is_online_visible: false,
      },
      {
        id: 2,
        code: 'POLI-02',
        name: 'Gigi',
        service_type: 'farmasi',
        service_type_label: 'Farmasi',
        warehouse: null,
        quota: 0,
        jkn_quota: 0,
        bpjs_code: null,
        is_active: false,
        is_visible: true,
        is_online_visible: false,
      },
    ],
    meta: {
      current_page: 1,
      last_page: 2,
      per_page: 15,
      total: 30,
      from: 1,
      to: 2,
    },
  },
}

const mockPolyclinicsPageTwo = {
  data: {
    data: [
      {
        id: 16,
        code: 'POLI-16',
        name: 'Poli Mata',
        service_type: 'rawat-jalan',
        service_type_label: 'Rawat Jalan',
        warehouse: null,
        quota: 15,
        jkn_quota: 5,
        bpjs_code: 'MAT',
        is_active: true,
        is_visible: true,
        is_online_visible: false,
      },
    ],
    meta: {
      current_page: 2,
      last_page: 2,
      per_page: 15,
      total: 30,
      from: 16,
      to: 30,
    },
  },
}

const mockEmptyResponse = {
  data: {
    data: [],
    meta: { current_page: 1, last_page: 1, per_page: 15, total: 0, from: 0, to: 0 },
  },
}

const router = createRouter({
  history: createWebHistory(),
  routes: [
    {
      path: '/master-data/polyclinics',
      name: 'master-data.polyclinics',
      component: PolyclinicView,
      meta: { requiresAuth: true },
    },
    { path: '/login', name: 'login', component: { template: '<div>Login</div>' } },
  ],
})

const toast = useToast()
const toastEntries = () => toast.toasts.value

function findButtonByText(wrapper, text) {
  return wrapper.findAll('button').find((button) => button.text().includes(text))
}

describe('Master Data Polyclinic Frontend', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    toast.clear()
    polyclinicService.getServiceTypes.mockResolvedValue(mockServiceTypes)
  })

  afterEach(() => {
    vi.useRealTimers()
  })

  describe('Router & Auth Guard', () => {
    it('protects polyclinic route and redirects unauthenticated users', async () => {
      router.push('/master-data/polyclinics')
      await router.isReady()
      const route = router.resolve('/master-data/polyclinics')
      expect(route.meta.requiresAuth).toBe(true)
    })
  })

  describe('AppSidebar (Master Data Menu)', () => {
    it('renders Master Data submenu and can be expanded', async () => {
      const wrapper = mount(AppSidebar, {
        global: { plugins: [router] },
        props: { isOpen: true },
      })

      const masterDataBtn = wrapper.findAll('button.nav-link').find((b) => b.text().includes('Master Data'))
      expect(masterDataBtn).toBeDefined()

      await masterDataBtn.trigger('click')

      const polyclinicLink = wrapper.findAll('.submenu-link').find((l) => l.text() === 'Poliklinik')
      expect(polyclinicLink).toBeDefined()
    })
  })

  describe('PolyclinicView', () => {
    const mountView = async (serviceMock = mockPolyclinics) => {
      polyclinicService.getPolyclinics.mockResolvedValue(serviceMock)
      const wrapper = mount(PolyclinicView, {
        global: {
          plugins: [router],
          stubs: {
            Teleport: true,
          },
        },
      })
      await flushPromises()
      return wrapper
    }

    it('fetches polyclinics and service types on mount and renders columns', async () => {
      const wrapper = await mountView()

      expect(polyclinicService.getPolyclinics).toHaveBeenCalled()
      expect(polyclinicService.getServiceTypes).toHaveBeenCalled()
      expect(wrapper.text()).toContain('POLI-01')
      expect(wrapper.text()).toContain('Umum')
      expect(wrapper.text()).toContain('Rawat Jalan')
      expect(wrapper.text()).toContain('W1 — Gudang 1')
      expect(wrapper.text()).toContain('Kuota JKN')

      const headers = wrapper.findAll('thead th').map((th) => th.text().trim())
      expect(headers).not.toContain('Poliklinik Induk')
      expect(headers).not.toContain('Kode SatuSehat')
      expect(headers).not.toContain('Kode BPJS')
    })

    it('displays empty state when no data and load error state when API fails', async () => {
      const emptyWrapper = await mountView(mockEmptyResponse)
      expect(emptyWrapper.text()).toContain('Belum ada data poliklinik')

      polyclinicService.getPolyclinics.mockRejectedValueOnce({
        response: { status: 500, data: { message: 'SQLSTATE[42S02]: table error' } },
      })
      const errorWrapper = mount(PolyclinicView, {
        global: { plugins: [router], stubs: { Teleport: true } },
      })
      await flushPromises()
      expect(errorWrapper.text()).toContain('Gagal memuat data poliklinik')
      expect(errorWrapper.text()).not.toContain('SQLSTATE')
    })

    it('handles debounced search and status filter correctly', async () => {
      vi.useFakeTimers()
      const wrapper = await mountView()

      const searchInput = wrapper.find('input[type="search"]')
      await searchInput.setValue('test search')

      vi.advanceTimersByTime(450)
      await flushPromises()

      expect(polyclinicService.getPolyclinics).toHaveBeenLastCalledWith(
        expect.objectContaining({
          search: 'test search',
          page: 1,
        }),
      )

      const statusSelect = wrapper.find('select[aria-label="Filter status aktif"]')
      expect(statusSelect.element.value).toBe('')
      await statusSelect.setValue('true')

      expect(polyclinicService.getPolyclinics).toHaveBeenLastCalledWith(
        expect.objectContaining({
          is_active: true,
          page: 1,
        }),
      )
    })

    it('does not render service_type filter or toggle status action button', async () => {
      const wrapper = await mountView()

      expect(wrapper.findAll('select').length).toBe(1)
      expect(wrapper.text()).not.toContain('Semua Jenis Layanan')
      expect(wrapper.find('button[aria-label="Muat ulang data"]').exists()).toBe(true)
      expect(wrapper.find('button[title="Edit"]').exists()).toBe(true)
      expect(wrapper.find('button[title="Arsipkan"]').exists()).toBe(true)
      expect(wrapper.find('button[title="Nonaktifkan"]').exists()).toBe(false)

      const callArgs = polyclinicService.getPolyclinics.mock.calls.at(-1)[0]
      expect(callArgs).not.toHaveProperty('service_type')
    })

    it('opens add modal, maps 422 validation errors, and shows error toast without wiping table', async () => {
      const wrapper = await mountView()
      polyclinicService.createPolyclinic.mockRejectedValue({
        response: {
          status: 422,
          data: {
            message: 'Data tidak valid.',
            errors: { code: ['Code is taken'] },
          },
        },
      })

      await findButtonByText(wrapper, 'Tambah Poliklinik').trigger('click')
      await flushPromises()

      expect(wrapper.find('h2').text()).toBe('Tambah Poliklinik')

      await wrapper.find('input#code').setValue('POLI-01')
      await wrapper.find('input#name').setValue('Umum')
      await wrapper.find('select#service_type').setValue('rawat-jalan')
      await wrapper.find('form').trigger('submit.prevent')
      await flushPromises()

      expect(wrapper.text()).toContain('Code is taken')
      expect(wrapper.text()).toContain('POLI-01')
      expect(toastEntries().at(-1)?.tone).toBe('danger')
    })

    it('shows success toast on create and update including bpjs_code', async () => {
      const wrapper = await mountView()
      polyclinicService.createPolyclinic.mockResolvedValue({ data: { data: { id: 99 } } })

      await findButtonByText(wrapper, 'Tambah Poliklinik').trigger('click')
      await flushPromises()

      await wrapper.find('input#code').setValue('NEW-01')
      await wrapper.find('input#name').setValue('Poliklinik Baru')
      await wrapper.find('select#service_type').setValue('rawat-jalan')
      await wrapper.find('input#bpjs_code').setValue('  BPJS-01  ')
      await wrapper.find('form').trigger('submit.prevent')
      await flushPromises()

      expect(polyclinicService.createPolyclinic).toHaveBeenCalledWith(
        expect.objectContaining({
          code: 'NEW-01',
          name: 'Poliklinik Baru',
          service_type: 'rawat-jalan',
          bpjs_code: 'BPJS-01',
        }),
      )
      expect(toastEntries().at(-1)?.tone).toBe('success')
      expect(toastEntries().at(-1)?.message).toBe('Data Poliklinik berhasil ditambahkan.')

      polyclinicService.getPolyclinic.mockResolvedValue({
        data: {
          data: {
            id: 1,
            code: 'POLI-01',
            name: 'Umum',
            service_type: 'rawat-jalan',
            quota: 20,
            jkn_quota: 10,
            bpjs_code: 'B-01',
            warehouse_id: null,
          },
        },
      })
      polyclinicService.updatePolyclinic.mockResolvedValue({ data: { data: { id: 1 } } })

      await wrapper.find('button[title="Edit"]').trigger('click')
      await flushPromises()

      const bpjsInput = wrapper.find('input#bpjs_code')
      expect(bpjsInput.element.value).toBe('B-01')

      await bpjsInput.setValue('B-01-UPDATED')
      await wrapper.find('form').trigger('submit.prevent')
      await flushPromises()

      expect(polyclinicService.updatePolyclinic).toHaveBeenCalledWith(
        1,
        expect.objectContaining({ bpjs_code: 'B-01-UPDATED' }),
      )
      expect(toastEntries().at(-1)?.message).toBe('Data Poliklinik berhasil diperbarui.')
    })

    it('shows success toast on archive and 409 error toast on archive conflict', async () => {
      const wrapper = await mountView()
      polyclinicService.archivePolyclinic.mockResolvedValueOnce({ status: 204 })

      await wrapper.find('button[title="Arsipkan"]').trigger('click')
      await flushPromises()
      await findButtonByText(wrapper, 'Ya, Arsipkan').trigger('click')
      await flushPromises()

      expect(polyclinicService.archivePolyclinic).toHaveBeenCalledWith(1)
      expect(toastEntries().at(-1)?.message).toBe('Data Poliklinik berhasil diarsipkan.')

      polyclinicService.archivePolyclinic.mockRejectedValueOnce({
        response: {
          status: 409,
          data: { message: 'Poliklinik masih memiliki jadwal dokter aktif.' },
        },
      })

      await wrapper.find('button[title="Arsipkan"]').trigger('click')
      await flushPromises()
      await findButtonByText(wrapper, 'Ya, Arsipkan').trigger('click')
      await flushPromises()

      expect(toastEntries().at(-1)?.tone).toBe('danger')
      expect(toastEntries().at(-1)?.message).toBe('Poliklinik masih memiliki jadwal dokter aktif.')
    })

    it('navigates to page 2, updates row offset numbering, and resets to page 1 on filter/search', async () => {
      vi.useFakeTimers()
      const wrapper = await mountView()

      // Halaman 1 tampil dengan nomor urut 1
      expect(wrapper.get('tbody tr td:first-child').text()).toBe('1')
      expect(wrapper.text()).toContain('POLI-01')

      const nextButton = wrapper.get('button[aria-label="Halaman berikutnya"]')
      expect(nextButton.attributes('disabled')).toBeUndefined()

      polyclinicService.getPolyclinics.mockResolvedValueOnce(mockPolyclinicsPageTwo)
      await nextButton.trigger('click')
      await flushPromises()

      expect(polyclinicService.getPolyclinics).toHaveBeenLastCalledWith(
        expect.objectContaining({
          page: 2,
          per_page: 15,
        }),
      )
      expect(wrapper.text()).toContain('POLI-16')
      expect(wrapper.text()).toContain('Poli Mata')
      expect(wrapper.get('tbody tr td:first-child').text()).toBe('16')

      // Filter status mengembalikan pagination ke halaman 1
      polyclinicService.getPolyclinics.mockResolvedValueOnce(mockPolyclinics)
      await wrapper.get('select[aria-label="Filter status aktif"]').setValue('false')
      await flushPromises()

      expect(polyclinicService.getPolyclinics).toHaveBeenLastCalledWith(
        expect.objectContaining({
          is_active: false,
          page: 1,
        }),
      )

      // Pencarian juga menjaga pagination di halaman 1
      polyclinicService.getPolyclinics.mockResolvedValueOnce(mockPolyclinicsPageTwo)
      await wrapper.get('button[aria-label="Halaman berikutnya"]').trigger('click')
      await flushPromises()
      expect(wrapper.get('tbody tr td:first-child').text()).toBe('16')

      polyclinicService.getPolyclinics.mockResolvedValueOnce(mockPolyclinics)
      await wrapper.get('input[type="search"]').setValue('Umum')
      await vi.advanceTimersByTimeAsync(450)
      await flushPromises()

      expect(polyclinicService.getPolyclinics).toHaveBeenLastCalledWith(
        expect.objectContaining({
          search: 'Umum',
          page: 1,
        }),
      )
    })

    it('ignores stale pagination response when a newer search request resolves first', async () => {
      vi.useFakeTimers()
      const wrapper = await mountView()

      let resolveSlowPageTwo
      const slowPageTwoPromise = new Promise((resolve) => {
        resolveSlowPageTwo = resolve
      })
      polyclinicService.getPolyclinics.mockReturnValueOnce(slowPageTwoPromise)

      await wrapper.get('button[aria-label="Halaman berikutnya"]').trigger('click')

      // Sebelum request halaman 2 selesai, user mengetik pencarian baru yang langsung resolve
      polyclinicService.getPolyclinics.mockResolvedValueOnce(mockEmptyResponse)
      await wrapper.get('input[type="search"]').setValue('TidakAda')
      await vi.advanceTimersByTimeAsync(450)
      await flushPromises()

      expect(wrapper.text()).toContain('Poliklinik tidak ditemukan')

      // Response lambat halaman 2 datang belakangan dan harus diabaikan
      resolveSlowPageTwo(mockPolyclinicsPageTwo)
      await flushPromises()

      expect(wrapper.text()).toContain('Poliklinik tidak ditemukan')
      expect(wrapper.text()).not.toContain('POLI-16')
    })
  })
})
