import { ref } from 'vue'
import { occupationsService } from '@/services/master-data/occupations.service'

export function useOccupations() {
  const occupations = ref([])
  const meta = ref({
    current_page: 1,
    last_page: 1,
    per_page: 10,
    total: 0
  })
  
  const loading = ref(false)
  const error = ref(null)

  const fetchOccupations = async (params = {}) => {
    loading.value = true
    error.value = null
    try {
      const response = await occupationsService.getOccupations(params)
      occupations.value = response.data || []
      if (response.meta) {
        meta.value = response.meta
      }
    } catch (err) {
      error.value = err.response?.data?.message || 'Gagal memuat data pekerjaan'
      throw err
    } finally {
      loading.value = false
    }
  }

  const createOccupation = async (data) => {
    loading.value = true
    error.value = null
    try {
      return await occupationsService.createOccupation(data)
    } catch (err) {
      error.value = err.response?.data?.message || 'Gagal menyimpan data'
      throw err
    } finally {
      loading.value = false
    }
  }

  const updateOccupation = async (id, data) => {
    loading.value = true
    error.value = null
    try {
      return await occupationsService.updateOccupation(id, data)
    } catch (err) {
      error.value = err.response?.data?.message || 'Gagal memperbarui data'
      throw err
    } finally {
      loading.value = false
    }
  }

  const deleteOccupation = async (id) => {
    loading.value = true
    error.value = null
    try {
      await occupationsService.deleteOccupation(id)
    } catch (err) {
      error.value = err.response?.data?.message || 'Gagal menghapus data'
      throw err
    } finally {
      loading.value = false
    }
  }



  return {
    occupations,
    meta,
    loading,
    error,
    fetchOccupations,
    createOccupation,
    updateOccupation,
    deleteOccupation
  }
}
