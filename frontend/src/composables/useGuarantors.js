import { ref } from 'vue'
import { guarantorsService } from '@/services/master-data/guarantors.service'

export function useGuarantors() {
  const guarantors = ref([])
  const meta = ref({
    current_page: 1,
    last_page: 1,
    per_page: 10,
    total: 0
  })
  
  const loading = ref(false)
  const error = ref(null)

  const fetchGuarantors = async (params = {}) => {
    loading.value = true
    error.value = null
    try {
      const response = await guarantorsService.getGuarantors(params)
      guarantors.value = response.data || []
      if (response.meta) {
        meta.value = response.meta
      }
    } catch (err) {
      error.value = err.response?.data?.message || 'Gagal memuat data jenis asuransi'
      throw err
    } finally {
      loading.value = false
    }
  }

  const createGuarantor = async (data) => {
    loading.value = true
    error.value = null
    try {
      return await guarantorsService.createGuarantor(data)
    } catch (err) {
      error.value = err.response?.data?.message || 'Gagal menyimpan data'
      throw err
    } finally {
      loading.value = false
    }
  }

  const updateGuarantor = async (id, data) => {
    loading.value = true
    error.value = null
    try {
      return await guarantorsService.updateGuarantor(id, data)
    } catch (err) {
      error.value = err.response?.data?.message || 'Gagal memperbarui data'
      throw err
    } finally {
      loading.value = false
    }
  }

  const deleteGuarantor = async (id) => {
    loading.value = true
    error.value = null
    try {
      await guarantorsService.deleteGuarantor(id)
    } catch (err) {
      error.value = err.response?.data?.message || 'Gagal menghapus data'
      throw err
    } finally {
      loading.value = false
    }
  }

  const updateStatus = async (id, isActive) => {
    loading.value = true
    error.value = null
    try {
      return await guarantorsService.updateStatus(id, isActive)
    } catch (err) {
      error.value = err.response?.data?.message || 'Gagal memperbarui status'
      throw err
    } finally {
      loading.value = false
    }
  }

  return {
    guarantors,
    meta,
    loading,
    error,
    fetchGuarantors,
    createGuarantor,
    updateGuarantor,
    deleteGuarantor,
    updateStatus
  }
}
