import { ref, computed } from 'vue'
import { radiologyCategoriesService } from '@/services/master-data/radiologyCategories.service'

export function useRadiologyCategories() {
  const categories = ref([])
  const meta = ref({
    current_page: 1,
    per_page: 10,
    total: 0
  })
  const loading = ref(false)
  const error = ref(null)

  const fetchCategories = async (params = {}) => {
    loading.value = true
    error.value = null
    try {
      const response = await radiologyCategoriesService.getAll({
        page: meta.value.current_page,
        per_page: meta.value.per_page,
        ...params
      })
      categories.value = response.data.data
      if (response.data.meta) {
        meta.value = response.data.meta
      }
    } catch (err) {
      error.value = err.response?.data?.message || 'Gagal memuat data kategori radiologi.'
      console.error('Error fetching radiology categories:', err)
    } finally {
      loading.value = false
    }
  }

  const createCategory = async (data) => {
    loading.value = true
    error.value = null
    try {
      await radiologyCategoriesService.create(data)
      return { success: true }
    } catch (err) {
      const errorMessage = err.response?.data?.message || 'Gagal menyimpan kategori radiologi.'
      error.value = errorMessage
      return { success: false, error: errorMessage, errors: err.response?.data?.errors }
    } finally {
      loading.value = false
    }
  }

  const updateCategory = async (id, data) => {
    loading.value = true
    error.value = null
    try {
      await radiologyCategoriesService.update(id, data)
      return { success: true }
    } catch (err) {
      const errorMessage = err.response?.data?.message || 'Gagal memperbarui kategori radiologi.'
      error.value = errorMessage
      return { success: false, error: errorMessage, errors: err.response?.data?.errors }
    } finally {
      loading.value = false
    }
  }

  const deleteCategory = async (id) => {
    loading.value = true
    error.value = null
    try {
      await radiologyCategoriesService.delete(id)
      return { success: true }
    } catch (err) {
      const errorMessage = err.response?.data?.message || 'Gagal menghapus kategori radiologi.'
      error.value = errorMessage
      return { success: false, error: errorMessage }
    } finally {
      loading.value = false
    }
  }

  return {
    categories,
    meta,
    loading,
    error,
    fetchCategories,
    createCategory,
    updateCategory,
    deleteCategory,
    isEmpty: computed(() => !loading.value && categories.value.length === 0)
  }
}
