import { ref } from 'vue'
import axios from '@/utils/axios'
import type { RadiologyItem, RadiologyItemFilters } from '@/types/radiology'

export function useRadiologyItems() {
  const items = ref<RadiologyItem[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)
  
  const currentPage = ref(1)
  const perPage = ref(10)
  const totalItems = ref(0)

  const fetchItems = async (filters: RadiologyItemFilters = {}) => {
    loading.value = true
    error.value = null
    try {
      const response = await axios.get('/master-data/radiology-items', {
        params: {
          page: currentPage.value,
          per_page: perPage.value,
          ...filters
        }
      })
      
      items.value = response.data.data
      
      if (response.data.meta) {
        totalItems.value = response.data.meta.total
        currentPage.value = response.data.meta.current_page
      }
    } catch (e: any) {
      error.value = e.response?.data?.message || 'Gagal memuat data item radiologi'
      items.value = []
    } finally {
      loading.value = false
    }
  }

  const createItem = async (data: any) => {
    loading.value = true
    error.value = null
    try {
      await axios.post('/master-data/radiology-items', data)
      return true
    } catch (e: any) {
      error.value = e.response?.data?.message || 'Gagal menyimpan item radiologi'
      return false
    } finally {
      loading.value = false
    }
  }

  const updateItem = async (id: number, data: any) => {
    loading.value = true
    error.value = null
    try {
      await axios.put(`/master-data/radiology-items/${id}`, data)
      return true
    } catch (e: any) {
      error.value = e.response?.data?.message || 'Gagal mengubah item radiologi'
      return false
    } finally {
      loading.value = false
    }
  }

  const deleteItem = async (id: number) => {
    loading.value = true
    error.value = null
    try {
      await axios.delete(`/master-data/radiology-items/${id}`)
      return true
    } catch (e: any) {
      error.value = e.response?.data?.message || 'Gagal menghapus item radiologi'
      return false
    } finally {
      loading.value = false
    }
  }

  const updateStatus = async (id: number, isActive: boolean) => {
    try {
      await axios.patch(`/master-data/radiology-items/${id}/status`, {
        is_active: isActive
      })
      return true
    } catch (e: any) {
      error.value = e.response?.data?.message || 'Gagal mengubah status'
      return false
    }
  }

  return {
    items,
    loading,
    error,
    currentPage,
    perPage,
    totalItems,
    fetchItems,
    createItem,
    updateItem,
    deleteItem,
    updateStatus
  }
}
