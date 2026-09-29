import { ref } from 'vue'
import ProcedureUserMappingService from '@/services/master-data/procedure-user-mappings.service'

export function useProcedureUserMappings() {
  const mappings = ref([])
  const loading = ref(false)
  const error = ref(null)
  const meta = ref({
    current_page: 1,
    last_page: 1,
    per_page: 10,
    total: 0
  })

  const fetchMappings = async (params = {}) => {
    loading.value = true
    error.value = null
    try {
      const response = await ProcedureUserMappingService.getMappings(params)
      mappings.value = response.data
      meta.value = response.meta
    } catch (err) {
      error.value = err.response?.data?.message || 'Gagal memuat data mapping'
      console.error(err)
    } finally {
      loading.value = false
    }
  }

  const createMapping = async (data) => {
    loading.value = true
    error.value = null
    try {
      await ProcedureUserMappingService.createMapping(data)
      return true
    } catch (err) {
      error.value = err.response?.data?.message || 'Gagal menyimpan mapping'
      throw err
    } finally {
      loading.value = false
    }
  }

  const updateMapping = async (id, data) => {
    loading.value = true
    error.value = null
    try {
      await ProcedureUserMappingService.updateMapping(id, data)
      return true
    } catch (err) {
      error.value = err.response?.data?.message || 'Gagal memperbarui mapping'
      throw err
    } finally {
      loading.value = false
    }
  }

  const deleteMapping = async (id) => {
    loading.value = true
    error.value = null
    try {
      await ProcedureUserMappingService.deleteMapping(id)
      return true
    } catch (err) {
      error.value = err.response?.data?.message || 'Gagal menghapus mapping'
      throw err
    } finally {
      loading.value = false
    }
  }

  return {
    mappings,
    loading,
    error,
    meta,
    fetchMappings,
    createMapping,
    updateMapping,
    deleteMapping
  }
}
