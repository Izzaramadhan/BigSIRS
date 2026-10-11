import { ref, reactive } from 'vue'
import icd9cmService from '@/services/master-data/icd9cm.service'

export function useIcd9Cms() {
  const items = ref([])
  const loading = ref(false)
  const submitting = ref(false)
  const error = ref(null)

  const pagination = reactive({
    current_page: 1,
    last_page: 1,
    per_page: 10,
    total: 0,
    from: 0,
    to: 0,
    get currentPage() {
      return this.current_page
    },
    set currentPage(val) {
      this.current_page = val
    },
    get lastPage() {
      return this.last_page
    },
    set lastPage(val) {
      this.last_page = val
    },
    get perPage() {
      return this.per_page
    },
    set perPage(val) {
      this.per_page = val
    }
  })

  const filters = reactive({
    search: ''
  })

  const sort = reactive({
    column: 'code',
    direction: 'asc'
  })

  const fetchIcd9Cms = async (page = 1) => {
    loading.value = true
    error.value = null
    try {
      const targetPage = typeof page === 'number' ? page : pagination.current_page || 1
      const params = {
        page: targetPage,
        per_page: pagination.per_page,
        sort: sort.column,
        direction: sort.direction
      }

      if (filters.search && filters.search.trim()) {
        params.search = filters.search.trim()
      }

      const response = await icd9cmService.getList(params)
      items.value = response.data || []

      if (response.meta) {
        pagination.current_page = response.meta.current_page ?? targetPage
        pagination.last_page = response.meta.last_page ?? 1
        pagination.per_page = response.meta.per_page ?? 10
        pagination.total = response.meta.total ?? items.value.length
        pagination.from = response.meta.from ?? (items.value.length > 0 ? (pagination.current_page - 1) * pagination.per_page + 1 : 0)
        pagination.to = response.meta.to ?? Math.min(pagination.current_page * pagination.per_page, pagination.total)
      } else {
        pagination.current_page = targetPage
        pagination.last_page = 1
        pagination.total = items.value.length
        pagination.from = items.value.length > 0 ? 1 : 0
        pagination.to = items.value.length
      }
    } catch (err) {
      error.value = err.response?.data?.message || 'Data ICD-9 gagal dimuat.'
      console.error(err)
    } finally {
      loading.value = false
    }
  }

  const createIcd9Cm = async (payload) => {
    submitting.value = true
    error.value = null
    try {
      const response = await icd9cmService.create(payload)
      return { success: true, data: response.data }
    } catch (err) {
      return {
        success: false,
        error: err,
        errors: err.response?.data?.errors || {}
      }
    } finally {
      submitting.value = false
    }
  }

  const updateIcd9Cm = async (id, payload) => {
    submitting.value = true
    error.value = null
    try {
      const response = await icd9cmService.update(id, payload)
      return { success: true, data: response.data }
    } catch (err) {
      return {
        success: false,
        error: err,
        errors: err.response?.data?.errors || {}
      }
    } finally {
      submitting.value = false
    }
  }

  const deleteIcd9Cm = async (id) => {
    submitting.value = true
    error.value = null
    try {
      const response = await icd9cmService.delete(id)
      return { success: true, data: response }
    } catch (err) {
      return {
        success: false,
        error: err
      }
    } finally {
      submitting.value = false
    }
  }

  const setPage = (page) => {
    if (page >= 1 && page <= pagination.last_page) {
      pagination.current_page = page
      fetchIcd9Cms(page)
    }
  }

  const setSort = (column) => {
    if (sort.column === column) {
      sort.direction = sort.direction === 'asc' ? 'desc' : 'asc'
    } else {
      sort.column = column
      sort.direction = 'asc'
    }
    fetchIcd9Cms(1)
  }

  const setFilter = (key, value) => {
    filters[key] = value
    pagination.current_page = 1
    fetchIcd9Cms(1)
  }

  return {
    items,
    codes: items,
    loading,
    submitting,
    error,
    pagination,
    filters,
    sort,
    fetchCodes: fetchIcd9Cms,
    fetchIcd9Cms,
    createCode: createIcd9Cm,
    createIcd9Cm,
    updateCode: updateIcd9Cm,
    updateIcd9Cm,
    deleteCode: deleteIcd9Cm,
    deleteIcd9Cm,
    setPage,
    handlePageChange: setPage,
    setSort,
    setFilter
  }
}
