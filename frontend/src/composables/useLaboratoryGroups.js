import { ref } from 'vue'
import axios from '@/utils/axios'

export function useLaboratoryGroups() {
  const laboratoryGroups = ref([])
  const meta = ref({})
  const loading = ref(false)
  const error = ref(null)

  const fetchLaboratoryGroups = async (params = {}) => {
    loading.value = true
    error.value = null
    try {
      const response = await axios.get('/master-data/laboratory-groups', { params })
      laboratoryGroups.value = response.data.data
      meta.value = response.data.meta
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to fetch laboratory groups'
      console.error('Error fetching laboratory groups:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  const getLaboratoryGroup = async (id) => {
    loading.value = true
    error.value = null
    try {
      const response = await axios.get(`/master-data/laboratory-groups/${id}`)
      return response.data.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to fetch laboratory group details'
      console.error('Error fetching laboratory group details:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  const createLaboratoryGroup = async (data) => {
    loading.value = true
    error.value = null
    try {
      const response = await axios.post('/master-data/laboratory-groups', data)
      return response.data.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to create laboratory group'
      console.error('Error creating laboratory group:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  const updateLaboratoryGroup = async (id, data) => {
    loading.value = true
    error.value = null
    try {
      const response = await axios.put(`/master-data/laboratory-groups/${id}`, data)
      return response.data.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to update laboratory group'
      console.error('Error updating laboratory group:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  const deleteLaboratoryGroup = async (id) => {
    loading.value = true
    error.value = null
    try {
      await axios.delete(`/master-data/laboratory-groups/${id}`)
      return true
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to delete laboratory group'
      console.error('Error deleting laboratory group:', err)
      return false
    } finally {
      loading.value = false
    }
  }

  return {
    laboratoryGroups,
    meta,
    loading,
    error,
    fetchLaboratoryGroups,
    getLaboratoryGroup,
    createLaboratoryGroup,
    updateLaboratoryGroup,
    deleteLaboratoryGroup
  }
}
