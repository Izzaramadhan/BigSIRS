import { ref, computed } from 'vue'
import axios from '@/utils/axios'

export function useMedicinePackages() {
  const medicinePackages = ref([])
  const loading = ref(false)
  const error = ref(null)
  
  const total = ref(0)
  const currentPage = ref(1)
  const perPage = ref(10)
  const searchQuery = ref('')
  const isActiveFilter = ref(null)

  const fetchMedicinePackages = async (page = 1) => {
    loading.value = true
    error.value = null
    currentPage.value = page
    
    try {
      const params = {
        page: currentPage.value,
        per_page: perPage.value
      }
      
      if (searchQuery.value) {
        params.search = searchQuery.value
      }
      
      if (isActiveFilter.value !== null) {
        params.is_active = isActiveFilter.value
      }

      const response = await axios.get('/master-data/medicine-packages', { params })
      medicinePackages.value = response.data.data
      total.value = response.data.meta.total
      
    } catch (err) {
      error.value = err.response?.data?.message || err.message
      console.error('Error fetching medicine packages:', err)
    } finally {
      loading.value = false
    }
  }

  const getMedicinePackage = async (id) => {
    loading.value = true
    error.value = null
    try {
      const response = await axios.get(`/master-data/medicine-packages/${id}`)
      return response.data.data
    } catch (err) {
      error.value = err.response?.data?.message || err.message
      throw err
    } finally {
      loading.value = false
    }
  }

  const createMedicinePackage = async (data) => {
    loading.value = true
    error.value = null
    try {
      const response = await axios.post('/master-data/medicine-packages', data)
      return response.data.data
    } catch (err) {
      error.value = err.response?.data?.message || err.message
      throw err
    } finally {
      loading.value = false
    }
  }

  const updateMedicinePackage = async (id, data) => {
    loading.value = true
    error.value = null
    try {
      const response = await axios.put(`/master-data/medicine-packages/${id}`, data)
      return response.data.data
    } catch (err) {
      error.value = err.response?.data?.message || err.message
      throw err
    } finally {
      loading.value = false
    }
  }

  const deleteMedicinePackage = async (id) => {
    loading.value = true
    error.value = null
    try {
      await axios.delete(`/master-data/medicine-packages/${id}`)
    } catch (err) {
      error.value = err.response?.data?.message || err.message
      throw err
    } finally {
      loading.value = false
    }
  }

  const totalPages = computed(() => {
    return Math.ceil(total.value / perPage.value)
  })

  return {
    medicinePackages,
    loading,
    error,
    total,
    currentPage,
    perPage,
    searchQuery,
    isActiveFilter,
    totalPages,
    fetchMedicinePackages,
    getMedicinePackage,
    createMedicinePackage,
    updateMedicinePackage,
    deleteMedicinePackage
  }
}
