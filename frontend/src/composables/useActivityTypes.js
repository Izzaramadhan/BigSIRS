import { ref } from 'vue'
import ActivityTypeService from '@/services/activityType.service'

export function useActivityTypes() {
  const activityTypes = ref([])
  const meta = ref(null)
  const loading = ref(false)
  const error = ref(null)

  const fetchActivityTypes = async (params = {}) => {
    loading.value = true
    error.value = null
    try {
      const response = await ActivityTypeService.getAll(params)
      activityTypes.value = response.data
      meta.value = response.meta || {
        current_page: 1,
        last_page: 1,
        total: response.data.length,
        per_page: 10
      }
      return response
    } catch (err) {
      error.value = err.response?.data?.message || 'Gagal memuat data jenis kegiatan'
      throw err
    } finally {
      loading.value = false
    }
  }

  const createActivityType = async (data) => {
    loading.value = true
    error.value = null
    try {
      const response = await ActivityTypeService.create(data)
      return response
    } catch (err) {
      error.value = err.response?.data?.message || 'Gagal menambahkan data jenis kegiatan'
      throw err
    } finally {
      loading.value = false
    }
  }

  const updateActivityType = async (id, data) => {
    loading.value = true
    error.value = null
    try {
      const response = await ActivityTypeService.update(id, data)
      return response
    } catch (err) {
      error.value = err.response?.data?.message || 'Gagal memperbarui data jenis kegiatan'
      throw err
    } finally {
      loading.value = false
    }
  }

  const updateStatus = async (id, isActive) => {
    error.value = null
    try {
      const response = await ActivityTypeService.updateStatus(id, isActive)
      // Update local state if needed
      const index = activityTypes.value.findIndex(item => item.id === id)
      if (index !== -1) {
        activityTypes.value[index].is_active = isActive
      }
      return response
    } catch (err) {
      error.value = err.response?.data?.message || 'Gagal memperbarui status'
      throw err
    }
  }

  const deleteActivityType = async (id) => {
    loading.value = true
    error.value = null
    try {
      await ActivityTypeService.delete(id)
    } catch (err) {
      error.value = err.response?.data?.message || 'Gagal menghapus data jenis kegiatan'
      throw err
    } finally {
      loading.value = false
    }
  }

  const lookupActivityTypes = async (excludeId = null) => {
    try {
      const params = excludeId ? { exclude_id: excludeId } : {}
      const response = await ActivityTypeService.lookup(params)
      return response.data
    } catch (err) {
      console.error('Failed to fetch activity types lookup', err)
      return []
    }
  }

  return {
    activityTypes,
    meta,
    loading,
    error,
    fetchActivityTypes,
    createActivityType,
    updateActivityType,
    updateStatus,
    deleteActivityType,
    lookupActivityTypes
  }
}
