import { ref } from 'vue'
import axios from '@/utils/axios'

export function useDistricts() {
  const districts = ref([])
  const meta = ref(null)
  const loading = ref(false)
  const error = ref(null)

  const fetchDistricts = async (params = {}) => {
    loading.value = true
    error.value = null
    try {
      const response = await axios.get('/master-data/districts', { params })
      districts.value = response.data.data
      meta.value = response.data.meta
    } catch (err) {
      error.value = err.response?.data?.message || 'Gagal memuat data kecamatan'
      throw err
    } finally {
      loading.value = false
    }
  }

  const createDistrict = async (data) => {
    loading.value = true
    error.value = null
    try {
      const response = await axios.post('/master-data/districts', data)
      return response.data.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Gagal menambahkan kecamatan'
      throw err
    } finally {
      loading.value = false
    }
  }

  const updateDistrict = async (id, data) => {
    loading.value = true
    error.value = null
    try {
      const response = await axios.put(`/master-data/districts/${id}`, data)
      return response.data.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Gagal memperbarui kecamatan'
      throw err
    } finally {
      loading.value = false
    }
  }

  const deleteDistrict = async (id) => {
    loading.value = true
    error.value = null
    try {
      await axios.delete(`/master-data/districts/${id}`)
    } catch (err) {
      error.value = err.response?.data?.message || 'Gagal menghapus kecamatan'
      throw err
    } finally {
      loading.value = false
    }
  }

  return {
    districts,
    meta,
    loading,
    error,
    fetchDistricts,
    createDistrict,
    updateDistrict,
    deleteDistrict
  }
}
