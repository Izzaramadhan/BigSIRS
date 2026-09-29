import { ref } from 'vue'
import icd10Service from '@/services/master-data/icd10.service'

export function useIcd10Codes() {
  const codes = ref([])
  const loading = ref(false)
  const error = ref(null)
  
  const pagination = ref({
    currentPage: 1,
    perPage: 10,
    total: 0,
    lastPage: 1
  })
  
  const filters = ref({
    search: '',
    is_active: null
  })

  const fetchCodes = async (page = 1) => {
    loading.value = true
    error.value = null
    try {
      const params = {
        page,
        per_page: pagination.value.perPage,
        ...(filters.value.search ? { search: filters.value.search } : {}),
        ...(filters.value.is_active !== null ? { is_active: filters.value.is_active ? 1 : 0 } : {})
      }
      
      const response = await icd10Service.getIcd10Codes(params)
      codes.value = response.data
      
      if (response.meta) {
        pagination.value = {
          currentPage: response.meta.current_page,
          perPage: response.meta.per_page,
          total: response.meta.total,
          lastPage: response.meta.last_page
        }
      }
    } catch (err) {
      error.value = err.response?.data?.message || 'Gagal mengambil data ICD-10'
      console.error(err)
    } finally {
      loading.value = false
    }
  }

  const createCode = async (data) => {
    loading.value = true
    error.value = null
    try {
      await icd10Service.createIcd10Code(data)
      await fetchCodes(1)
      return true
    } catch (err) {
      error.value = err.response?.data?.message || 'Gagal menambahkan ICD-10'
      return false
    } finally {
      loading.value = false
    }
  }

  const updateCode = async (id, data) => {
    loading.value = true
    error.value = null
    try {
      await icd10Service.updateIcd10Code(id, data)
      await fetchCodes(pagination.value.currentPage)
      return true
    } catch (err) {
      error.value = err.response?.data?.message || 'Gagal memperbarui ICD-10'
      return false
    } finally {
      loading.value = false
    }
  }

  const updateStatus = async (id, isActive) => {
    loading.value = true
    error.value = null
    try {
      await icd10Service.updateStatus(id, isActive)
      
      const idx = codes.value.findIndex(c => c.id === id)
      if (idx !== -1) {
        codes.value[idx].is_active = isActive
      }
      return true
    } catch (err) {
      error.value = err.response?.data?.message || 'Gagal mengubah status ICD-10'
      return false
    } finally {
      loading.value = false
    }
  }

  const deleteCode = async (id) => {
    loading.value = true
    error.value = null
    try {
      await icd10Service.deleteIcd10Code(id)
      await fetchCodes(pagination.value.currentPage)
      return true
    } catch (err) {
      error.value = err.response?.data?.message || 'Gagal menghapus ICD-10'
      return false
    } finally {
      loading.value = false
    }
  }

  const handlePageChange = (page) => {
    fetchCodes(page)
  }

  const setFilter = (key, value) => {
    filters.value[key] = value
    fetchCodes(1)
  }

  return {
    codes,
    loading,
    error,
    pagination,
    filters,
    fetchCodes,
    createCode,
    updateCode,
    updateStatus,
    deleteCode,
    handlePageChange,
    setFilter
  }
}
