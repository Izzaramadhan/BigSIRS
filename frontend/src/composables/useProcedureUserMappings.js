import { ref, reactive } from 'vue'
import ProcedureUserMappingService from '@/services/master-data/procedure-user-mappings.service'

export function useProcedureUserMappings() {
  const mappings = ref([])
  const loading = ref(false)
  const submitting = ref(false)
  const error = ref(null)

  const pagination = reactive({
    current_page: 1,
    last_page: 1,
    per_page: 10,
    total: 0,
    from: 0,
    to: 0
  })

  const fetchMappings = async (params = {}) => {
    loading.value = true
    error.value = null
    try {
      const response = await ProcedureUserMappingService.getMappings({
        page: pagination.current_page,
        per_page: pagination.per_page,
        ...params
      })
      mappings.value = response.data || []
      if (response.meta) {
        pagination.current_page = response.meta.current_page
        pagination.last_page = response.meta.last_page
        pagination.per_page = response.meta.per_page
        pagination.total = response.meta.total
        pagination.from = (response.meta.current_page - 1) * response.meta.per_page + 1
        pagination.to = Math.min(response.meta.current_page * response.meta.per_page, response.meta.total)
      }
      return response
    } catch (err) {
      error.value = err.response?.data?.message || err.message || 'Gagal memuat data mapping tindakan user'
      console.error(err)
      throw err
    } finally {
      loading.value = false
    }
  }

  const getMapping = async (id) => {
    error.value = null
    try {
      return await ProcedureUserMappingService.getMapping(id)
    } catch (err) {
      error.value = err.response?.data?.message || err.message || 'Gagal memuat detail mapping tindakan user'
      throw err
    }
  }

  const createMapping = async (data) => {
    submitting.value = true
    error.value = null
    try {
      const response = await ProcedureUserMappingService.createMapping(data)
      return { success: true, data: response }
    } catch (err) {
      error.value = err.response?.data?.message || err.message || 'Gagal menyimpan mapping tindakan user'
      return { success: false, error: err }
    } finally {
      submitting.value = false
    }
  }

  const updateMapping = async (id, data) => {
    submitting.value = true
    error.value = null
    try {
      const response = await ProcedureUserMappingService.updateMapping(id, data)
      return { success: true, data: response }
    } catch (err) {
      error.value = err.response?.data?.message || err.message || 'Gagal memperbarui mapping tindakan user'
      return { success: false, error: err }
    } finally {
      submitting.value = false
    }
  }

  const deleteMapping = async (id) => {
    submitting.value = true
    error.value = null
    try {
      const response = await ProcedureUserMappingService.deleteMapping(id)
      return { success: true, data: response }
    } catch (err) {
      error.value = err.response?.data?.message || err.message || 'Gagal menghapus mapping tindakan user'
      return { success: false, error: err }
    } finally {
      submitting.value = false
    }
  }

  return {
    mappings,
    pagination,
    meta: pagination,
    loading,
    submitting,
    error,
    fetchMappings,
    getMapping,
    createMapping,
    updateMapping,
    deleteMapping
  }
}
