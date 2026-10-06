import { ref } from 'vue'
import axios from '@/utils/axios'

export function useRegencies() {
  const regencies = ref([])
  const meta = ref(null)
  const loading = ref(false)
  const error = ref(null)

  const fetchRegencies = async (params = {}) => {
    loading.value = true
    error.value = null
    try {
      const response = await axios.get('/master-data/regencies', { params })
      regencies.value = response.data.data
      meta.value = response.data.meta
    } catch (err) {
      error.value = err.response?.data?.message || 'Gagal memuat data kabupaten'
      throw err
    } finally {
      loading.value = false
    }
  }

  const createRegency = async (data) => {
    loading.value = true
    error.value = null
    try {
      const response = await axios.post('/master-data/regencies', data)
      return response.data.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Gagal menambahkan kabupaten'
      throw err
    } finally {
      loading.value = false
    }
  }

  const updateRegency = async (id, data) => {
    loading.value = true
    error.value = null
    try {
      const response = await axios.put(`/master-data/regencies/${id}`, data)
      return response.data.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Gagal memperbarui kabupaten'
      throw err
    } finally {
      loading.value = false
    }
  }

  const deleteRegency = async (id) => {
    loading.value = true
    error.value = null
    try {
      await axios.delete(`/master-data/regencies/${id}`)
    } catch (err) {
      error.value = err.response?.data?.message || 'Gagal menghapus kabupaten'
      throw err
    } finally {
      loading.value = false
    }
  }

  return {
    regencies,
    meta,
    loading,
    error,
    fetchRegencies,
    createRegency,
    updateRegency,
    deleteRegency
  }
}
