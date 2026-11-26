import { reactive, ref } from 'vue'
import { normalizeApiError } from '@/utils/http-error'

const isFilled = (value) => value !== '' && value !== null && value !== undefined

/**
 * State & aksi standar untuk halaman CRUD (daftar + tambah/ubah/hapus).
 * Menggantikan ±45 composable `useXxx.js` yang berisi boilerplate identik.
 *
 * Perbedaan penting dari versi lama:
 * - `loadError` HANYA untuk gagal memuat daftar. Gagal simpan/hapus dikembalikan
 *   sebagai hasil, bukan ditulis ke state halaman — sehingga tabel tidak lagi
 *   "hilang" berganti error state saat validasi form gagal.
 * - Respons pencarian yang datang terlambat diabaikan (race condition).
 * - Setelah mutasi berhasil, daftar dimuat ulang otomatis.
 *
 * @param {ReturnType<import('@/services/core/createResourceService').createResourceService>} service
 * @param {object} [options]
 * @param {string} [options.label]   nama data untuk pesan fallback, mis. 'bed'
 * @param {number} [options.perPage]
 * @param {{ column: string, direction: 'asc'|'desc' }} [options.sort]
 * @param {Record<string, unknown>} [options.filters] filter awal selain `search`
 */
export function useCrudResource(service, options = {}) {
  const {
    label = 'data',
    perPage = 10,
    sort = { column: 'created_at', direction: 'desc' },
    filters: initialFilters = {},
  } = options

  const items = ref([])
  const meta = reactive({ page: 1, perPage, total: 0, lastPage: 1, from: 0, to: 0 })
  const filters = reactive({ search: '', ...initialFilters })
  const loading = ref(false)
  const loadError = ref('')
  const saving = ref(false)

  let latestRequest = 0

  async function fetchItems() {
    const requestId = ++latestRequest
    loading.value = true
    loadError.value = ''

    const params = Object.fromEntries(
      Object.entries({
        page: meta.page,
        per_page: meta.perPage,
        sort: sort.column,
        direction: sort.direction,
        ...filters,
      }).filter(([, value]) => isFilled(value)),
    )

    try {
      const response = await service.list(params)
      if (requestId !== latestRequest) return

      items.value = response.data ?? []
      Object.assign(meta, {
        page: response.meta?.current_page ?? 1,
        perPage: response.meta?.per_page ?? perPage,
        total: response.meta?.total ?? items.value.length,
        lastPage: response.meta?.last_page ?? 1,
        from: response.meta?.from ?? 0,
        to: response.meta?.to ?? 0,
      })
    } catch (error) {
      if (requestId !== latestRequest) return
      loadError.value = normalizeApiError(error, `Data ${label} gagal dimuat.`).message
    } finally {
      if (requestId === latestRequest) loading.value = false
    }
  }

  function setPage(page) {
    if (page < 1 || page > meta.lastPage || page === meta.page) return
    meta.page = page
    return fetchItems()
  }

  /** Ubah filter dan kembali ke halaman 1. */
  function setFilters(patch) {
    Object.assign(filters, patch)
    meta.page = 1
    return fetchItems()
  }

  async function mutate(request, fallbackMessage) {
    saving.value = true
    try {
      const data = await request()
      await fetchItems()
      return { ok: true, data }
    } catch (error) {
      return { ok: false, error: normalizeApiError(error, fallbackMessage) }
    } finally {
      saving.value = false
    }
  }

  const create = (payload) =>
    mutate(() => service.create(payload), `Data ${label} gagal ditambahkan.`)

  const update = (id, payload) =>
    mutate(() => service.update(id, payload), `Data ${label} gagal diperbarui.`)

  const remove = (id) =>
    mutate(async () => {
      const result = await service.remove(id)
      // Menghapus item terakhir di sebuah halaman → mundur satu halaman.
      if (items.value.length === 1 && meta.page > 1) meta.page -= 1
      return result
    }, `Data ${label} gagal dihapus.`)

  return {
    items,
    meta,
    filters,
    loading,
    loadError,
    saving,
    fetchItems,
    setPage,
    setFilters,
    create,
    update,
    remove,
  }
}
