import { ref } from 'vue'
import DietTypeService from '@/services/dietType.service'

export function useDietTypes() {
  const dietTypes = ref([])
  const meta = ref(null)
  const loading = ref(false)
  const error = ref(null)

  const fetchDietTypes = async (params = {}) => {
    loading.value = true
    error.value = null
    try {
      const response = await DietTypeService.getAll(params)
      dietTypes.value = response.data
      meta.value = response.meta || {
        current_page: 1,
        last_page: 1,
        total: response.data.length,
        per_page: 10
      }
      return response
    } catch (err) {
      error.value = err.response?.data?.message || 'Gagal memuat data asuhan gizi'
      throw err
    } finally {
      loading.value = false
    }
  }

  const createDietType = async (data) => {
    loading.value = true
    error.value = null
    try {
      const response = await DietTypeService.create(data)
      return response
    } catch (err) {
      error.value = err.response?.data?.message || 'Gagal menambahkan data asuhan gizi'
      throw err
    } finally {
      loading.value = false
    }
  }

  const updateDietType = async (id, data) => {
    loading.value = true
    error.value = null
    try {
      const response = await DietTypeService.update(id, data)
      return response
    } catch (err) {
      error.value = err.response?.data?.message || 'Gagal memperbarui data asuhan gizi'
      throw err
    } finally {
      loading.value = false
    }
  }

  const updateStatus = async (id, isActive) => {
    error.value = null
    try {
      const response = await DietTypeService.updateStatus(id, isActive)
      // Update local state if needed
      const index = dietTypes.value.findIndex(item => item.id === id)
      if (index !== -1) {
        dietTypes.value[index].is_active = isActive
      }
      return response
    } catch (err) {
      error.value = err.response?.data?.message || 'Gagal memperbarui status'
      throw err
    }
  }

  const deleteDietType = async (id) => {
    loading.value = true
    error.value = null
    try {
      await DietTypeService.delete(id)
    } catch (err) {
      error.value = err.response?.data?.message || 'Gagal menghapus data asuhan gizi'
      throw err
    } finally {
      loading.value = false
    }
  }

  return {
    dietTypes,
    meta,
    loading,
    error,
    fetchDietTypes,
    createDietType,
    updateDietType,
    updateStatus,
    deleteDietType
  }
}
