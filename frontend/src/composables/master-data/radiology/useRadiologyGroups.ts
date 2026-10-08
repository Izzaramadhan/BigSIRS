import { ref } from 'vue'
import axios from '@/utils/axios'
import type { RadiologyGroup, RadiologyGroupFilters } from '@/types/radiology'

export function useRadiologyGroups() {
  const items = ref<RadiologyGroup[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)
  
  const currentPage = ref(1)
  const perPage = ref(10)
  const totalItems = ref(0)

  const fetchItems = async (filters: RadiologyGroupFilters = {}) => {
    loading.value = true
    error.value = null
    try {
      const response = await axios.get('/master-data/radiology-groups', {
        params: {
          page: currentPage.value,
          per_page: perPage.value,
          ...filters
        }
      })
      
      items.value = response.data.data
      totalItems.value = response.data.meta.total
      currentPage.value = response.data.meta.current_page
    } catch (e: any) {
      error.value = e.response?.data?.message || 'Gagal memuat data group radiologi'
      items.value = []
      totalItems.value = 0
    } finally {
      loading.value = false
    }
  }

  const createItem = async (data: Partial<RadiologyGroup>) => {
    loading.value = true
    error.value = null
    try {
      const response = await axios.post('/master-data/radiology-groups', data)
      return response.data.data
    } catch (e: any) {
      error.value = e.response?.data?.message || 'Gagal menyimpan data group radiologi'
      throw e
    } finally {
      loading.value = false
    }
  }

  const updateItem = async (id: number, data: Partial<RadiologyGroup>) => {
    loading.value = true
    error.value = null
    try {
      const response = await axios.put(`/master-data/radiology-groups/${id}`, data)
      return response.data.data
    } catch (e: any) {
      error.value = e.response?.data?.message || 'Gagal memperbarui data group radiologi'
      throw e
    } finally {
      loading.value = false
    }
  }

  const deleteItem = async (id: number) => {
    loading.value = true
    error.value = null
    try {
      await axios.delete(`/master-data/radiology-groups/${id}`)
    } catch (e: any) {
      error.value = e.response?.data?.message || 'Gagal menghapus data group radiologi'
      throw e
    } finally {
      loading.value = false
    }
  }

  const toggleStatus = async (id: number, currentStatus: boolean) => {
    loading.value = true
    error.value = null
    try {
      await axios.patch(`/master-data/radiology-groups/${id}/status`, {
        is_active: !currentStatus
      })
    } catch (e: any) {
      error.value = e.response?.data?.message || 'Gagal mengubah status'
      throw e
    } finally {
      loading.value = false
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
    toggleStatus
  }
}
