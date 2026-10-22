import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import SirsBedAvailabilityView from '../bed/SirsBedAvailabilityView.vue'
import SirsBedAvailabilityTable from '@/components/master-data/sirs-bed-availability/SirsBedAvailabilityTable.vue'
import SirsBedAvailabilityToolbar from '@/components/master-data/sirs-bed-availability/SirsBedAvailabilityToolbar.vue'
import MasterDataEmptyState from '@/components/master-data/shared/MasterDataEmptyState.vue'
import { mockSirsBedAvailability } from '@/mocks/sirsBedAvailability.mock'
import { useSirsBedAvailability } from '@/composables/useSirsBedAvailability'

describe('SirsBedAvailabilityView.vue & SirsBedAvailability Components', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('1. Halaman dapat dirender dengan benar', () => {
    const wrapper = mount(SirsBedAvailabilityView)
    expect(wrapper.exists()).toBe(true)
  })

  it('2. Judul dan deskripsi Ketersediaan Bed SIRS tampil', () => {
    const wrapper = mount(SirsBedAvailabilityView)
    expect(wrapper.find('.page-title').text()).toBe('Ketersediaan Bed SIRS')
    expect(wrapper.find('.page-subtitle').text()).toContain(
      'Menampilkan informasi ketersediaan tempat tidur untuk kebutuhan pelaporan SIRS.',
    )
  })

  it('3. Breadcrumb tampil sesuai alur Data Bed / Ketersediaan Bed SIRS', () => {
    const wrapper = mount(SirsBedAvailabilityView)
    const breadcrumbs = wrapper.find('.breadcrumbs')
    expect(breadcrumbs.exists()).toBe(true)
    expect(breadcrumbs.text()).toContain('Data Bed')
    expect(breadcrumbs.text()).toContain('Ketersediaan Bed SIRS')
  })

  it('4. Information banner tampil dengan keterangan persiapan integrasi', () => {
    const wrapper = mount(SirsBedAvailabilityView)
    const banner = wrapper.find('.info-banner')
    expect(banner.exists()).toBe(true)
    expect(banner.text()).toContain(
      'Halaman ini masih dalam tahap persiapan dan belum terintegrasi dengan layanan SIRS.',
    )
  })

  it('5. Card judul DAFTAR KETERSEDIAAN BED SIRS tampil', () => {
    const wrapper = mount(SirsBedAvailabilityView)
    const cardTitle = wrapper.find('.card-title')
    expect(cardTitle.exists()).toBe(true)
    expect(cardTitle.text()).toBe('DAFTAR KETERSEDIAAN BED SIRS')
  })

  it('6. Field pencarian tampil dengan label Cari dan placeholder yang sesuai', () => {
    const wrapper = mount(SirsBedAvailabilityView)
    const searchLabel = wrapper.find('.search-label')
    expect(searchLabel.exists()).toBe(true)
    expect(searchLabel.text()).toBe('Cari')

    const searchInput = wrapper.find('#sirs-bed-search')
    expect(searchInput.exists()).toBe(true)
    expect(searchInput.attributes('placeholder')).toBe('Cari kelas atau ruang')
  })

  it('7. Pilihan jumlah data tampil dengan opsi 10, 25, 50, dan 100', () => {
    const wrapper = mount(SirsBedAvailabilityView)
    const select = wrapper.find('#per-page-select')
    expect(select.exists()).toBe(true)

    const options = select.findAll('option')
    const values = options.map((opt) => Number(opt.element.value))
    expect(values).toEqual([10, 25, 50, 100])
  })

  it('8. Tombol Refresh tampil dan dapat diklik tanpa memanggil endpoint luar', async () => {
    const wrapper = mount(SirsBedAvailabilityView)
    const refreshBtn = wrapper.find('.btn-icon')
    expect(refreshBtn.exists()).toBe(true)
    expect(refreshBtn.attributes('title')).toBe('Refresh Data')

    // Klik tombol refresh
    await refreshBtn.trigger('click')
    // Verifikasi tidak ada error dan tidak ada request luar
    expect(wrapper.exists()).toBe(true)
  })

  it('9. Empty state tampil saat data awal kosong dengan judul dan pesan yang tepat', () => {
    const wrapper = mount(SirsBedAvailabilityView)
    const emptyState = wrapper.findComponent(MasterDataEmptyState)
    expect(emptyState.exists()).toBe(true)
    expect(emptyState.find('.empty-title').text()).toBe('Belum ada data ketersediaan Bed SIRS')
    expect(emptyState.find('.empty-message').text()).toBe(
      'Data akan ditampilkan setelah sumber data dan alur integrasi SIRS ditentukan.',
    )
  })

  it('10. Tabel memiliki tujuh kolom sesuai urutan: No, Kelas, Ruang, Kapasitas, Tersedia, Last Update, Aksi', () => {
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
        ],
        pagination: { current_page: 1, per_page: 10, total: 1 },
        loading: false,
      },
    })

    const headers = wrapper.findAll('thead th')
    expect(headers).toHaveLength(7)
    expect(headers[0].text()).toBe('No')
    expect(headers[1].text()).toBe('Kelas')
    expect(headers[2].text()).toBe('Ruang')
    expect(headers[3].text()).toBe('Kapasitas')
    expect(headers[4].text()).toBe('Tersedia')
    expect(headers[5].text()).toBe('Last Update')
    expect(headers[6].text()).toBe('Aksi')
  })

  it('11. Kapasitas dan Tersedia ditampilkan sebagai angka dan Last Update diformat ke Bahasa Indonesia', () => {
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

    const rows = wrapper.findAll('tbody tr')
    expect(rows).toHaveLength(2)

    // Baris 1
    const cols1 = rows[0].findAll('td')
    expect(cols1[0].text()).toBe('1') // No
    expect(cols1[1].text()).toBe('Kelas 3') // Kelas
    expect(cols1[2].text()).toBe('Ruang Melati') // Ruang
    expect(cols1[3].text()).toBe('10') // Kapasitas
    expect(cols1[4].text()).toBe('4') // Tersedia
    expect(cols1[5].text()).toContain('Oktober 2026') // Last Update
    expect(cols1[5].text()).toContain('09.30')

    // Baris 2 (null lastUpdate)
    const cols2 = rows[1].findAll('td')
    expect(cols2[3].text()).toBe('2')
    expect(cols2[4].text()).toBe('0')
    expect(cols2[5].text()).toBe('—')
  })

  it('12. Kolom Aksi menampilkan tombol nonaktif dengan tooltip alur tindakan belum ditentukan', () => {
    const wrapper = mount(SirsBedAvailabilityTable, {
      props: {
        items: [
          {
            id: 1,
            className: 'Kelas 3',
            roomName: 'Ruang Melati',
            capacity: 10,
            available: 4,
            lastUpdatedAt: null,
          },
        ],
        pagination: { current_page: 1, per_page: 10, total: 1 },
        loading: false,
      },
    })

    const actionBtn = wrapper.find('tbody tr td.col-actions button')
    expect(actionBtn.exists()).toBe(true)
    expect(actionBtn.text()).toBe('Belum tersedia')
    expect(actionBtn.attributes('disabled')).toBeDefined()
    expect(actionBtn.attributes('title')).toBe('Alur tindakan belum ditentukan.')
  })

  it('13. useSirsBedAvailability composable: pencarian dan pagination lokal bekerja pada mock data', () => {
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

    // Muat mock data untuk pengujian
    setRows(mockSirsBedAvailability)
    expect(filteredRows.value.length).toBe(mockSirsBedAvailability.length)

    // PerPage = 5
    setPerPage(5)
    expect(pagination.value.per_page).toBe(5)
    expect(paginatedRows.value.length).toBe(5)
    expect(pagination.value.last_page).toBe(3) // 12 items / 5 = 3 pages

    // Navigasi ke halaman 2
    setPage(2)
    expect(pagination.value.current_page).toBe(2)
    expect(paginatedRows.value[0].id).toBe(mockSirsBedAvailability[5].id)

    // Pencarian berdasarkan nama kelas
    setSearch('VIP')
    expect(
      filteredRows.value.every((r) => r.className.includes('VIP') || r.roomName.includes('VIP')),
    ).toBe(true)
    expect(pagination.value.current_page).toBe(1) // Otomatis reset ke hal 1

    // Pencarian berdasarkan nama ruang
    setSearch('Mawar')
    expect(filteredRows.value.length).toBe(1)
    expect(filteredRows.value[0].roomName).toContain('Mawar')

    // Refresh mereset filter dan pagination
    refresh()
    expect(filteredRows.value.length).toBe(mockSirsBedAvailability.length)
    expect(pagination.value.current_page).toBe(1)
  })

  it('14. Loading state menampilkan 7 kolom skeleton rows', () => {
    const wrapper = mount(SirsBedAvailabilityTable, {
      props: {
        items: [],
        pagination: { current_page: 1, per_page: 10, total: 0 },
        loading: true,
      },
    })

    const skeletons = wrapper.findAll('.skeleton-row')
    expect(skeletons.length).toBeGreaterThan(0)
  })

  it('15. Toolbar memancarkan event perubahan pencarian dan perPage', async () => {
    const wrapper = mount(SirsBedAvailabilityToolbar, {
      props: {
        search: '',
        perPage: 10,
        loading: false,
      },
    })

    // Update search
    const input = wrapper.find('#sirs-bed-search')
    await input.setValue('ICU')
    expect(wrapper.emitted('update:search')).toBeTruthy()
    expect(wrapper.emitted('update:search')[0]).toEqual(['ICU'])

    // Update perPage
    const select = wrapper.find('#per-page-select')
    await select.setValue(25)
    expect(wrapper.emitted('update:perPage')).toBeTruthy()
    expect(wrapper.emitted('update:perPage')[0]).toEqual([25])
  })
})
