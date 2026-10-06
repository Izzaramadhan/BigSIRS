import { ref } from 'vue'
import axios from '@/utils/axios'

export function useVillages() {
  const villages = ref([])
  const meta = ref(null)
  const loading = ref(false)
  const error = ref(null)

  const fetchVillages = async (params = {}) => {
    loading.value = true
    error.value = null
    try {
      const response = await axios.get('/master-data/villages', { params })
      villages.value = response.data.data
      meta.value = response.data.meta
    } catch (err) {
      error.value = err.response?.data?.message || 'Gagal memuat data kelurahan'
      throw err
    } finally {
      loading.value = false
    }
  }

  const createVillage = async (data) => {
    loading.value = true
    error.value = null
    try {
      const response = await axios.post('/master-data/villages', data)
      return response.data.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Gagal menambahkan kelurahan'
      throw err
    } finally {
      loading.value = false
    }
  }

  const updateVillage = async (id, data) => {
    loading.value = true
    error.value = null
    try {
      const response = await axios.put(`/master-data/villages/${id}`, data)
      return response.data.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Gagal memperbarui kelurahan'
      throw err
    } finally {
      loading.value = false
    }
  }

  const deleteVillage = async (id) => {
    loading.value = true
    error.value = null
    try {
      await axios.delete(`/master-data/villages/${id}`)
    } catch (err) {
      error.value = err.response?.data?.message || 'Gagal menghapus kelurahan'
      throw err
    } finally {
      loading.value = false
    }
  }

  return {
    villages,
    meta,
    loading,
    error,
    fetchVillages,
    createVillage,
    updateVillage,
    deleteVillage
  }
}
