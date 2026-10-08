import { ref } from 'vue'
import axios from '@/utils/axios'

export function useMedicines() {
  const items = ref([])
  const loading = ref(false)
  const error = ref(null)
  
  const pagination = ref({
    page: 1,
    rowsPerPage: 10,
    rowsNumber: 0,
    sortBy: 'created_at',
    descending: true
  })
  
  const fetchItems = async (params = {}) => {
    loading.value = true
    error.value = null
    try {
      const response = await axios.get('/master-data/medicines', {
        params: {
          page: pagination.value.page,
          per_page: pagination.value.rowsPerPage,
          sortBy: pagination.value.sortBy,
          sortDesc: pagination.value.descending,
          ...params
        }
      })
      
      items.value = response.data.data
      pagination.value.rowsNumber = response.data.meta.total
    } catch (e: any) {
      error.value = e.response?.data?.message || 'Gagal memuat data obat'
      console.error('Error fetching medicines:', e)
    } finally {
      loading.value = false
    }
  }

  const createItem = async (data: any) => {
    loading.value = true
    error.value = null
    try {
      await axios.post('/master-data/medicines', data)
      return true
    } catch (e: any) {
      error.value = e.response?.data?.message || 'Gagal menyimpan data obat'
      console.error('Error creating medicine:', e)
      throw e
    } finally {
      loading.value = false
    }
  }

  const updateItem = async (id: number | string, data: any) => {
    loading.value = true
    error.value = null
    try {
      await axios.put(`/master-data/medicines/${id}`, data)
      return true
    } catch (e: any) {
      error.value = e.response?.data?.message || 'Gagal memperbarui data obat'
      console.error('Error updating medicine:', e)
      throw e
    } finally {
      loading.value = false
    }
  }

  const deleteItem = async (id: number | string) => {
    loading.value = true
    error.value = null
    try {
      await axios.delete(`/master-data/medicines/${id}`)
      return true
    } catch (e: any) {
      error.value = e.response?.data?.message || 'Gagal menghapus data obat'
      console.error('Error deleting medicine:', e)
      throw e
    } finally {
      loading.value = false
    }
  }

  const toggleStatus = async (item: any) => {
    const previousStatus = item.is_active;
    item.is_active = !previousStatus;
    error.value = null
    
    try {
      await axios.patch(`/master-data/medicines/${item.id}/status`, {
        is_active: item.is_active
      });
      return true;
    } catch (e: any) {
      console.error('Error toggling status:', e);
      item.is_active = previousStatus;
      error.value = e.response?.data?.message || 'Gagal mengubah status obat'
      throw e;
    }
  };

  return {
    items,
    loading,
    error,
    pagination,
    fetchItems,
    createItem,
    updateItem,
    deleteItem,
    toggleStatus
  }
}
