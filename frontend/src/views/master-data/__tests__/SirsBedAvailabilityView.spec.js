import { beforeEach, describe, expect, it, vi } from 'vitest'
import { flushPromises, mount } from '@vue/test-utils'
import SirsBedAvailabilityView from '../bed/SirsBedAvailabilityView.vue'
import SirsBedAvailabilityTable from '@/components/master-data/sirs-bed-availability/SirsBedAvailabilityTable.vue'
import SirsBedAvailabilityToolbar from '@/components/master-data/sirs-bed-availability/SirsBedAvailabilityToolbar.vue'
import { useSirsBedAvailability } from '@/composables/useSirsBedAvailability'
import { mockSirsBedAvailability } from '@/mocks/sirsBedAvailability.mock'

describe('SirsBedAvailabilityView & SirsBedAvailability Components', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('menampilkan header, breadcrumbs, banner status integrasi, dan judul daftar SIRS', () => {
    const wrapper = mount(SirsBedAvailabilityView)

    expect(wrapper.get('h1').text()).toBe('Ketersediaan Bed SIRS')
    expect(wrapper.text()).toContain(
      'Menampilkan informasi ketersediaan tempat tidur untuk kebutuhan pelaporan SIRS.',
    )

    const breadcrumbs = wrapper.get('nav[aria-label="Breadcrumb"]').findAll('li')
    expect(breadcrumbs.map((li) => li.text().replace('/', '').trim())).toEqual([
      'Dashboard',
      'Master Data',
      'Data Bed',
      'Ketersediaan Bed SIRS',
    ])

    const statusBanner = wrapper.get('[role="status"]')
    expect(statusBanner.text()).toContain(
      'Halaman ini masih dalam tahap persiapan dan belum terintegrasi dengan layanan SIRS.',
    )

    expect(wrapper.get('h2').text().toLowerCase()).toBe('daftar ketersediaan bed sirs')
  })

  it('menampilkan kontrol jumlah entri (10, 25, 50, 100), pencarian, dan tombol Bersihkan Pencarian yang mereset filter lokal', async () => {
    const wrapper = mount(SirsBedAvailabilityView)

    const perPageSelect = wrapper.get('select[aria-label="Pilihan jumlah data per halaman"]')
    const optionValues = perPageSelect.findAll('option').map((opt) => Number(opt.element.value))
    expect(optionValues).toEqual([10, 25, 50, 100])

    const searchInput = wrapper.get('input[aria-label="Cari kelas atau ruang"]')
    expect(searchInput.attributes('placeholder')).toBe('Cari kelas atau ruang')
    await searchInput.setValue('Melati')
    await flushPromises()
    expect(wrapper.text()).toContain('Data ketersediaan tidak ditemukan')

    const resetBtn = wrapper.get('button[aria-label="Bersihkan pencarian"]')
    expect(resetBtn.text()).toContain('Bersihkan Pencarian')
    expect(resetBtn.attributes('title')).toBe('Bersihkan filter pencarian dan kembali ke halaman 1')
    await resetBtn.trigger('click')
    await flushPromises()

    expect(searchInput.element.value).toBe('')
    expect(wrapper.text()).toContain('Belum ada data ketersediaan Bed SIRS')
  })

  it('menampilkan empty state prototipe saat data awal kosong serta empty state pencarian', async () => {
    const wrapper = mount(SirsBedAvailabilityView)

    expect(wrapper.text()).toContain('Belum ada data ketersediaan Bed SIRS')
    expect(wrapper.text()).toContain(
      'Data akan ditampilkan setelah sumber data dan alur integrasi SIRS ditentukan.',
    )

    await wrapper.get('input[aria-label="Cari kelas atau ruang"]').setValue('VIP')
    await flushPromises()
    expect(wrapper.text()).toContain('Data ketersediaan tidak ditemukan')
    expect(wrapper.text()).toContain('Tidak ada data kelas atau ruang yang cocok dengan “VIP”.')
  })

  it('tabel menampilkan tujuh kolom standar, format angka/tanggal Indonesia, dan tombol aksi nonaktif', () => {
    const wrapper = mount(SirsBedAvailabilityTable, {
      props: {
        items: [
          {
            id: 1,
            className: 'Kelas 3',
            roomName: 'Ruang Melati',
            capacity: 10,
            available: 4,
            lastUpdatedAt: '2026-10-22T09:30:00',
          },
          {
            id: 2,
            className: 'VIP',
            roomName: 'Ruang Anggrek',
            capacity: 2,
            available: 0,
            lastUpdatedAt: null,
          },
        ],
        pagination: { current_page: 1, per_page: 10, total: 2 },
        loading: false,
      },
    })

    const headers = wrapper.findAll('thead th').map((th) => th.text())
    expect(headers).toEqual(['No', 'Kelas', 'Ruang', 'Kapasitas', 'Tersedia', 'Last Update', 'Aksi'])

    const rows = wrapper.findAll('tbody tr')
    expect(rows).toHaveLength(2)

    const cols1 = rows[0].findAll('td').map((td) => td.text())
    expect(cols1.slice(0, 5)).toEqual(['1', 'Kelas 3', 'Ruang Melati', '10', '4'])
    expect(cols1[5]).toContain('22 Oktober 2026')
    expect(cols1[5]).toContain('09.30')

    const cols2 = rows[1].findAll('td').map((td) => td.text())
    expect(cols2.slice(0, 6)).toEqual(['2', 'VIP', 'Ruang Anggrek', '2', '0', '—'])

    const actionButtons = rows[0].findAll('button[aria-label="Alur tindakan belum ditentukan"]')
    expect(actionButtons).toHaveLength(1)
    expect(actionButtons[0].text()).toBe('Belum tersedia')
    expect(actionButtons[0].attributes('disabled')).toBeDefined()
    expect(actionButtons[0].attributes('title')).toBe('Alur tindakan belum ditentukan.')
  })

  it('tabel menampilkan loading state dengan skeleton 7 kolom saat loading aktif', () => {
    const wrapper = mount(SirsBedAvailabilityTable, {
      props: {
        items: [],
        pagination: { current_page: 1, per_page: 10, total: 0 },
        loading: true,
      },
    })

    const tbody = wrapper.get('tbody')
    expect(tbody.attributes('aria-busy')).toBe('true')
    const rows = tbody.findAll('tr')
    expect(rows.length).toBeGreaterThan(0)
    expect(rows[0].findAll('td')).toHaveLength(7)
  })

  it('toolbar memancarkan event update:search, update:perPage, dan refresh', async () => {
    const wrapper = mount(SirsBedAvailabilityToolbar, {
      props: {
        search: '',
        perPage: 10,
        loading: false,
      },
    })

    await wrapper.get('input[aria-label="Cari kelas atau ruang"]').setValue('ICU')
    expect(wrapper.emitted('update:search')).toEqual([['ICU']])

    await wrapper.get('select[aria-label="Pilihan jumlah data per halaman"]').setValue(25)
    expect(wrapper.emitted('update:perPage')).toEqual([[25]])

    await wrapper.get('button[aria-label="Bersihkan pencarian"]').trigger('click')
    expect(wrapper.emitted('refresh')).toHaveLength(1)
  })

  it('useSirsBedAvailability mengelola pencarian dan pagination lokal pada mock data tanpa API', () => {
    const {
      setRows,
      filteredRows,
      paginatedRows,
      pagination,
      setSearch,
      setPage,
      setPerPage,
      refresh,
    } = useSirsBedAvailability()

    expect(paginatedRows.value).toEqual([])

    setRows(mockSirsBedAvailability)
    expect(filteredRows.value.length).toBe(mockSirsBedAvailability.length)

    setPerPage(5)
    expect(pagination.value.per_page).toBe(5)
    expect(paginatedRows.value.length).toBe(5)
    expect(pagination.value.last_page).toBe(3)

    setPage(2)
    expect(pagination.value.current_page).toBe(2)
    expect(paginatedRows.value[0].id).toBe(mockSirsBedAvailability[5].id)

    setSearch('VIP')
    expect(
      filteredRows.value.every((r) => r.className.includes('VIP') || r.roomName.includes('VIP')),
    ).toBe(true)
    expect(pagination.value.current_page).toBe(1)

    setSearch('Mawar')
    expect(filteredRows.value.length).toBe(1)
    expect(filteredRows.value[0].roomName).toContain('Mawar')

    refresh()
    expect(filteredRows.value.length).toBe(mockSirsBedAvailability.length)
    expect(pagination.value.current_page).toBe(1)
  })
})
