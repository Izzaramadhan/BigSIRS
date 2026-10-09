import { ref } from 'vue'
import axios from '@/utils/axios'

export function useFollowUpHandlings() {
  const handlings = ref([])
  const loading = ref(false)
  const error = ref(null)
  const pagination = ref({
    currentPage: 1,
    lastPage: 1,
    total: 0,
    perPage: 10
  })

  const fetchHandlings = async (params = {}) => {
    loading.value = true
    error.value = null
    
    try {
      const response = await axios.get('/master-data/follow-up-handlings', {
        params: {
          page: params.page || pagination.value.currentPage,
          per_page: params.per_page || pagination.value.perPage,
          search: params.search || ''
        }
      })
      
      if (params.per_page === 'all') {
        handlings.value = response.data.data
        pagination.value = {
          currentPage: 1,
          lastPage: 1,
          total: response.data.data.length,
          perPage: 'all'
        }
      } else {
        handlings.value = response.data.data
        pagination.value = {
          currentPage: response.data.meta.current_page,
          lastPage: response.data.meta.last_page,
          total: response.data.meta.total,
          perPage: response.data.meta.per_page
        }
      }
    } catch (err) {
      error.value = err.response?.data?.message || 'Terjadi kesalahan saat memuat data penanganan lanjutan'
      if (err.response?.status === 401) {
        throw err
      }
    } finally {
      loading.value = false
    }
  }

  const createHandling = async (data) => {
    loading.value = true
    error.value = null
    
    try {
      const response = await axios.post('/master-data/follow-up-handlings', data)
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Terjadi kesalahan saat menyimpan data'
      throw err
    } finally {
      loading.value = false
    }
  }

  const updateHandling = async (id, data) => {
    loading.value = true
    error.value = null
    
    try {
      const response = await axios.put(`/master-data/follow-up-handlings/${id}`, data)
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Terjadi kesalahan saat mengupdate data'
      throw err
    } finally {
      loading.value = false
    }
  }

  const deleteHandling = async (id) => {
    loading.value = true
    error.value = null
    
    try {
      await axios.delete(`/master-data/follow-up-handlings/${id}`)
    } catch (err) {
      error.value = err.response?.data?.message || 'Terjadi kesalahan saat menghapus data'
      throw err
    } finally {
      loading.value = false
    }
  }

  return {
    handlings,
    loading,
    error,
    pagination,
    fetchHandlings,
    createHandling,
    updateHandling,
    deleteHandling
  }
}
