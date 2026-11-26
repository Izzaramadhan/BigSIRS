import { reactive, ref } from 'vue'
import { getSafeDoctorScheduleErrorMessage } from '@/components/master-data/doctor-schedules/doctor-schedule.helpers'
import doctorScheduleService from '@/services/master-data/doctor-schedules.service'
import { normalizeApiError } from '@/utils/http-error'

const isFilled = (value) => value !== '' && value !== null && value !== undefined

/**
 * Composable modul Jadwal Dokter: mengelola daftar berpaginasi sekaligus data rekap mingguan,
 * dilengkapi perlindungan race condition (request counter) dan pemisahan error fetch vs mutasi.
 */
export function useDoctorSchedules() {
  const items = ref([])
  const meta = reactive({
    page: 1,
    perPage: 10,
    total: 0,
    lastPage: 1,
    from: 0,
    to: 0,
  })
  const filters = reactive({
    search: '',
    doctor_id: null,
    polyclinic_id: null,
  })
  const sort = reactive({
    column: 'id',
    direction: 'desc',
  })

  const loading = ref(false)
  const loadError = ref('')
  const saving = ref(false)

  const recapItems = ref([])
  const recapLoading = ref(false)
  const recapError = ref('')

  let latestListRequest = 0
  let latestRecapRequest = 0

  async function fetchSchedules() {
    const requestId = ++latestListRequest
    loading.value = true
    loadError.value = ''

    const params = Object.fromEntries(
      Object.entries({
        page: meta.page,
        per_page: meta.perPage,
        sort_by: sort.column,
        sort_dir: sort.direction,
        search: filters.search,
        doctor_id: filters.doctor_id,
        polyclinic_id: filters.polyclinic_id,
      }).filter(([, value]) => isFilled(value)),
    )

    try {
      const response = await doctorScheduleService.getDoctorSchedules(params)
      if (requestId !== latestListRequest) return

      const rows = Array.isArray(response?.data) ? response.data : []
      items.value = rows

      const pageMeta = response?.meta ?? response ?? {}
      Object.assign(meta, {
        page: pageMeta.current_page ?? meta.page,
        perPage: pageMeta.per_page ?? meta.perPage,
        total: pageMeta.total ?? rows.length,
        lastPage: pageMeta.last_page ?? 1,
        from: pageMeta.from ?? (rows.length > 0 ? (meta.page - 1) * meta.perPage + 1 : 0),
        to:
          pageMeta.to ??
          (rows.length > 0 ? (meta.page - 1) * meta.perPage + rows.length : 0),
      })
    } catch (error) {
      if (requestId !== latestListRequest) return
      const normalized = normalizeApiError(error, 'Data jadwal dokter gagal dimuat.')
      loadError.value = getSafeDoctorScheduleErrorMessage(
        normalized,
        'Data jadwal dokter gagal dimuat.',
      )
    } finally {
      if (requestId === latestListRequest) loading.value = false
    }
  }

  async function fetchRecap() {
    const requestId = ++latestRecapRequest
    recapLoading.value = true
    recapError.value = ''

    try {
      const response = await doctorScheduleService.getRecap()
      if (requestId !== latestRecapRequest) return { ok: true, stale: true }

      recapItems.value = Array.isArray(response?.data)
        ? response.data
        : Array.isArray(response)
          ? response
          : []
      return { ok: true }
    } catch (error) {
      if (requestId !== latestRecapRequest) return { ok: false, stale: true }
      const normalized = normalizeApiError(error, 'Data rekap jadwal dokter gagal dimuat.')
      const safeMsg = getSafeDoctorScheduleErrorMessage(
        normalized,
        'Data rekap jadwal dokter gagal dimuat.',
      )
      recapError.value = safeMsg
      return { ok: false, error: normalized }
    } finally {
      if (requestId === latestRecapRequest) recapLoading.value = false
    }
  }

  function setPage(page) {
    if (page < 1 || page > meta.lastPage || page === meta.page) return Promise.resolve()
    meta.page = page
    return fetchSchedules()
  }

  function setFilters(patch) {
    Object.assign(filters, patch)
    meta.page = 1
    return fetchSchedules()
  }

  function setSort(column) {
    if (sort.column === column) {
      sort.direction = sort.direction === 'asc' ? 'desc' : 'asc'
    } else {
      sort.column = column
      sort.direction = 'asc'
    }
    meta.page = 1
    return fetchSchedules()
  }

  async function refreshAll() {
    const [, recapResult] = await Promise.all([fetchSchedules(), fetchRecap()])
    return recapResult
  }

  async function mutate(requestFn, fallbackMessage, afterSuccess) {
    saving.value = true
    try {
      const data = await requestFn()
      if (afterSuccess) afterSuccess()
      const [, recapResult] = await Promise.all([fetchSchedules(), fetchRecap()])
      return { ok: true, data, recapOk: recapResult?.ok !== false }
    } catch (error) {
      return { ok: false, error: normalizeApiError(error, fallbackMessage) }
    } finally {
      saving.value = false
    }
  }

  const create = (payload) =>
    mutate(() => doctorScheduleService.createDoctorSchedule(payload), 'Jadwal dokter gagal ditambahkan.')

  const update = (id, payload) =>
    mutate(
      () => doctorScheduleService.updateDoctorSchedule(id, payload),
      'Jadwal dokter gagal diperbarui.',
    )

  const remove = (id) =>
    mutate(
      () => doctorScheduleService.deleteDoctorSchedule(id),
      'Jadwal dokter gagal dihapus.',
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
    recapItems,
    recapLoading,
    recapError,
    fetchSchedules,
    fetchRecap,
    refreshAll,
    setPage,
    setFilters,
    setSort,
    create,
    update,
    remove,
  }
}
