import { ref } from 'vue'
import axios from '@/utils/axios'

export function useMedicationSignas() {
  const medicationSignas = ref([])
  const meta = ref({
    current_page: 1,
    from: null,
    last_page: 1,
    per_page: 10,
    to: null,
    total: 0
  })
  const loading = ref(false)
  const error = ref(null)

  const fetchMedicationSignas = async (params = {}) => {
    loading.value = true
    error.value = null
    try {
      const response = await axios.get('/master-data/medication-signas', {
        params: {
          page: params.page || 1,
          per_page: params.per_page || 10,
          search: params.search || '',
          ...params
        }
      })
      medicationSignas.value = response.data.data
      meta.value = response.data.meta
    } catch (err) {
      error.value = err.response?.data?.message || 'Terjadi kesalahan saat memuat data'
      console.error('Error fetching medication signas:', err)
    } finally {
      loading.value = false
    }
  }

  const createMedicationSigna = async (data) => {
    loading.value = true
    error.value = null
    try {
      const response = await axios.post('/master-data/medication-signas', data)
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Gagal menyimpan data'
      throw err
    } finally {
      loading.value = false
    }
  }

  const updateMedicationSigna = async (id, data) => {
    loading.value = true
    error.value = null
    try {
      const response = await axios.put(`/master-data/medication-signas/${id}`, data)
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Gagal mengupdate data'
      throw err
    } finally {
      loading.value = false
    }
  }

  const deleteMedicationSigna = async (id) => {
    loading.value = true
    error.value = null
    try {
      await axios.delete(`/master-data/medication-signas/${id}`)
      return true
    } catch (err) {
      error.value = err.response?.data?.message || 'Gagal menghapus data'
      throw err
    } finally {
      loading.value = false
    }
  }

  const updateMedicationSignaStatus = async (id, isActive) => {
    loading.value = true
    error.value = null
    try {
      const response = await axios.patch(`/master-data/medication-signas/${id}/status`, {
        is_active: isActive
      })
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Gagal mengubah status'
      throw err
    } finally {
      loading.value = false
    }
  }

  return {
    medicationSignas,
    meta,
    loading,
    error,
    fetchMedicationSignas,
    createMedicationSigna,
    updateMedicationSigna,
    deleteMedicationSigna,
    updateMedicationSignaStatus
  }
}
