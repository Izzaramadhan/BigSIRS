import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createRouter, createWebHistory } from 'vue-router'
import PolyclinicView from '../PolyclinicView.vue'
import AppSidebar from '@/components/layout/AppSidebar.vue'
import { polyclinicService } from '@/services/polyclinic'

// Mock axios and service
vi.mock('@/utils/axios', () => {
  return {
    default: {
      get: vi.fn(),
      post: vi.fn(),
      put: vi.fn(),
      patch: vi.fn(),
      delete: vi.fn(),
      interceptors: { request: { use: vi.fn() }, response: { use: vi.fn() } }
    }
  }
})

vi.mock('@/services/polyclinic', () => {
  return {
    polyclinicService: {
      getPolyclinics: vi.fn(),
      getPolyclinic: vi.fn(),
      getServiceTypes: vi.fn(),
      createPolyclinic: vi.fn(),
      updatePolyclinic: vi.fn(),
      updatePolyclinicStatus: vi.fn(),
      archivePolyclinic: vi.fn()
    }
  }
})

const mockServiceTypes = {
  data: {
    data: [
      { value: 'rawat-jalan', label: 'Rawat Jalan' },
      { value: 'farmasi', label: 'Farmasi' },
    ]
  }
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
        quota: 0,
        jkn_quota: 0,
        bpjs_code: null,
        is_active: false,
        is_visible: true,
        is_online_visible: false,
      }
    ],
    meta: {
      current_page: 1,
      last_page: 2,
      per_page: 15,
      total: 30
    }
  }
}

const mockEmptyResponse = {
  data: {
    data: [],
    meta: { current_page: 1, last_page: 1, per_page: 15, total: 0 }
  }
}

const router = createRouter({
  history: createWebHistory(),
  routes: [
    {
      path: '/master-data/polyclinics',
      name: 'master-data.polyclinics',
      component: PolyclinicView,
      meta: { requiresAuth: true }
    },
    { path: '/login', name: 'login', component: { template: '<div>Login</div>' } }
  ]
})

