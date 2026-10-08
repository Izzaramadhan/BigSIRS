import { ref, computed } from 'vue'
import { radiologyTypesService } from '@/services/master-data/radiologyTypes.service'

export function useRadiologyTypes() {
  const types = ref([])
  const meta = ref({
    current_page: 1,
    per_page: 10,
    total: 0
  })
  const loading = ref(false)
  const error = ref(null)

  const fetchTypes = async (params = {}) => {
    loading.value = true
    error.value = null
    try {
      const response = await radiologyTypesService.getAll({
        page: meta.value.current_page,
        per_page: meta.value.per_page,
        ...params
      })
      types.value = response.data.data
      if (response.data.meta) {
        meta.value = response.data.meta
      }
    } catch (err) {
      error.value = err.response?.data?.message || 'Gagal memuat data tipe radiologi.'
      console.error('Error fetching radiology types:', err)
    } finally {
      loading.value = false
    }
  }

  const createType = async (data) => {
    loading.value = true
    error.value = null
    try {
      await radiologyTypesService.create(data)
      return { success: true }
    } catch (err) {
      const errorMessage = err.response?.data?.message || 'Gagal menyimpan tipe radiologi.'
      error.value = errorMessage
      return { success: false, error: errorMessage, errors: err.response?.data?.errors }
    } finally {
      loading.value = false
    }
  }

  const updateType = async (id, data) => {
    loading.value = true
    error.value = null
    try {
      await radiologyTypesService.update(id, data)
      return { success: true }
    } catch (err) {
      const errorMessage = err.response?.data?.message || 'Gagal memperbarui tipe radiologi.'
      error.value = errorMessage
      return { success: false, error: errorMessage, errors: err.response?.data?.errors }
    } finally {
      loading.value = false
    }
  }

  const deleteType = async (id) => {
    loading.value = true
    error.value = null
    try {
      await radiologyTypesService.delete(id)
      return { success: true }
    } catch (err) {
      const errorMessage = err.response?.data?.message || 'Gagal menghapus tipe radiologi.'
      error.value = errorMessage
      return { success: false, error: errorMessage }
    } finally {
      loading.value = false
    }
  }

  return {
    types,
    meta,
    loading,
    error,
    fetchTypes,
    createType,
    updateType,
    deleteType,
    isEmpty: computed(() => !loading.value && types.value.length === 0)
  }
}
