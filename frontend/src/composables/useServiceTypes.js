import { ref } from 'vue'
import ServiceTypeService from '@/services/serviceType.service'

export function useServiceTypes() {
  const serviceTypes = ref([])
  const loading = ref(false)
  const error = ref(null)
  const meta = ref({
    current_page: 1,
    last_page: 1,
    total: 0,
    per_page: 10
  })

  const fetchServiceTypes = async (params = {}) => {
    loading.value = true
    error.value = null
    try {
      const response = await ServiceTypeService.getAll(params)
      serviceTypes.value = response.data
      meta.value = response.meta || {
        current_page: 1,
        last_page: 1,
        total: response.data.length,
        per_page: 10
      }
      return response
    } catch (err) {
      error.value = err.response?.data?.message || 'Gagal memuat data jenis layanan'
      throw err
    } finally {
      loading.value = false
    }
  }

  const createServiceType = async (data) => {
    loading.value = true
    error.value = null
    try {
      const response = await ServiceTypeService.create(data)
      return response
    } catch (err) {
      error.value = err.response?.data?.message || 'Gagal menyimpan data jenis layanan'
      throw err
    } finally {
      loading.value = false
    }
  }

  const updateServiceType = async (id, data) => {
    loading.value = true
    error.value = null
    try {
      const response = await ServiceTypeService.update(id, data)
      return response
    } catch (err) {
      error.value = err.response?.data?.message || 'Gagal memperbarui data jenis layanan'
      throw err
    } finally {
      loading.value = false
    }
  }

  const updateStatus = async (id, isActive) => {
    loading.value = true
    error.value = null
    try {
      const response = await ServiceTypeService.updateStatus(id, isActive)
      return response
    } catch (err) {
      error.value = err.response?.data?.message || 'Gagal mengubah status jenis layanan'
      throw err
    } finally {
      loading.value = false
    }
  }

  const deleteServiceType = async (id) => {
    loading.value = true
    error.value = null
    try {
      await ServiceTypeService.delete(id)
    } catch (err) {
      error.value = err.response?.data?.message || 'Gagal menghapus data jenis layanan'
      throw err
    } finally {
      loading.value = false
    }
  }

  return {
    serviceTypes,
    loading,
    error,
    meta,
    fetchServiceTypes,
    createServiceType,
    updateServiceType,
    updateStatus,
    deleteServiceType
  }
}