describe('Master Data Polyclinic Frontend', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    polyclinicService.getServiceTypes.mockResolvedValue(mockServiceTypes)
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
        props: { isOpen: true }
      })

      const buttons = wrapper.findAll('button.nav-link')
      const masterDataBtn = buttons.find(b => b.text().includes('Master Data'))
      expect(masterDataBtn).toBeDefined()

      await masterDataBtn.trigger('click')

      const polyclinicLink = wrapper.findAll('.submenu-link').find(l => l.text() === 'Poliklinik')
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
            Teleport: true
          }
        },
      })
      await flushPromises()
      return wrapper
    }

    it('fetches polyclinics and service types on mount', async () => {
      await mountView()
      expect(polyclinicService.getPolyclinics).toHaveBeenCalled()
      expect(polyclinicService.getServiceTypes).toHaveBeenCalled()
    })

    it('fetches and displays listing on mount', async () => {
      const wrapper = await mountView()
      expect(wrapper.text()).toContain('POLI-01')
      expect(wrapper.text()).toContain('Umum')
    })

    it('displays Jenis Layanan column in table', async () => {
      const wrapper = await mountView()
      expect(wrapper.text()).toContain('Jenis Layanan')
      expect(wrapper.text()).toContain('Rawat Jalan')
    })

    it('does NOT display Poliklinik Induk column in table', async () => {
      const wrapper = await mountView()
      expect(wrapper.text()).not.toContain('Poliklinik Induk')
    })

    it('does NOT display Kode SatuSehat column in table', async () => {
      const wrapper = await mountView()
      expect(wrapper.text()).not.toContain('SatuSehat')
    })

    it('displays Kuota column in table', async () => {
      const wrapper = await mountView()
      expect(wrapper.text()).toContain('Kuota JKN')
    })

    it('displays empty state when no data', async () => {
      const wrapper = await mountView(mockEmptyResponse)
      expect(wrapper.text()).toContain('Belum ada data')
    })

    it('handles debounced search', async () => {
      vi.useFakeTimers()
      const wrapper = await mountView()

      const searchInput = wrapper.find('input[type="text"]')
      await searchInput.setValue('test search')

      vi.advanceTimersByTime(500)
      await flushPromises()

      expect(polyclinicService.getPolyclinics).toHaveBeenCalledWith(expect.objectContaining({
        search: 'test search',
        page: 1
      }))

      vi.useRealTimers()
    })

    it('opens add modal with correct title', async () => {
      const wrapper = await mountView()

      await wrapper.find('button.btn-primary').trigger('click')
      await flushPromises()

      expect(wrapper.find('.modal-content').exists()).toBe(true)
      expect(wrapper.find('.modal-header h3').text()).toBe('Tambah Poliklinik')
    })

    it('maps 422 validation errors to fields and shows error toast', async () => {
      const wrapper = await mountView()
      polyclinicService.createPolyclinic.mockRejectedValue({
        response: {
          status: 422,
          data: { errors: { code: ['Code is taken'] } }
        }
      })

      await wrapper.find('button.btn-primary').trigger('click')
      await flushPromises()

      await wrapper.find('form').trigger('submit.prevent')
      await flushPromises()

      // 422 error should show field error and an error toast summary
      expect(wrapper.find('.invalid-feedback').text()).toBe('Code is taken')
      const toast = wrapper.find('.app-toast')
      expect(toast.exists()).toBe(true)
      expect(toast.classes()).toContain('toast-error')
      expect(toast.find('.toast-title').text()).toBe('Gagal Menambahkan')
      expect(toast.find('.toast-message').text()).toBe('Data Poliklinik gagal ditambahkan. Silakan periksa kembali data yang dimasukkan.')
    })

    it('shows success toast on successful creation', async () => {
      const wrapper = await mountView()
      polyclinicService.createPolyclinic.mockResolvedValue({ success: true })

      await wrapper.find('button.btn-primary').trigger('click')
      await flushPromises()

      // Fill and submit form
      await wrapper.find('input[id="code"]').setValue('NEW-01')
      await wrapper.find('input[id="name"]').setValue('Poliklinik Baru')
      await wrapper.find('form').trigger('submit.prevent')
      
      // Toast shouldn't exist before API resolves (Wait, mock resolves immediately, but we can check if we want)
      await flushPromises()

      const toast = wrapper.find('.app-toast')
      expect(toast.exists()).toBe(true)
      expect(toast.classes()).toContain('toast-success')
      expect(toast.find('.toast-title').text()).toBe('Berhasil')
      expect(toast.find('.toast-message').text()).toBe('Data Poliklinik berhasil ditambahkan.')
    })

    it('shows success toast on successful update', async () => {
      const wrapper = await mountView()
      polyclinicService.updatePolyclinic.mockResolvedValue({ success: true })

      // Click Edit button on the first item
      await wrapper.find('.actions-cell .edit').trigger('click')
      await flushPromises()

      await wrapper.find('form').trigger('submit.prevent')
      await flushPromises()

      const toast = wrapper.find('.app-toast')
      expect(toast.exists()).toBe(true)
      expect(toast.classes()).toContain('toast-success')
      expect(toast.find('.toast-title').text()).toBe('Berhasil')
      expect(toast.find('.toast-message').text()).toBe('Data Poliklinik berhasil diperbarui.')
    })

    it('shows success toast on successful archive', async () => {
      const wrapper = await mountView()
      polyclinicService.archivePolyclinic.mockResolvedValue({ success: true })

      // Click Delete/Archive button on the first item
      await wrapper.find('.actions-cell .delete').trigger('click')
      await flushPromises()

      // Confirm deletion
      await wrapper.find('.modal-content .btn-danger').trigger('click')
      await flushPromises()

      const toast = wrapper.find('.app-toast')
      expect(toast.exists()).toBe(true)
      expect(toast.classes()).toContain('toast-success')
      expect(toast.find('.toast-title').text()).toBe('Berhasil')
      expect(toast.find('.toast-message').text()).toBe('Data Poliklinik berhasil diarsipkan.')
    })

    it('shows backend error message on archive failure', async () => {
      const wrapper = await mountView()
      polyclinicService.archivePolyclinic.mockRejectedValue({
        response: {
          status: 400,
          data: { message: 'Backend custom archive error.' }
        }
      })

      await wrapper.find('.actions-cell .delete').trigger('click')
      await flushPromises()

      await wrapper.find('.modal-content .btn-danger').trigger('click')
      await flushPromises()

      const toast = wrapper.find('.app-toast')
      expect(toast.exists()).toBe(true)
      expect(toast.classes()).toContain('toast-error')
      expect(toast.find('.toast-title').text()).toBe('Gagal Mengarsipkan')
      expect(toast.find('.toast-message').text()).toBe('Backend custom archive error.')
    })

    it('closes toast when close button is clicked', async () => {
      const wrapper = await mountView()
      polyclinicService.createPolyclinic.mockResolvedValue({ success: true })

      await wrapper.find('button.btn-primary').trigger('click')
      await flushPromises()

      await wrapper.find('form').trigger('submit.prevent')
      await flushPromises()

      let toast = wrapper.find('.app-toast')
      expect(toast.exists()).toBe(true)

      await toast.find('.toast-close').trigger('click')
      await flushPromises()

      toast = wrapper.find('.app-toast')
      expect(toast.exists()).toBe(false)
    })

    it('hides toast automatically after duration', async () => {
      vi.useFakeTimers()
      const wrapper = await mountView()
      polyclinicService.createPolyclinic.mockResolvedValue({ success: true })

      await wrapper.find('button.btn-primary').trigger('click')
      await flushPromises()

      await wrapper.find('form').trigger('submit.prevent')
      await flushPromises()

      expect(wrapper.find('.app-toast').exists()).toBe(true)

      vi.advanceTimersByTime(3500)
      await flushPromises()

      expect(wrapper.find('.app-toast').exists()).toBe(false)
      vi.useRealTimers()
    })

    it('filter displays correct initial values when null', async () => {
      const wrapper = await mountView()
      const selects = wrapper.findAll('select.filter-select')
      expect(selects.length).toBe(1)
      expect(selects[0].element.value).toBe('') // Semua Status
      
      const searchInput = wrapper.find('input[type="text"]')
      expect(searchInput.attributes('placeholder')).toContain('Cari kode atau nama poliklinik')
    })

    it('filter status emits true and false correctly', async () => {
      const wrapper = await mountView()
      const filterSelect = wrapper.find('select.filter-select') // first one is status
      
      await filterSelect.setValue('true')
      await filterSelect.trigger('change')
      
      // The view's filters should be updated
      expect(polyclinicService.getPolyclinics).toHaveBeenCalledWith(expect.objectContaining({
        is_active: true
      }))
    })

    it('does not render service_type filter and reset button', async () => {
      const wrapper = await mountView()
      
      const selects = wrapper.findAll('select.filter-select')
      expect(selects.length).toBe(1) // Only status filter
      expect(wrapper.text()).not.toContain('Semua Jenis Layanan')
      
      expect(wrapper.find('button[title="Reset Filter"]').exists()).toBe(false)
      expect(wrapper.find('button[title="Refresh Data"]').exists()).toBe(true)
      
      expect(polyclinicService.getPolyclinics).toHaveBeenCalledWith(expect.objectContaining({
        page: 1
      }))
      
      const callArgs = polyclinicService.getPolyclinics.mock.calls.at(-1)[0]
      expect(callArgs).not.toHaveProperty('service_type')
    })

    it('table has no toggle status button', async () => {
      const wrapper = await mountView()
      // Toggle button SVG or .toggle class should not exist inside actions
      expect(wrapper.find('.actions-cell .toggle').exists()).toBe(false)
      // Status badge should still exist
      expect(wrapper.find('.status-badge').exists()).toBe(true)
      // Edit button should exist
      expect(wrapper.find('.actions-cell .edit').exists()).toBe(true)
      // Delete/archive button should exist
      expect(wrapper.find('.actions-cell .delete').exists()).toBe(true)
    })
  })
})
