import { reactive, ref } from 'vue'
import { getSafePolyclinicErrorMessage } from '@/components/master-data/polyclinics/polyclinic.helpers'
import { polyclinicService } from '@/services/polyclinic'
import { normalizeApiError } from '@/utils/http-error'

const isFilled = (value) => value !== '' && value !== null && value !== undefined

/**
 * Composable modul Poliklinik: mengelola daftar berpaginasi, filter status aktif,
 * jenis layanan, guard race condition, serta mutasi CRUD tanpa menimpa error tabel.
 * Tetap mempertahankan `items` & `fetchPolyclinics(params)` untuk caller modul lain (mis. ProcedureFormModal).
 */
export function usePolyclinics() {
  const items = ref([])
  const meta = reactive({
    page: 1,
    perPage: 15,
    total: 0,
    lastPage: 1,
    from: 0,
    to: 0,
  })
  const filters = reactive({
    search: '',
    is_active: null,
  })
  const sort = reactive({
    column: 'name',
    direction: 'asc',
  })

  const loading = ref(false)
  const loadError = ref('')
  const saving = ref(false)
  const serviceTypes = ref([])

  let latestListRequest = 0
  let latestServiceTypesRequest = 0

  async function fetchServiceTypes() {
    const requestId = ++latestServiceTypesRequest
    try {
      const response = await polyclinicService.getServiceTypes()
      if (requestId !== latestServiceTypesRequest) return
      const payload = response?.data?.data ?? response?.data ?? response ?? []
      serviceTypes.value = Array.isArray(payload) ? payload : []
    } catch {
      if (requestId === latestServiceTypesRequest) serviceTypes.value = []
    }
  }

  async function fetchPolyclinics(overrideParams = {}) {
    const requestId = ++latestListRequest
    loading.value = true
    loadError.value = ''

    const baseParams = {
      page: meta.page,
      per_page: meta.perPage,
      sort: sort.column,
      direction: sort.direction,
    }
    if (isFilled(filters.search)) baseParams.search = filters.search
    if (filters.is_active !== null && filters.is_active !== undefined && filters.is_active !== '') {
      baseParams.is_active = filters.is_active
    }

    const params = {
      ...baseParams,
      ...(overrideParams && typeof overrideParams === 'object' ? overrideParams : {}),
    }

    try {
      const response = await polyclinicService.getPolyclinics(params)
      if (requestId !== latestListRequest) return

      const payload = response?.data ?? response
      const rows = Array.isArray(payload?.data) ? payload.data : []
      items.value = rows

      const pageMeta = payload?.meta ?? {}
      const currentPage = pageMeta.current_page ?? meta.page
      const perPage = pageMeta.per_page ?? meta.perPage
      const total = pageMeta.total ?? rows.length

      Object.assign(meta, {
        page: currentPage,
        perPage,
        total,
        lastPage: pageMeta.last_page ?? 1,
        from: pageMeta.from ?? (rows.length > 0 ? (currentPage - 1) * perPage + 1 : 0),
        to: pageMeta.to ?? (rows.length > 0 ? (currentPage - 1) * perPage + rows.length : 0),
      })
    } catch (error) {
      if (requestId !== latestListRequest) return
      const normalized = normalizeApiError(error, 'Data poliklinik gagal dimuat.')
      loadError.value = getSafePolyclinicErrorMessage(normalized, 'Data poliklinik gagal dimuat.')
    } finally {
      if (requestId === latestListRequest) loading.value = false
    }
  }

  function setPage(page) {
    if (page < 1 || page > meta.lastPage || page === meta.page) return Promise.resolve()
    meta.page = page
    return fetchPolyclinics()
  }

  function setFilters(patch) {
    Object.assign(filters, patch)
    meta.page = 1
    return fetchPolyclinics()
  }

  function resetFilters() {
    return setFilters({ search: '', is_active: null })
  }

  function setSort(column) {
    if (sort.column === column) {
      sort.direction = sort.direction === 'asc' ? 'desc' : 'asc'
    } else {
      sort.column = column
      sort.direction = 'asc'
    }
    meta.page = 1
    return fetchPolyclinics()
  }

  async function mutate(requestFn, fallbackMessage, afterSuccess) {
    saving.value = true
    try {
      const data = await requestFn()
      if (afterSuccess) afterSuccess()
      await fetchPolyclinics()
      return { ok: true, data }
    } catch (error) {
      return { ok: false, error: normalizeApiError(error, fallbackMessage) }
    } finally {
      saving.value = false
    }
  }

  const create = (payload) =>
    mutate(
      () => polyclinicService.createPolyclinic(payload),
      'Data poliklinik gagal ditambahkan.',
    )

  const update = (id, payload) =>
    mutate(
      () => polyclinicService.updatePolyclinic(id, payload),
      'Data poliklinik gagal diperbarui.',
    )

  const remove = (id) =>
    mutate(
      () => polyclinicService.archivePolyclinic(id),
      'Data poliklinik gagal diarsipkan.',
      () => {
        if (items.value.length === 1 && meta.page > 1) {
          meta.page -= 1
        }
      },
    )

  return {
    items,
    meta,
    filters,
    sort,
    loading,
    loadError,
    saving,
    serviceTypes,
    fetchPolyclinics,
    fetchServiceTypes,
    create,
    update,
    remove,
    setPage,
    setFilters,
    resetFilters,
    setSort,
  }
}
