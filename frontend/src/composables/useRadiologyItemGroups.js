import { ref } from 'vue'
import { radiologyItemGroupsService } from '@/services/master-data/radiologyItemGroups.service'
export function useRadiologyItemGroups() {
  
  const groups = ref([])
  const loading = ref(false)
  const error = ref(null)
  
  const pagination = ref({
    current_page: 1,
    per_page: 10,
    total: 0,
    last_page: 1
  })

  const fetchGroups = async (params = {}) => {
    loading.value = true
    error.value = null
    try {
      const currentParams = {
        page: pagination.value.current_page,
        per_page: pagination.value.per_page,
        ...params
      }
      
      const response = await radiologyItemGroupsService.getGroups(currentParams)
      
      groups.value = response.data
      
      if (response.meta) {
        pagination.value = {
          current_page: response.meta.current_page,
          per_page: response.meta.per_page,
          total: response.meta.total,
          last_page: response.meta.last_page
        }
      }
    } catch (err) {
      error.value = err
      // error toast handled in view
    } finally {
      loading.value = false
    }
  }

  const createGroup = async (payload) => {
    loading.value = true
    error.value = null
    try {
      await radiologyItemGroupsService.createGroup(payload)
      // success toast handled in view
      return true
    } catch (err) {
      error.value = err
      // error toast handled in view
      throw err
    } finally {
      loading.value = false
    }
  }

  const updateGroup = async (id, payload) => {
    loading.value = true
    error.value = null
    try {
      await radiologyItemGroupsService.updateGroup(id, payload)
      // success toast handled in view
      return true
    } catch (err) {
      error.value = err
      // error toast handled in view
      throw err
    } finally {
      loading.value = false
    }
  }

  const deleteGroup = async (id) => {
    loading.value = true
    error.value = null
    try {
      await radiologyItemGroupsService.deleteGroup(id)
      // success toast handled in view
      return true
    } catch (err) {
      error.value = err
      // error toast handled in view
      throw err
    } finally {
      loading.value = false
    }
  }

  const handlePageChange = (page) => {
    pagination.value.current_page = page
    fetchGroups()
  }

  const handleLimitChange = (limit) => {
    pagination.value.current_page = 1
    pagination.value.per_page = limit
    fetchGroups()
  }

  return {
    groups,
    loading,
    error,
    pagination,
    fetchGroups,
    createGroup,
    updateGroup,
    deleteGroup,
    handlePageChange,
    handleLimitChange
  }
}
