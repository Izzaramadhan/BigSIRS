import { ref, reactive, computed } from 'vue'
import { buildingsService } from '@/services/master-data/buildings.service'

export function useBuildings() {
  const items = ref([])
  const pagination = reactive({
    current_page: 1,
    per_page: 10,
    total: 0,
    last_page: 1,
    from: 0,
    to: 0,
  })

  const filters = reactive({
    search: '',
  })

  const sort = reactive({
    column: 'created_at',
    direction: 'desc',
  })

  const loading = ref(false)
  const submitting = ref(false)
  const error = ref(null)

  const fetchBuildings = async (customParams = {}) => {
    loading.value = true
    error.value = null

    try {
      const params = {
        page: pagination.current_page,
        per_page: pagination.per_page,
        sort: sort.column,
        direction: sort.direction,
        ...customParams,
      }

      if (filters.search && filters.search.trim()) {
        params.search = filters.search.trim()
      }

      const response = await buildingsService.getBuildings(params)

      const dataList = response.data || []
      items.value = Array.isArray(dataList) ? dataList : dataList.data || []

      if (response.meta) {
        pagination.current_page = response.meta.current_page || 1
        pagination.per_page = response.meta.per_page || 10
        pagination.total = response.meta.total || 0
        pagination.last_page = response.meta.last_page || 1
        pagination.from =
          response.meta.from ?? (pagination.current_page - 1) * pagination.per_page + 1
        pagination.to =
          response.meta.to ??
          Math.min(pagination.current_page * pagination.per_page, pagination.total)
      } else {
        pagination.total = items.value.length
        pagination.from = items.value.length > 0 ? 1 : 0
        pagination.to = items.value.length
        pagination.last_page = 1
      }

      return { success: true, data: response }
    } catch (err) {
      const msg = err.response?.data?.message || 'Data Gedung gagal dimuat.'
      error.value = msg
      return { success: false, error: err }
    } finally {
      loading.value = false
    }
  }

  const createBuilding = async (payload) => {
    submitting.value = true
    error.value = null

    try {
      const response = await buildingsService.createBuilding(payload)
      return { success: true, data: response }
    } catch (err) {
      const errorMessage = err.response?.data?.message || 'Data Gedung gagal ditambahkan.'
      error.value = errorMessage
      return {
        success: false,
        error: err,
        errors: err.response?.data?.errors || {},
      }
    } finally {
      submitting.value = false
    }
  }

  const updateBuilding = async (id, payload) => {
    submitting.value = true
    error.value = null

    try {
      const response = await buildingsService.updateBuilding(id, payload)
      return { success: true, data: response }
    } catch (err) {
      const errorMessage = err.response?.data?.message || 'Data Gedung gagal diperbarui.'
      error.value = errorMessage
      return {
        success: false,
        error: err,
        errors: err.response?.data?.errors || {},
      }
    } finally {
      submitting.value = false
    }
  }

  const deleteBuilding = async (id) => {
    submitting.value = true
    error.value = null

    try {
      await buildingsService.deleteBuilding(id)
      return { success: true }
    } catch (err) {
      const errorMessage = err.response?.data?.message || 'Data Gedung gagal dihapus.'
      error.value = errorMessage
      return {
        success: false,
        error: err,
      }
    } finally {
      submitting.value = false
    }
  }

  const setPage = (page) => {
    if (page >= 1 && page <= pagination.last_page) {
      pagination.current_page = page
      return fetchBuildings()
    }
  }

  const setSort = (column) => {
    if (sort.column === column) {
      sort.direction = sort.direction === 'asc' ? 'desc' : 'asc'
    } else {
      sort.column = column
      sort.direction = 'asc'
    }
    pagination.current_page = 1
    return fetchBuildings()
  }

  return {
    items,
    pagination,
    filters,
    sort,
    loading,
    submitting,
    error,
    fetchBuildings,
    createBuilding,
    updateBuilding,
    deleteBuilding,
    setPage,
    setSort,
    isEmpty: computed(() => !loading.value && items.value.length === 0),
  }
}
