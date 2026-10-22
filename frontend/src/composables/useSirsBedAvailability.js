import { ref, computed } from 'vue'

/**
 * Composable untuk mengelola state tampilan halaman Ketersediaan Bed SIRS.
 *
 * CATATAN INTEGRASI:
 * - Halaman ini adalah prototipe frontend sementara.
 * - Belum terhubung dengan API resmi SIRS Online / Kementerian Kesehatan.
 * - Tidak menghitung transaksi pasien secara lokal dan tidak memanggil API BPJS/Aplicare.
 * - State awal secara default kosong (empty array) agar menampilkan empty state.
 *
 * @typedef {Object} SirsBedAvailability
 * @property {number|string} id
 * @property {string} className
 * @property {string} roomName
 * @property {number} capacity
 * @property {number} available
 * @property {string|null} lastUpdatedAt
 */
export function useSirsBedAvailability(initialRows = []) {
  // Default data kosong (array kosong) sesuai ketentuan prototipe
  const rows = ref([...initialRows])
  const loading = ref(false)
  const error = ref(null)

  const filters = ref({
    search: '',
  })

  const perPage = ref(10)
  const currentPage = ref(1)

  // Filter pencarian berdasarkan nama kelas atau nama ruang (frontend-only)
  const filteredRows = computed(() => {
    const q = (filters.value.search || '').trim().toLowerCase()
    if (!q) {
      return rows.value
    }
    return rows.value.filter((item) => {
      const className = String(item.className || '').toLowerCase()
      const roomName = String(item.roomName || '').toLowerCase()
      return className.includes(q) || roomName.includes(q)
    })
  })

  const total = computed(() => filteredRows.value.length)

  const lastPage = computed(() => {
    if (total.value === 0) return 1
    return Math.max(1, Math.ceil(total.value / perPage.value))
  })

  // Slicing data untuk pagination lokal
  const paginatedRows = computed(() => {
    const start = (currentPage.value - 1) * perPage.value
    const end = start + perPage.value
    return filteredRows.value.slice(start, end)
  })

  const pagination = computed(() => {
    const start = total.value === 0 ? 0 : (currentPage.value - 1) * perPage.value + 1
    const end = total.value === 0 ? 0 : Math.min(currentPage.value * perPage.value, total.value)

    return {
      current_page: currentPage.value,
      per_page: perPage.value,
      total: total.value,
      last_page: lastPage.value,
      from: start,
      to: end,
    }
  })

  const setPage = (page) => {
    if (page < 1 || page > lastPage.value) return
    currentPage.value = page
  }

  const setPerPage = (val) => {
    perPage.value = Number(val) || 10
    currentPage.value = 1
  }

  const setSearch = (val) => {
    filters.value.search = val
    currentPage.value = 1
  }

  // Tombol Refresh: hanya mereset filter dan memuat ulang state lokal tanpa fake API
  const refresh = () => {
    filters.value.search = ''
    currentPage.value = 1
    error.value = null
  }

  const setRows = (newRows) => {
    rows.value = Array.isArray(newRows) ? [...newRows] : []
    currentPage.value = 1
  }

  return {
    rows,
    filteredRows,
    paginatedRows,
    loading,
    error,
    filters,
    perPage,
    currentPage,
    pagination,
    total,
    lastPage,
    setPage,
    setPerPage,
    setSearch,
    refresh,
    setRows,
  }
}
