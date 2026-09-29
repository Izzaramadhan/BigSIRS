import { ref } from 'vue'
import icd9cmService from '@/services/master-data/icd9cm.service'

export function useIcd9Cms() {
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
      
      const response = await icd9cmService.getList(params)
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
      error.value = err.response?.data?.message || 'Gagal mengambil data ICD-9-CM'
      console.error(err)
    } finally {
      loading.value = false
    }
  }

  const createCode = async (data) => {
    loading.value = true
    error.value = null
    try {
      await icd9cmService.create(data)
      await fetchCodes(1)
      return true
    } catch (err) {
      error.value = err.response?.data?.message || 'Gagal menambahkan ICD-9-CM'
      return false
    } finally {
      loading.value = false
    }
  }

  const updateCode = async (id, data) => {
    loading.value = true
    error.value = null
    try {
      await icd9cmService.update(id, data)
      await fetchCodes(pagination.value.currentPage)
      return true
    } catch (err) {
      error.value = err.response?.data?.message || 'Gagal memperbarui ICD-9-CM'
      return false
    } finally {
      loading.value = false
    }
  }

  const updateStatus = async (id, isActive) => {
    loading.value = true
    error.value = null
    try {
      await icd9cmService.updateStatus(id, isActive)
      
      const idx = codes.value.findIndex(c => c.id === id)
      if (idx !== -1) {
        codes.value[idx].is_active = isActive
      }
      return true
    } catch (err) {
      error.value = err.response?.data?.message || 'Gagal mengubah status ICD-9-CM'
      return false
    } finally {
      loading.value = false
    }
  }

  const deleteCode = async (id) => {
    loading.value = true
    error.value = null
    try {
      await icd9cmService.delete(id)
      
      if (codes.value.length === 1 && pagination.value.currentPage > 1) {
        await fetchCodes(pagination.value.currentPage - 1)
      } else {
        await fetchCodes(pagination.value.currentPage)
      }
      return true
    } catch (err) {
      if (err.response?.status === 409) {
        error.value = err.response.data.message || 'Data sedang digunakan dan tidak dapat dihapus'
      } else {
        error.value = err.response?.data?.message || 'Gagal menghapus ICD-9-CM'
      }
      return false
    } finally {
      loading.value = false
    }
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
    deleteCode
  }
}
