import { ref } from 'vue'
import { educationsService } from '@/services/master-data/educations.service'

export function useEducations() {
  const educations = ref([])
  const meta = ref({
    current_page: 1,
    last_page: 1,
    per_page: 10,
    total: 0
  })
  
  const loading = ref(false)
  const error = ref(null)

  const fetchEducations = async (params = {}) => {
    loading.value = true
    error.value = null
    try {
      const response = await educationsService.getEducations(params)
      educations.value = response.data || []
      if (response.meta) {
        meta.value = response.meta
      }
    } catch (err) {
      error.value = err.response?.data?.message || 'Gagal memuat data pendidikan'
      throw err
    } finally {
      loading.value = false
    }
  }

  const createEducation = async (data) => {
    loading.value = true
    error.value = null
    try {
      return await educationsService.createEducation(data)
    } catch (err) {
      error.value = err.response?.data?.message || 'Gagal menyimpan data'
      throw err
    } finally {
      loading.value = false
    }
  }

  const updateEducation = async (id, data) => {
    loading.value = true
    error.value = null
    try {
      return await educationsService.updateEducation(id, data)
    } catch (err) {
      error.value = err.response?.data?.message || 'Gagal memperbarui data'
      throw err
    } finally {
      loading.value = false
    }
  }

  const deleteEducation = async (id) => {
    loading.value = true
    error.value = null
    try {
      await educationsService.deleteEducation(id)
    } catch (err) {
      error.value = err.response?.data?.message || 'Gagal menghapus data'
      throw err
    } finally {
      loading.value = false
    }
  }


  return {
    educations,
    meta,
    loading,
    error,
    fetchEducations,
    createEducation,
    updateEducation,
    deleteEducation
  }
}
